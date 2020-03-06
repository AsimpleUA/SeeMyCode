<?php
namespace App\Service\Websockets\SocketsEngine\SocketInterface;

interface Manager
{
    /**
     * @param $obj
     */
    public function refresh($obj):void;

    /**
     * @return void
     */
    public function ping():void;
}