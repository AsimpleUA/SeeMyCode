<?php
namespace App\Service\Websockets\SocketsEngine\SocketInterface;

use Ratchet\Wamp\Topic;

interface FmcSocketTopic
{
    public function sendData(Topic $topic, string $type, array $permitted = [], array $data = []) :void;
}