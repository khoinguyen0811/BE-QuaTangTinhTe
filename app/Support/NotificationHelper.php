<?php

namespace App\Support;

use App\Models\Order;
use App\Services\NotificationService;

class NotificationHelper
{
    public static function sendNewOrderNotification(Order $order): void
    {
        app(NotificationService::class)->notifyOrderCreated($order);
    }
}
