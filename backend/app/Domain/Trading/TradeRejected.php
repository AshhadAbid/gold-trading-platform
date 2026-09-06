<?php

namespace App\Domain\Trading;

use RuntimeException;

final class TradeRejected extends RuntimeException
{
    public function __construct(string $message, public readonly string $reason, public readonly int $status = 422) { parent::__construct($message); }
}
