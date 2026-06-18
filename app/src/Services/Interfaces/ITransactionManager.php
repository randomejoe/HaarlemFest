<?php

namespace App\Services\Interfaces;

interface ITransactionManager
{
    public function run(callable $operation): mixed;
}
