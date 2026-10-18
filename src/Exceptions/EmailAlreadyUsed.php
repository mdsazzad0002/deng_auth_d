<?php

namespace Dengrweb\DAuth\Exceptions;

use RuntimeException;

/**
 * A different local user already has this email, and it could not be linked
 * (the Accounts email is unverified, or link_by_email is off).
 */
class EmailAlreadyUsed extends RuntimeException {}
