<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Exceptions;

/**
 * Thrown when file I/O operations fail.
 *
 * This includes file not found, permission denied, and read/write errors.
 */
class IOException extends GblnException
{
}
