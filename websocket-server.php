<?php
/**
 * Simple PHP WebSocket Server
 *
 * This script implements a WebSocket server from scratch using PHP sockets.
 */

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set time limit to infinity to keep the server running
set_time_limit(0);

require __DIR__.'/vendor/autoload.php';

$config = include __DIR__ . '/config/websocket.php';

// Configuration
$host = $config['url'];
$port = $config['port'];
$clients = [];
$max_clients = 10;

/**
 * Perform the WebSocket handshake
 */
function performHandshake($header, $client_socket) {
    // Extract the WebSocket key from the header
    if (preg_match('/Sec-WebSocket-Key:\s(.*)\r\n/', $header, $matches)) {
        $client_key = trim($matches[1]);

        // Create the server key by concatenating with the WebSocket GUID
        $server_key = base64_encode(sha1($client_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        // Create response header
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Accept: $server_key\r\n\r\n";

        // Send the response header
        socket_write($client_socket, $headers, strlen($headers));

        echo "Handshake completed\n";
        return true;
    }

    return false;
}

/**
 * Decode a WebSocket message
 */
function decodeWebSocketMessage($buffer) {
    if (empty($buffer)) {
        return false;
    }

    // Get the opcode from the first byte
    $opcode = ord($buffer[0]) & 0x0F;

    // Check if this is a text frame (opcode 1)
    if ($opcode !== 1) {
        // Handle close, ping, pong, or binary messages
        if ($opcode === 8) {
            // Close frame
            return false;
        }
        // For other frames, we'll just return false for now
        return false;
    }

    // Get the payload length
    $mask = ord($buffer[1]) >> 7;
    $payload_length = ord($buffer[1]) & 0x7F;

    $offset = 2;

    // Extended payload length
    if ($payload_length === 126) {
        $payload_length = unpack('n', substr($buffer, $offset, 2))[1];
        $offset += 2;
    } elseif ($payload_length === 127) {
        $payload_length = unpack('J', substr($buffer, $offset, 8))[1];
        $offset += 8;
    }

    // If the message is masked, get the masking key
    $masking_key = '';
    if ($mask) {
        $masking_key = substr($buffer, $offset, 4);
        $offset += 4;
    }

    // Extract and unmask the payload data
    $payload = substr($buffer, $offset, $payload_length);

    // Unmask the payload if it's masked
    if ($mask) {
        $unmasked = '';
        for ($i = 0; $i < $payload_length; $i++) {
            $unmasked .= $payload[$i] ^ $masking_key[$i % 4];
        }
        $payload = $unmasked;
    }

    return $payload;
}

/**
 * Encode a message according to the WebSocket protocol
 */
function encodeWebSocketMessage($message) {
    $message_length = strlen($message);

    // First byte: FIN bit (1), reserved bits (000), and opcode (0001 for text)
    $header = chr(0x81);

    // Second byte: Mask bit (0) and payload length
    if ($message_length <= 125) {
        $header .= chr($message_length);
    } elseif ($message_length <= 65535) {
        $header .= chr(126) . pack('n', $message_length);
    } else {
        $header .= chr(127) . pack('J', $message_length);
    }

    // Concatenate header and message
    return $header . $message;
}

/**
 * Send a message to a specific client
 */
function sendMessageToClient($client_socket, $message) {
    $encoded_message = encodeWebSocketMessage($message);
    socket_write($client_socket, $encoded_message, strlen($encoded_message));
}

/**
 * Send a server message to all clients
 */
function sendServerMessage($message, $clients) {
    $server_message = $message;
    $encoded_message = encodeWebSocketMessage($server_message);

    foreach ($clients as $client) {
        socket_write($client, $encoded_message, strlen($encoded_message));
    }
}

/**
 * Broadcast a message to all connected clients
 */
function broadcastMessage($message, $clients, $sender = null) {
    $encoded_message = encodeWebSocketMessage($message);

    foreach ($clients as $client) {
        // Don't send the message back to the sender (if provided)
        if ($sender !== null && $client === $sender) {
            continue;
        }

        socket_write($client, $encoded_message, strlen($encoded_message));
    }
}

/**
 * Disconnect a client
 */
function disconnectClient($client_socket, &$read_sockets, &$clients) {
    // Remove the client socket from the arrays
    $key = array_search($client_socket, $read_sockets);
    if ($key !== false) {
        unset($read_sockets[$key]);
    }

    $key = array_search($client_socket, $clients);
    if ($key !== false) {
        unset($clients[$key]);
    }

    // Get client IP address and port for logging
    if (socket_getpeername($client_socket, $client_ip, $client_port)) {
        echo "Client disconnected: $client_ip:$client_port\n";
    }

    // Close the socket
    socket_close($client_socket);
}

/**
 * Handle server commands
 */
function handleServerCommand($command, $clients) {
    global $socket; // Make sure we have access to the server socket for the /exit command

    $parts = explode(' ', $command, 2);
    $cmd = strtolower($parts[0]);
    $args = isset($parts[1]) ? $parts[1] : '';

    switch ($cmd) {
        case '/help':
            echo "Available commands:\n";
            echo "/broadcast [message] - Send a message to all clients\n";
            echo "/clients - List all connected clients\n";
            echo "/kick [client_id] - Disconnect a specific client\n";
            echo "/exit - Shut down the server\n";
            break;

        case '/broadcast':
            if (!empty($args)) {
                echo "Broadcasting: $args\n";
                sendServerMessage($args, $clients);
            } else {
                echo "Usage: /broadcast [message]\n";
            }
            break;

        case '/clients':
            if (empty($clients)) {
                echo "No clients connected.\n";
            } else {
                echo "Connected clients:\n";
                foreach ($clients as $id => $client) {
                    socket_getpeername($client, $ip, $port);
                    echo "[$id] $ip:$port\n";
                }
            }
            break;

        case '/kick':
            $client_id = intval($args);
            if (isset($clients[$client_id])) {
                $client_to_kick = $clients[$client_id];
                socket_getpeername($client_to_kick, $ip, $port);
                echo "Kicking client [$client_id] $ip:$port\n";

                // Send a message to the client before disconnecting
                sendMessageToClient($client_to_kick, "You have been disconnected by the server.");

                // Force disconnect
                socket_close($client_to_kick);
                unset($clients[$client_id]);
            } else {
                echo "Invalid client ID: $client_id\n";
            }
            break;

        case '/exit':
            echo "Shutting down server...\n";
            // Close all client connections
            foreach ($clients as $client) {
                socket_close($client);
            }
            // Close the server socket
            socket_close($socket);
            exit(0);
            break;

        default:
            echo "Unknown command: $cmd\n";
            echo "Type /help for available commands\n";
            break;
    }
}

// Create a TCP socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "Socket creation failed: " . socket_strerror(socket_last_error()) . "\n";
    exit(1);
}

// Set socket options
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

// Bind the socket to the host and port
if (socket_bind($socket, $host, $port) === false) {
    echo "Socket bind failed: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit(1);
}

// Start listening for connections
if (socket_listen($socket, $max_clients) === false) {
    echo "Socket listen failed: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit(1);
}

echo "WebSocket server started on $host:$port\n";

// Add the main socket to the list of readable sockets
$read_sockets = [$socket];

// Set up stdin as a non-blocking resource for server commands
$stdin = fopen('php://stdin', 'r');
stream_set_blocking($stdin, 0);

// Main loop
while (true) {
    // Create a copy of the read sockets array
    $read = $read_sockets;
    $write = null;
    $except = null;

    // Add stdin to be monitored for server commands
    $read_resources = [$stdin];

    // Monitor the sockets for changes with a short timeout (0.1 seconds)
    if (socket_select($read, $write, $except, 0, 100000) < 1) {
        // Check for server commands via stdin
        $input = fgets($stdin);
        if ($input !== false) {
            $input = trim($input);

            // Check if it's a server command
            if (strpos($input, '/') === 0) {
                handleServerCommand($input, $clients);
            } else if (!empty($input)) {
                // Treat as a message to send to all clients
                echo "Server: $input\n";
                sendServerMessage($input, $clients);
            }
        }
        continue;
    }

    // Handle new connections
    if (in_array($socket, $read)) {
        $client_socket = socket_accept($socket);

        // Get client IP address and port
        socket_getpeername($client_socket, $client_ip, $client_port);
        echo "New connection from $client_ip:$client_port\n";

        // Add the new client to the array of sockets to read from
        $read_sockets[] = $client_socket;
        $clients[] = $client_socket;

        // Handle the WebSocket handshake
        $header = socket_read($client_socket, 1024);
        performHandshake($header, $client_socket);

        // Send a welcome message to the new client
        sendMessageToClient($client_socket, "Welcome to the server!");

        // Remove the main socket from the read array
        $key = array_search($socket, $read);
        unset($read[$key]);
    }

    // Handle client messages
    foreach ($read as $client_socket) {
        $bytes = @socket_recv($client_socket, $buffer, 2048, 0);

        // If the client has disconnected
        if ($bytes === 0) {
            disconnectClient($client_socket, $read_sockets, $clients);
            continue;
        }

        // Process the message
        $message = decodeWebSocketMessage($buffer);
        if ($message !== false) {
            echo "Received message: $message\n";

            if (strpos($message, '/') === 0) {
                handleServerCommand($message, $clients);
            }

            // Broadcast the message to all connected clients
//            broadcastMessage($message, $clients, $client_socket);
        }
    }
}

