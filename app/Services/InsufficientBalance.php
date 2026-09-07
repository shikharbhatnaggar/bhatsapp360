<?php

namespace App\Services;

use RuntimeException;

/** Raised inside a locked wallet transaction when the balance will not cover a debit. */
class InsufficientBalance extends RuntimeException
{
}
