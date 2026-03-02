<?php

declare(strict_types=1);

namespace App\Application\Championship\Exception;

final class AlreadyRegisteredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Vous êtes déjà inscrit à cette manche.');
    }
}
