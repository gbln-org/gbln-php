<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Exceptions;

/**
 * Thrown when serialisation to GBLN format fails.
 *
 * This includes unsupported types and serialisation errors.
 */
class SerialiseException extends GblnException
{
}
