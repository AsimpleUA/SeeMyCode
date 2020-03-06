<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 06.03.2020
 * Time: 15:59
 */

namespace App\Service\Websockets\SocketsEngine;


use App\Service\Websockets\SocketsEngine\SocketInterface\Systems;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AbstractSystems implements Systems
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     * @var Pusher $pusher
     */
    protected $manager;
    protected $pusher;

    public function __construct(RegistryInterface $doctrine, Pusher $pusher)
    {
        $this->manager = $doctrine->getManager();
        $this->pusher = $pusher;
    }

    public function system(array $data, array $options = []):?array
    {
        // TODO: Implement system() method.
    }

    public function send(string $topic, array $data = [], array $arguments = [], array $context = [])
    {
        return $this->pusher->push($data, $topic, $arguments, $context);
    }
}