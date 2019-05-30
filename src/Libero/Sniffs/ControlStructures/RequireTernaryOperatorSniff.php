<?php

declare(strict_types=1);

namespace Libero\CodingStandard\Libero\Sniffs\ControlStructures;

use Exception;
use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\ControlStructures\RequireTernaryOperatorSniff as BaseRequireTernaryOperatorSniff;
use function strpos;

// This is only present to catch exceptions.

final class RequireTernaryOperatorSniff extends BaseRequireTernaryOperatorSniff
{
    public function process(File $phpcsFile, $stackPtr) : void
    {
        try {
            parent::process($phpcsFile, $stackPtr);
        } catch (Exception $e) {
            if (false === strpos($e->getMessage(), 'without curly braces is not supported')) {
                throw $e;
            }
        }
    }
}
