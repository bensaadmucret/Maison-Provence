<?php

namespace App\MessageHandler;

use App\Message\ExportOrdersMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ExportOrdersMessageHandler
{
    public function __invoke(ExportOrdersMessage $message)
    {
        // TODO: Implement export logic
    }
}
