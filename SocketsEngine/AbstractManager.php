<?php


namespace App\Service\Websockets\SocketsEngine;


use App\Entity\User\User;
use App\Service\Websockets\SocketsEngine\SocketInterface\Manager;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Object_;

abstract class AbstractManager implements Manager
{
    /**
     * @var EntityManagerInterface
     */
    protected $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function ping(): void
    {
        try {
            if (!$this->manager->getConnection()->ping())
            {
                $this->manager->getConnection()->close();
                $this->manager->getConnection()->connect();
            }
        }
        catch(\Exception $e)
        {
            $this->manager->getConnection()->connect();
        }
    }

    /**
     * @param object $obj
     */
    public function refresh($obj): void
    {
        $this->manager->refresh($obj);
    }
}