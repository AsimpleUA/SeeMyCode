<?php


namespace App\Service\Websockets\Chat;

use App\Entity\User\User;
use App\Service\Websockets\SocketsEngine\AbstractTopic;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;

class ChatTopic extends AbstractTopic
{
    /**
     * @var ChatManager $manager
     */
    protected $manager;

    /**
     * ChatTopic constructor.
     * @param $socket_key
     * @param ChatAuth $auth
     * @param ChatManager $manager
     */
    public function __construct($socket_key, ChatAuth $auth, ChatManager $manager)
    {
        parent::__construct($socket_key, $auth, $manager);
        $this->name = 'app.topic.chat';
    }

    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) :void
    {
        $session_id = $connection->WAMP->sessionId;
        $dialog = $this->manager->checkDialog($request->getAttributes()->get('dialog_id'));
        $user = $this->auth->checkAuth($session_id, $dialog->getId());
        $this->auth->unauthorize($session_id, $dialog->getId());
        $this->sendData($topic, 'left', $this->manager->getPermitted($dialog), ['user' => $user->getId()]);
    }

    public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible) :void
    {
        parent::onPublish($connection, $topic, $request, $event, $exclude, $eligible);

        $session_id = $connection->WAMP->sessionId;
        if(!$dialog = $this->manager->checkDialog($request->getAttributes()->get('dialog_id')))
        {
            $this->sendData($topic, 'disconnect', [$session_id], ['reason' => 'Wrong dialog']);
            return;
        }
        $data = \is_string($event) ? json_decode($event, true) : $event;
        $permitted = $this->manager->getPermitted($dialog);

        if(isset($data['socket_key']) && $data['socket_key'] === $this->key)
        {
            $receivers = $data['permitted'] ?? [];
            unset($data['socket_key'], $data['permitted']);
            $this->sendData($topic, $data['type'], $receivers, $data);
            return;
        }

        if($data['type'] === 'auth')
        {
            if($user = $this->auth->authorize($data['token'], $session_id, $dialog->getId()))
            {
                $this->sendData($topic, 'authorized', [$session_id], [
                    'user' => [
                        'id' => $user->getId(),
                        'name' => $user->getUserName()
                    ]
                ]);
                $users = $this->manager->getUsersOnline($dialog->getId());
                /**
                 * @var User $online_user
                 */
                foreach ($users AS $online_user)
                {
                    if($user->getId() !== $online_user)
                    {
                        $this->sendData($topic, 'online', [$session_id], ['user' => $online_user]);
                    }
                }
                $this->sendData($topic, 'online', $permitted, ['user' => $user->getId()]);
            }
            else
            {
                $this->sendData($topic, 'disconnect', [$session_id], ['reason' => 'Wrong token']);
            }
            return;
        }

        if($user = $this->auth->checkAuth($session_id, $dialog->getId()))
        {
            if($user) {
                $this->manager->refresh($user);

                if(!$dialog->getUsers()->contains($user))
                {
                    $this->sendData($topic, 'disconnect', [$session_id], ['reason' => 'Wrong dialog']);
                }

                switch($data['type']) {
                    case 'update':
                        $this->sendData($topic, 'update', [$session_id], [
                            'list' => $this->manager->getList($dialog, $user)
                        ]);
                        break;
                    case 'list':
                        $page = $data['page'] ?? 1;
                        $limit =  $data['limit'] ?? 20;
                        $list = $this->manager->getList($dialog, $user, [ 'page' => $page, 'limit' => $limit]);
                        $total = $this->manager->getTotal($dialog, $user);
                        $this->sendData($topic, 'list', [$session_id], [
                            'list' => $list,
                            'count' => \count($list),
                            'page' => $page,
                            'limit' => $limit,
                            'total' => $total
                        ]);
                        break;
                    case 'message':
                        $this->sendData($topic, 'message', $permitted, [
                            'message' => $this->manager->message($dialog, $user, $data['message'])
                        ]);
                        break;
                    case 'empty':
                        $this->sendData($topic, 'empty', [$session_id], [
                            'status' => 'ok'
                        ]);
                        break;
                    case 'seen':
                        $permitted = $this->manager->setSeen($data['id']) ? [$session_id] : $permitted;
                        $this->sendData($topic, 'seen', $permitted, [
                            'id' => $data['id'],
                            'type' => $data['type']
                        ]);
                        break;
                    default:
                        $this->sendData($topic, 'system', [$session_id], [
                            'id' => 0,
                            'messageType' => 'error',
                            'text' => 'Unknown type. Nothing done.',
                        ]);
                        break;
                }
            }
        }
        else
        {
            $this->sendData($topic, 'disconnect', [$session_id], ['reason' => 'Unauthorized session']);
        }
    }
}