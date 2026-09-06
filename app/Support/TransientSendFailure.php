<?php

namespace App\Support;

use RuntimeException;

/**
 * Thrown when WhatsApp rejects a send for a reason that may clear on its own
 * (rate limit, 5xx). The message stays queued and the job retries with backoff.
 */
class TransientSendFailure extends RuntimeException
{
}
