<?php


namespace App\Service\Websockets\SocketsEngine;


use App\Service\Websockets\SocketsEngine\SocketInterface\Auth;
use App\Service\Websockets\SocketsEngine\SocketInterface\FmcSocketTopic;
use App\Service\Websockets\SocketsEngine\SocketInterface\Manager;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

abstract class AbstractTopic implements TopicInterface, FmcSocketTopic
{
    /**
     * @var Auth $auth
     * @var AbstractManager $manager
     * @var string $key
     * @var string $name
     */
    protected $auth;
    protected $manager;
    protected $key;
    protected $name;

    public function __construct(string $socket_key, Auth $auth, Manager $manager)
    {
        $this->key = $socket_key;
        $this->auth = $auth;
        $this->manager = $manager;
        $this->name = 'app.topic.abstract';
    }

    /**
     * @param Topic $topic
     * @param string $type
     * @param array $permitted
     * @param array $data
     */
    public function sendData(Topic $topic, string $type, array $permitted = [], array $data = []) :void
    {
        $data['type'] = $type;
        $topic->broadcast($data, [], $permitted);
    }

    /**
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) :void
    {
        $session_id = $connection->WAMP->sessionId;
        $this->sendData($topic, 'subscribed', [$session_id]);
    }

    /**
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) :void
    {
        // TODO: Implement onUnSubscribe() method.
    }

    /**
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @param mixed $event
     * @param array $exclude
     * @param array $eligible
     */
    public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible) :void
    {
        $this->manager->ping();
    }

    /**
     * @return null|string
     */
    public function getName() :?string
    {
        return $this->name;
    }
}