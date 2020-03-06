<?php
namespace App\Service\Websockets\SocketsEngine\SocketInterface;

use App\Entity\User\User;

interface Auth
{
    /**
     * @param string $token
     * @param string $sessionId
     * @param int $obj_id
     * @return User|null
     */
    public function authorize(string $token, string $sessionId, int $obj_id = 0) :?User;

    /**
     * @param string $session_id
     * @param int $obj_id
     * @return mixed
     */
    public function unauthorize(string $session_id, int $obj_id = 0);

    /**
     * @param string $sessionId
     * @param int $obj_id
     * @return User|null
     */
    public function checkAuth(string $sessionId, int $obj_id = 0) :?User;
}