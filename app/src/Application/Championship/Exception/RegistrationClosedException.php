<?php

declare(strict_types=1);

namespace App\Application\Championship\Exception;

final class RegistrationClosedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Les inscriptions sont fermées pour cette manche.');
    }
}
