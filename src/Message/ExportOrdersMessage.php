<?php

namespace App\Message;

class ExportOrdersMessage
{
    public function __construct(
        private readonly array $criteria = []
    ) {
    }

    public function getCriteria(): array
    {
        return $this->criteria;
    }
}
