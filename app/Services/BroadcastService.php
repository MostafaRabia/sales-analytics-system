<?php

namespace App\Services;

class BroadcastService
{
    private static $socket;

    public function __construct()
    {
        if (!isset(self::$socket)) {
            self::$socket = fsockopen("127.0.0.1", 8080, $errno, $errstr, 30);

            $key = base64_encode(random_bytes(16));

            $headers = "GET / HTTP/1.1\r\n" .
                "Host: 127.0.0.1:8080\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "Sec-WebSocket-Key: $key\r\n" .
                "Sec-WebSocket-Version: 13\r\n" .
                "\r\n";

            fwrite(self::$socket, $headers);
            fread(self::$socket, 1500);
        }
    }

    private function encodeWebSocketMessage($message): string
    {
        $frame = [];
        $frame[0] = 129; // 10000001: text message
        $length = strlen($message);

        if ($length <= 125) {
            $frame[1] = $length;
        } elseif ($length <= 65535) {
            $frame[1] = 126;
            $frame[2] = ($length >> 8) & 255;
            $frame[3] = $length & 255;
        } else {
            $frame[1] = 127;
            for ($i = 0; $i < 8; $i++) {
                $frame[2 + $i] = ($length >> (8 * (7 - $i))) & 255;
            }
        }

        $encoded = '';
        foreach ($frame as $b) {
            $encoded .= chr($b);
        }

        return $encoded . $message;
    }

    public function sendMessage(array $message): void
    {
        fwrite(self::$socket, $this->encodeWebSocketMessage('/broadcast '.json_encode($message)));
    }
}
