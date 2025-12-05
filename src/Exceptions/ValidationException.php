<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Exceptions;

/**
 * Thrown when type validation fails during parsing.
 *
 * This includes integer range violations, string length violations,
 * and type mismatches.
 */
class ValidationException extends GblnException
{
}
