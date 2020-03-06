<?php


namespace App\Service\Websockets\Chat;


use App\Entity\Chat\Connection;
use App\Entity\Chat\Dialog;
use App\Entity\Chat\Message;
use App\Entity\Chat\MessageType;
use App\Entity\Core\Engine\Route;
use App\Entity\User\User;
use App\Service\Websockets\SocketsEngine\AbstractSystems;

class ChatSystems extends AbstractSystems
{
    /**
     * @param array $data
     * @param array $options
     * @return array|null
     */
    public function system(array $data, array $options = []): ?array
    {
        $user = $options['user'];
        $dialog = $options['dialog'];
        if(!$dialog->getUsers()->contains($user))
        {
            return [
                'code' => 400,
                'data' => [
                    'status' => 'error',
                    'message' => 'Wrong user'
                ]
            ];
        }

        /**
         * @var User $original_user
         * @var MessageType $type
         */
        $original_user = $data['current_user'];
        if(!$type = $this->manager->getRepository(MessageType::class)->findOneBy(['name' => $data['result']['type']]))
        {
            return [
                'code' => 400,
                'data' => [
                    'status' => 'error',
                    'message' => 'Wrong type'
                ]
            ];
        }
        if ($type === 5 && $this->manager->getRepository(Message::class)->findOneBy(['dialog' => $dialog, 'user' => $user, 'type' => $type]))
        {
            return [
                'code' => 401,
                'data' => [
                    'status' => 'error',
                    'message' => 'Request have been added before'
                ]
            ];
        }

        $message = $this->createMessage($user, $dialog, $type, $data['result']['text']);
        $connections = $this->manager->getRepository(Connection::class)->findBy(['user' => $user, 'dialog' => $dialog]);
        $permitted = [];

        /**
         * @var Connection $conn
         */
        foreach($connections AS $conn)
        {
            $permitted[] = $conn->getSession();
        }
        if(\count($permitted) > 0)
        {
            $this->send(
            'app_topic_chat',
            [
                'type' => 'system',
                'message' => [
                    'id' => $message->getId(),
                    'messageType' => $message->getType()->getName(),
                    'text' => $message->getValue(),
                    'createdAt' => ['date' => $message->getCreatedAt()->format('Y-m-d H:i:s')],
                    'updatedAt' => ['date' => $message->getUpdatedAt()->format('Y-m-d H:i:s')],
                    'user' => [
                        'id' => $original_user->getId(),
                        'name' => $original_user->getUserName(),
                        'avatar' => $original_user->getAvatar() ? $original_user->getAvatar()->getFile() : '',
                        'urls' => $this->getUserUrls($user)
                    ]
                ],
                'permitted' => $permitted,
                'socket_key' => $data['socket_key']
            ],
            [
                'dialog_id' => $dialog->getId()
            ], []);
        }

        return [
            'code' => 200,
            'data' => [
                'status' => 'success'
            ]
        ];
    }

    /**
     * @param Message $message
     * @param User $user
     * @param $data
     * @return array
     */
    public function httpMessage(Message $message, User $user, $data) :array
    {
        $connections = $this->manager->getRepository(Connection::class)->findBy(['dialog' => $message->getDialog()]);
        $permitted = [];
        /**
         * @var Connection $conn
         */
        foreach($connections AS $conn)
        {
            $permitted[] = $conn->getSession();
        }

        if($permitted)
        {
            $this->pusher->push([
                'type' => 'message',
                'message' => [
                    'id' => $message->getId(),
                    'messageType' => null,
                    'text' => $message->getValue(),
                    'createdAt' => ['date' => $message->getCreatedAt()->format('Y-m-d H:i:s')],
                    'updatedAt' => ['date' => $message->getUpdatedAt()->format('Y-m-d H:i:s')],
                    'user' => [
                        'id' => $user->getId(),
                        'name' => $user->getUserName(),
                        'avatar' => $user->getAvatar() ? $user->getAvatar()->getFile() : '',
                        'urls' => $this->getUserUrls($user)
                    ]
                ],
                'permitted' => $permitted,
                'socket_key' => $data['socket_key']
            ], 'app_topic_chat', [
                'dialog_id' => $message->getDialog()->getId()
            ], [
                ''
            ]);
        }
        return [
            'code' => 200,
            'data' => [
                'status' => 'success'
            ]
        ];
    }

    /**
     * @param User $user
     * @param Dialog $dialog
     * @param MessageType $type
     * @param string $value
     *
     * @return Message
     */
    private function createMessage(User $user, Dialog $dialog, MessageType $type, string $value = '') :Message
    {
        $message = new Message();
        $dialog->addMessage($message);
        $message
            ->setType($type)
            ->setDialog($dialog)
            ->setUser($user)
            ->setValue($value);
        $this->manager->persist($message);
        $this->manager->flush();

        return $message;
    }

    /**
     * @param User $user
     * @return array
     */
    private function getUserUrls(User $user) :array
    {
        $arr = [];
        $urls = $this->manager->getRepository(Route::class)->findBy(['property' => 'user', 'value' => $user->getId()]);
        /**
         * @var Route $url
         */
        foreach ($urls AS $url) {
            $arr[$url->getLang()] = $url->getUrl();
        }

        return $arr;
    }
}