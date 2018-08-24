<?php

namespace tests\Libero\CodingStandard;

use LogicException;
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
use function ini_get;
use function preg_match_all;
use function sort;
use function strpos;

final class Tests extends TestCase
{
    /** @var Runner */
    private static $codeSniffer;

    public static function setUpBeforeClass()
    {
        self::$codeSniffer = new Runner();
        self::$codeSniffer->config = new Config(['--standard=Libero']);
        self::$codeSniffer->init();
    }

    /**
     * @test
     * @dataProvider cases
     */
    public function it_finds_and_fixes_violations(
        string $filename,
        string $contents,
        string $fixed,
        array $messages,
        ?string $description
    ) : void {
        $file = $this->createFile($filename, $contents);
        $actual = flatten($this->getMessages($file));

        sort($actual);
        sort($messages);

        $this->assertSame($messages, $actual, $description);
        $this->assertSame($fixed, $file->fixer->getContents());
    }

    public function cases() : iterable
    {
        $files = Finder::create()->files()->in(__DIR__.'/cases');

        foreach ($files as $file) {
            preg_match_all('~(?:---)?([A-Z]+)---\s+([\s\S]+?)\n---~', $file->getContents(), $matches);

            $parts = array_combine(array_map('strtolower', $matches[1]), $matches[2]);

            if (isset($parts['messages'])) {
                $parts['messages'] = array_filter(explode("\n", $parts['messages']));
            }

            if (empty($parts['contents'])) {
                throw new LogicException("Couldn't find contents in {$file->getRelativePathname()}");
            }

            yield $file->getRelativePathname() => [
                $parts['filename'] ?? 'test.php',
                $parts['contents'],
                $parts['fixed'] ?? $parts['contents'],
                $parts['messages'] ?? [],
                $parts['description'] ?? null,
            ];
        }
    }

    private function createFile(string $filename, string $content) : File
    {
        if (!ini_get('short_open_tag') && false === strpos($content, '<?php')) {
            $this->markTestSkipped('short_open_tag option is disabled');
        }

        $file = new DummyFile(
            "phpcs_input_file:${filename}\n{$content}",
            self::$codeSniffer->ruleset,
            self::$codeSniffer->config
        );

        $file->process();

        $file->fixer->fixFile();

        $file = new DummyFile(
            "phpcs_input_file:${filename}\n{$file->fixer->getContents()}",
            self::$codeSniffer->ruleset,
            self::$codeSniffer->config
        );

        $file->process();

        return $file;
    }

    private function getMessages(File $file) : iterable
    {
        foreach ([$file->getErrors(), $file->getWarnings()] as $messages) {
            foreach ($messages as $line => $lineMessages) {
                foreach ($lineMessages as $column => $columnMessages) {
                    foreach ($columnMessages as $data) {
                        yield "{$line}:{$column} {$data['message']}";
                    }
                }
            }
        }
    }
}
