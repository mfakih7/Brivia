<?php

namespace App\Exceptions;

use RuntimeException;

class StaleRecord extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This record was changed by someone else after you opened it. Reload the page and try again.');
    }
}
