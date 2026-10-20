<?php

namespace App\Payments\Exceptions;

use RuntimeException;

/** A gateway call failed (network, auth, unexpected response). Message is safe to log. */
class GatewayException extends RuntimeException {}
