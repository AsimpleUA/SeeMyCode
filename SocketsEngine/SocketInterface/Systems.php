<?php
namespace App\Service\Websockets\SocketsEngine\SocketInterface;

interface Systems
{
    /**
     * @param array $data
     * @param array $options
     * @return array|null
     */
    public function system(array $data, array $options = []) :?array;

    /**
     * @param string $topic
     * @param array $data
     * @param array $arguments
     * @param array $context
     * @return mixed
     */
    public function send(string $topic, array $data = [], array $arguments = [], array $context = []);
}