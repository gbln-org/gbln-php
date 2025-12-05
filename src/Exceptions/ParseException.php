<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Exceptions;

/**
 * Thrown when parsing GBLN content fails.
 *
 * This includes syntax errors, unexpected tokens, and malformed GBLN strings.
 */
class ParseException extends GblnException
{
}
