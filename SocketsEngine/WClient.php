<?php

namespace App\Service\Websockets\SocketsEngine;

use \Gos\Component\WebSocketClient\Wamp\Client;
use \Gos\Component\WebSocketClient\Wamp\Protocol;
use Gos\Component\WebSocketClient\Wamp\WebsocketPayload;

class WClient extends Client
{
    /**
     * @param string $topicUri
     * @param array $payload
     * @param array $exclude
     * @param array $eligible
     * @throws \Gos\Component\WebSocketClient\Exception\BadResponseException
     * @throws \Gos\Component\WebSocketClient\Exception\WebsocketException
     */
    public function publish($topicUri, $payload, $exclude = [], $eligible = [])
    {
        if (null !== $this->logger) {
            $this->logger->info(sprintf(
                'Publish in %s',
                $topicUri
            ));
        }

        $data = array(Protocol::MSG_PUBLISH, $topicUri, $payload, $exclude, $eligible);
        $this->send($data);
    }

    /**
     * @param array $data
     * @param int $opcode
     * @param bool $masked
     *
     * @return $this|Client
     *
     * @throws \Gos\Component\WebSocketClient\Exception\BadResponseException
     * @throws \Gos\Component\WebSocketClient\Exception\WebsocketException
     */
    protected function send($data, $opcode = WebsocketPayload::OPCODE_TEXT, $masked = true):WClient
    {
        $rawMessage = json_encode($data);
        $payload = new WebsocketPayload();
        $payload
            ->setOpcode($opcode)
            ->setMask($masked)
            ->setPayload($rawMessage);

        $encoded = $payload->encodePayload();

        if (0 === @fwrite($this->socket, $encoded)) {
            $this->connected = false;
            $this->connect($this->target);

            fwrite($this->socket, $encoded);
        }

        return $this;
    }
}