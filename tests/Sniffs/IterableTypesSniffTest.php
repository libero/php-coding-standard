<?php

declare(strict_types=1);

namespace tests\Libero\CodingStandard\Sniffs;

use Libero\CodingStandard\Libero\Sniffs\Types\IterableTypesSniff;
use SlevomatCodingStandard\Sniffs\TestCase;

final class IterableTypesSniffTest extends TestCase
{
    public function testNoErrors() : void
    {
        self::assertNoSniffErrorInFile(self::checkFile(__DIR__.'/fixtures/iterableTypesNoErrors.php'));
    }

    public function testErrors() : void
    {
        $report = self::checkFile(__DIR__.'/fixtures/iterableTypesErrors.php');

        self::assertSniffError($report, 12, IterableTypesSniff::CODE_MISSING_PROPERTY_ITERABLE_TYPE_HINT);
        self::assertSniffError($report, 17, IterableTypesSniff::CODE_MISSING_PROPERTY_ITERABLE_TYPE_HINT);
        self::assertSniffError($report, 22, IterableTypesSniff::CODE_MISSING_PROPERTY_ITERABLE_TYPE_HINT);
        self::assertSniffError($report, 27, IterableTypesSniff::CODE_MISSING_PROPERTY_ITERABLE_TYPE_HINT);
        self::assertSniffError($report, 32, IterableTypesSniff::CODE_MISSING_PROPERTY_ITERABLE_TYPE_HINT);
        self::assertSniffError($report, 37, IterableTypesSniff::CODE_MISSING_PROPERTY_ITERABLE_TYPE_HINT);
        self::assertSniffError($report, 42, IterableTypesSniff::CODE_PROPERTY_ITERABLE_TYPE_HINT_GENERICS);
        self::assertSniffError($report, 44, IterableTypesSniff::CODE_MISSING_FUNCTION_ITERABLE_ARGUMENT_TYPE_HINT);
        self::assertSniffError($report, 44, IterableTypesSniff::CODE_MISSING_FUNCTION_ITERABLE_RETURN_TYPE_HINT);
        self::assertSniffError($report, 53, IterableTypesSniff::CODE_ARGUMENT_ITERABLE_TYPE_HINT_GENERICS);
        self::assertSniffError($report, 53, IterableTypesSniff::CODE_METHOD_ITERABLE_RETURN_TYPE_HINT_GENERICS);

        self::assertSame(11, $report->getErrorCount());
    }

    public function testFixableReturnTypeHints() : void
    {
        $report = self::checkFile(
            __DIR__.'/fixtures/fixableIterableTypesErrors.php',
            [],
            [
                IterableTypesSniff::CODE_MISSING_FUNCTION_ITERABLE_ARGUMENT_TYPE_HINT,
                IterableTypesSniff::CODE_MISSING_FUNCTION_ITERABLE_RETURN_TYPE_HINT,
                IterableTypesSniff::CODE_MISSING_PROPERTY_ITERABLE_TYPE_HINT,
                IterableTypesSniff::CODE_ARGUMENT_ITERABLE_TYPE_HINT_GENERICS,
                IterableTypesSniff::CODE_METHOD_ITERABLE_RETURN_TYPE_HINT_GENERICS,
                IterableTypesSniff::CODE_PROPERTY_ITERABLE_TYPE_HINT_GENERICS,
            ]
        );
        self::assertAllFixedInFile($report);
    }

    protected static function getSniffName() : string
    {
        return IterableTypesSniff::SNIFF_NAME;
    }

    protected static function getSniffClassName() : string
    {
        return IterableTypesSniff::class;
    }
}
