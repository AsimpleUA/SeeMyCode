<?php

namespace App\Service\Websockets\SocketsEngine;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Gos\Bundle\WebSocketBundle\Pusher\Message;
use App\Service\Websockets\SocketsEngine\WClient;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;

class Pusher extends AbstractPusher
{
    /**
     * @var array $settings
     */
    protected $settings;

    /**
     * Pusher constructor.
     * @param array $settings
     * @param WampRouter $router
     * @param MessageSerializer $serializer
     */
    public function __construct(array $settings, WampRouter $router, MessageSerializer $serializer)
    {
        $this->settings = $settings;
        $this->router = $router;
        $this->serializer = $serializer;
    }

    /**
     * @param string $data
     * @param array $context
     * @return string|void
     * @throws \Gos\Component\WebSocketClient\Exception\BadResponseException
     * @throws \Gos\Component\WebSocketClient\Exception\WebsocketException
     */
    protected function doPush($data, array $context)
    {
        if (false === $this->isConnected()) {
            $config = $this->settings;
            $this->connection = new WClient($config['host'], $config['port'], $config['ssl']);
            $this->connection->connect('/');
            $this->setConnected();
        }

        /**
         * @var Message $message
         */
        $message = $this->serializer->deserialize($data);
        $info = $message->getData();
        $this->connection->publish($message->getTopic(), $info, [], $info['permitted'] ?? []);
    }

    public function close()
    {
        if (false === $this->isConnected()) {
            return;
        }
        $this->connection->disconnect();
    }
}