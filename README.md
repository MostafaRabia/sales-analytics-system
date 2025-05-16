# Advanced Real-Time Sales Analytics System

## Which parts were assisted by AI?
    - I'm using phpstorm with github copilot, it's helping me write code faster, but very helpful in the CRUD creation
    - I used chatGPT and Claude for creating web-socket server and how to using it
    - Finally I have used chatGPT to understand the `/analytics` endpoint, I didn't know how to calculate `revenue` or what `Top products by sales` means, so he helped me, but the queries itself I know it

## Manual implementation details
    - I have implemented the web-socket server using pure php, I didn't use any framework for that
    - I have implemented the API integrations from the docs, read it then code it

## How to run and test the project
    - `composer install`
    - `php artisan key:generate`
    - `php artisan migrate --seed`
    - `php artisan serve`
    - `php websocket-server.php` // for the web-socket server
Then you can use Postman to call the endpoints and test the websocket
