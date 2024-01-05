// websocket_server.php

<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
require __DIR__ . '/vendor/autoload.php'; // this one on top

class UserCounter implements \Countable {
    private $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function add(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->broadcastUsersCount();
    }

    public function remove(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->broadcastUsersCount();
    }

    public function count() {
        return count($this->clients);
    }

    public function broadcastUsersCount() {
        $usersCount = $this->count();
        foreach ($this->clients as $client) {
            $client->send(json_encode(['usersCount' => $usersCount]));
        }
    }
}

class MyWebSocketServer implements MessageComponentInterface { // Updated interface name
    private $userCounter;

    public function __construct(UserCounter $userCounter) {
        $this->userCounter = $userCounter;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->userCounter->add($conn);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->userCounter->remove($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Handle incoming messages if needed
    }
}


$userCounter = new UserCounter();
$websocketServer = new MyWebSocketServer($userCounter);

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $websocketServer
        )
    ),
    8080
);

$server->run();
