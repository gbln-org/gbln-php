<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Exceptions;

use Exception;

/**
 * Base exception for all GBLN errors.
 *
 * All GBLN-specific exceptions inherit from this base class,
 * allowing callers to catch all GBLN errors with a single catch block.
 */
class GblnException extends Exception
{
}
