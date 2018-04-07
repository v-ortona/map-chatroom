<?php
require 'vendor/autoload.php';
//use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\MessageComponentInterface; //for binary msgs
use Ratchet\RFC6455\Messaging\MessageInterface; //for binary msgs
use Ratchet\ConnectionInterface;
use MessagePack\Packer;
use MessagePack\BufferUnpacker;


class Chat implements MessageComponentInterface {
    protected $clients;

    protected $playerData = array();  // assoc array of player names and locations

    public function __construct() {
        $this->clients = new \SplObjectStorage; //some PHP data structure
    }

    public function onOpen(ConnectionInterface $conn) {
        //Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    //When the server receives a msg from a client, send it to all other clients
    public function onMessage(ConnectionInterface $conn, MessageInterface $msg) {

            // add the unique ID of the player to the msg
            $type = substr($msg, 6, 1);
            if($type == "p") {  // if position msg
                $json = json_decode($msg, true);
                print_r($json);
                $playerData[(int)$conn->resourceId] = $json["i"];    // add newpos to playerData

                $posmsg = json_encode(array("t"=>"p", "d" => array($conn->resourceId => $json["i"])));


                // send msg
                $numRecv = count($this->clients) - 1;
                echo sprintf('Connection %d sending message "%s" to %d other connections' . "\n", $conn->resourceId, $posmsg, $numRecv);
                
                foreach ($this->clients as $client) {
                    if($conn !== $client) {
                        $client->send($posmsg); //send the $msg to the $client (method semantics confusing)
                    }
                }
            }

    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occured: {$e->getMessage()}\n";

        $conn->close();
    }
}