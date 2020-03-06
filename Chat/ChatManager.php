<?php


namespace App\Service\Websockets\Chat;


use App\Entity\Chat\Connection;
use App\Entity\Chat\Dialog;
use App\Entity\Chat\Message;
use App\Entity\Core\Engine\Route;
use App\Entity\User\User;
use App\Service\Websockets\SocketsEngine\AbstractManager;

class ChatManager extends AbstractManager
{
    /**
     * @param int $dialog_id
     * @return Dialog|null
     */
    public function checkDialog(int $dialog_id) :?Dialog
    {
        /**
         * @var Dialog $dialog
         */
        if($dialog = $this->manager->getRepository(Dialog::class)->find($dialog_id))
        {
            return $dialog;
        }
        return null;
    }

    /**
     * @param Dialog $dialog
     * @param User $user
     * @param array $info
     * @return array|null
     */
    public function message(Dialog $dialog, User $user, array $info = []) :?array
    {
        if($dialog->getUsers()->contains($user))
        {
            $message = new Message();
            $message->setDialog($dialog);
            $message->setUser($user);

            if(isset($info['text']) && $info['text'])
            {
                $message->setValue($info['text']);
            }

            if(isset($info['files']) && $info['files'])
            {
                foreach ($info['files'] AS $key => $file)
                {
                    echo 'file' . $key;
                }
            }
            $this->manager->persist($message);
            $this->manager->flush();

            return [
                'id' => $message->getId(),
                'text' => $message->getValue(),
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getUserName(),
                    'avatar' => $user->getAvatar() ? $user->getAvatar()->getFile() : '',
                    'urls' => $this->manager->getRepository(Route::class)->getUserRoutes($user->getId())
                ],
                'messageType' => null,
                'files' => [],
                'createdAt' => $message->getCreatedAt()->setTimezone(new \DateTimeZone('GMT-0')),
                'updatedAt' => $message->getUpdatedAt()->setTimezone(new \DateTimeZone('GMT-0'))
            ];
        }

        return null;
    }

    /**
     * @param Dialog $dialog
     * @return array
     */
    public function getPermitted(Dialog $dialog) :array
    {
        $connections = $this->manager->getRepository(Connection::class)->findBy(['dialog' => $dialog]);
        $sessions = [];
        foreach ($connections AS $connection)
        {
            $sessions[] = $connection->getSession();
        }
        return $sessions;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getUsersOnline(int $id) :array
    {
        $connections = $this->manager->getRepository(Connection::class)->findBy(['dialog' => $id]);
        $users = [];
        /**
         * @var Connection $connection
         */
        foreach ($connections AS $connection)
        {
            $users[] = $connection->getUser()->getId();
        }
        return $users;
    }

    /**
     * @param Dialog $dialog
     * @param User $user
     * @return int
     */
    public function getTotal(Dialog $dialog, User $user) :int
    {
        return $this->manager->getRepository(Message::class)->getTotal($dialog, $user);
    }

    /**
     * @param Dialog $dialog
     * @param  User $user
     * @param array $options
     * @return array|null
     */
    public function getList(Dialog $dialog, User $user, array $options = []) :?array
    {
        if(isset($options['page']))
        {
            $page = $options['page'] ?: 1;
        }
        else
        {
            $page = 1;
        }
        if(isset($options['limit']))
        {
            $limit = $options['limit'] ?: 20;
        }
        else
        {
            $limit = 20;
        }
        $list = $this->manager->getRepository(Message::class)->getList($dialog, $user, $page, $limit);
        foreach ($list AS $key => $item) {
            $item['seen'] = $item['seen'] ?: false;
            if(
                !$item['seen'] &&
                ((isset($item['messageType']) && $item['messageType'] && (int)$item['user']['id'] === $user->getId())||
                ((!isset($item['messageType']) || !$item['messageType']) && (int)$item['user']['id'] !== $user->getId()))
            )
            {
                $this->setSeen((int)$item['id']);
            }
            if(isset($item['messageType']) && ($item['messageType'] === 'request' || $item['messageType'] === 'question')) {
                $user_sender = null;
                //Pain
                foreach ($dialog->getUsers() AS $d_user)
                {
                    if($d_user->getId() !== $user->getId())
                    {
                        $user_sender = $d_user;
                        break;
                    }
                }
                if($user_sender)
                {
                    $user_urls = [];
                    $urls = $this->manager->getRepository(Route::class)->findBy(['property' => 'user', 'value' => $user_sender->getId()]);
                    /**
                     * @var Route $url
                     */
                    foreach ($urls AS $url) {
                        $user_urls[$url->getLang()] = $url->getUrl();
                    }
                    $item['user'] = [
                        'id' => $user_sender->getId(),
                        'name' => $user_sender->getUserName(),
                        'avatar' => $user_sender->getAvatar() ? $user_sender->getAvatar()->getFile() : '',
                        'urls' => $user_urls
                    ];
                } else {
                    $item['user']['wrong'] = false;
                }
            }
            $list[$key] = $item;
        }
        return $list;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function setSeen($id) :bool
    {
        if(!$msg = $this->manager->getRepository(Message::class)->find($id))
        {
            return false;
        }
        $msg->setSeen(true);
        $this->manager->persist($msg);
        $this->manager->flush();

        return (bool)$msg->getType();
    }
}