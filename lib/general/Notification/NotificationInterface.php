<?php
namespace Intensa\Logger\Notification;


interface NotificationInterface
{
    public function send();
    public function allowed();
}