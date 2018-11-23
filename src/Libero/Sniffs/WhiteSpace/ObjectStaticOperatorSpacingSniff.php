<?php

declare(strict_types=1);

namespace Libero\CodingStandard\Libero\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\ObjectOperatorSpacingSniff as BaseObjectOperatorSpacingSniff;
use const T_DOUBLE_COLON;

// This is only present to allow double colons to have separate configuration to the regular object operator.

final class ObjectStaticOperatorSpacingSniff extends BaseObjectOperatorSpacingSniff
{
    /**
     * @return array<int>
     */
    public function register() : array
    {
        return [T_DOUBLE_COLON];
    }
}
