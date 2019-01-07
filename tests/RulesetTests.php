<?php

declare(strict_types=1);

namespace tests\Libero\CodingStandard;

use LogicException;
use ParseError;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Runner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use function array_combine;
use function array_filter;
use function array_map;
use function explode;
use function Functional\flatten;
use function Functional\select_keys;
use function implode;
use function ini_get;
use function is_string;
use function mb_convert_encoding;
use function mb_detect_encoding;
use function preg_match_all;
use function preg_replace;
use function sort;
use function str_replace;
use function strpos;
use function token_get_all;
use const TOKEN_PARSE;

final class RulesetTests extends TestCase
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    /** @var Runner */
    private static $codeSniffer;

    public static function setUpBeforeClass() : void
    {
        self::$codeSniffer = new Runner();
        self::$codeSniffer->config = new Config(['--standard=Libero']);
        self::$codeSniffer->init();
    }

    /**
     * @test
     * @dataProvider cases
     *
     * @param array<string> $messages
     */
    public function it_finds_and_fixes_violations(
        string $filename,
        string $contents,
        string $fixed,
        array $messages,
        ?string $description,
        ?string $fixedEncoding
    ) : void {
        $file = static::createFile($filename, $contents);
        $actual = flatten(static::getMessages($file));

        sort($actual);
        sort($messages);

        static::assertSame($messages, $actual, $description ?? '');
        if (is_string($fixedEncoding)) {
            static::assertSame($fixedEncoding, mb_detect_encoding($file->fixer->getContents(), 'UTF-8', true));
        }
        static::assertSame($fixed, $file->fixer->getContents());
    }

    /**
     * @return iterable<string,array<mixed>>
     */
    public function cases() : iterable
    {
        $files = Finder::create()->files()->in(__DIR__.'/cases');

        foreach ($files as $file) {
            if ('one-per-file' !== $file->getFilename()) {
                //continue;
            }

            preg_match_all('~(?:---)?([A-Z-]+?)---\s+([\s\S]+?)\n---~', $file->getContents(), $matches);

            $parts = array_combine(array_map('strtolower', $matches[1]), $matches[2]);

            $parts['filename'] = $parts['filename'] ?? 'test.php';

            if (isset($parts['messages'])) {
                $parts['messages'] = array_filter(explode("\n", $parts['messages']));
            }

            $keys = ['fixed', 'fixed-encoding', 'fixed-line-endings', 'messages'];
            if (empty($parts['contents'])) {
                throw new LogicException("Couldn't find contents in {$file->getRelativePathname()}");
            } elseif (empty(select_keys($parts, $keys))) {
                throw new LogicException("Expected one of ".implode(', ', $keys)." in {$file->getRelativePathname()}");
            }

            try {
                token_get_all($parts['contents'], TOKEN_PARSE);
            } catch (ParseError $exception) {
                $message = "Failed to parse content in {$file->getRelativePathname()}: {$exception->getMessage()}";
                throw new LogicException($message, 0, $exception);
            }

            if (!empty($parts['line-endings'])) {
                $parts['line-endings'] = str_replace(['\n', '\r'], ["\n", "\r"], $parts['line-endings']);
                $parts['contents'] = preg_replace('~\R~', $parts['line-endings'], $parts['contents']);
            }

            if (!empty($parts['fixed'])) {
                try {
                    token_get_all($parts['fixed'], TOKEN_PARSE);
                } catch (ParseError $exception) {
                    $message = "Failed to parse fixed in {$file->getRelativePathname()}: {$exception->getMessage()}";
                    throw new LogicException($message, 0, $exception);
                }
            }

            switch ($parts['encoding'] ?? 'UTF-8') {
                case 'UTF-8 (BE)':
                    $parts['contents'] = self::UTF8_BOM.$parts['contents'];
                    break;
                case 'UTF-8':
                    break;
                default:
                    $parts['contents'] = mb_convert_encoding($parts['contents'], $parts['encoding']);
            }

            $parts['fixed'] = $parts['fixed'] ?? $parts['contents'];

            if (!empty($parts['fixed-line-endings'])) {
                $parts['fixed-line-endings'] = str_replace(['\n', '\r'], ["\n", "\r"], $parts['fixed-line-endings']);
                $parts['fixed'] = preg_replace('~\R~', $parts['fixed-line-endings'], $parts['fixed']);
            }

            yield $file->getRelativePathname() => [
                "{$file->getRelativePathname()}/{$parts['filename']}",
                $parts['contents'],
                $parts['fixed'],
                $parts['messages'] ?? [],
                $parts['description'] ?? null,
                $parts['fixed-encoding'] ?? null,
            ];
        }
    }

    private function createFile(string $filename, string $content) : File
    {
        if ('' === ini_get('short_open_tag') && false === strpos($content, '<?php')) {
            static::markTestSkipped('short_open_tag option is disabled');
        }

        $file = new DummyFile(
            "phpcs_input_file:before/${filename}\n{$content}",
            self::$codeSniffer->ruleset,
            self::$codeSniffer->config
        );

        $file->process();

        $file->fixer->fixFile();

        $file = new DummyFile(
            "phpcs_input_file:after/${filename}\n{$file->fixer->getContents()}",
            self::$codeSniffer->ruleset,
            self::$codeSniffer->config
        );

        $file->process();

        return $file;
    }

    /**
     * @return iterable<string>
     */
    private function getMessages(File $file) : iterable
    {
        foreach ([$file->getErrors(), $file->getWarnings()] as $messages) {
            foreach ($messages as $line => $lineMessages) {
                foreach ($lineMessages as $column => $columnMessages) {
                    foreach ($columnMessages as $data) {
                        yield "{$line}:{$column} {$data['source']}";
                    }
                }
            }
        }
    }
}
