<?php


namespace App\Service\Websockets\Chat;


use App\Entity\Chat\Connection;
use App\Entity\Chat\Dialog;
use App\Entity\User\User;
use App\Service\Websockets\SocketsEngine\SocketInterface\Auth;
use Doctrine\Common\Persistence\ObjectManager;

class ChatAuth implements Auth
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param string $token
     * @param string $sessionId
     * @param int $obj_id
     * @return User|null
     */
    public function authorize(string $token, string $sessionId, int $obj_id = 0)
    {

        if($user = $this->manager->getRepository(User::class)->findOneBy(['apiToken' => $token]))
        {
            $dialog = $this->manager->getRepository(Dialog::class)->find($obj_id);
            if(!$dialog->getUsers()->contains($user))
            {
                return null;
            }
            if(!$connection = $this->manager->getRepository(Connection::class)->findOneBy(['user' => $user, 'dialog' => $obj_id, 'session' => $sessionId]))
            {
                $connection = new Connection();
                $connection->setUser($user);
                $connection->setDialog($dialog);
                $this->manager->persist($connection);
            }

            $connection->setSession($sessionId);
            $this->manager->flush();

            return $user;
        }
        return null;
    }

    public function unauthorize(string $session_id, int $obj_id = 0)
    {
        if($connection = $this->manager->getRepository(Connection::class)->findOneBy(['session' => $session_id, 'dialog' => $obj_id]))
        {
            $this->manager->remove($connection);
            $this->manager->flush();
            return true;
        }

        return false;
    }

    /**
     * @param string $sessionId
     * @param int $obj_id
     * @return User|null
     */
    public function checkAuth(string $sessionId, int $obj_id = 0)
    {
        if($connection = $this->manager->getRepository(Connection::class)->findOneBy(['session' => $sessionId, 'dialog' => $obj_id]))
        {
            return $connection->getUser();
        }

        return null;
    }
}