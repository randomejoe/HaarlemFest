<?php

namespace App\Services;

use App\Services\Interfaces\ITransactionManager;
use PDO;
use Throwable;

class PdoTransactionManager implements ITransactionManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(callable $operation): mixed
    {
        try {
            $this->pdo->beginTransaction();
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
