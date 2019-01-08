<?php

namespace Vendor;

use ArrayObject;
use Traversable;

class Foo
{
    /**
     * @var array
     */
    var $bar;

    /**
     * @var array
     */
    public $baz;

    /**
     * @var array
     */
    protected $qux;

    /**
     * @var array
     */
    private $quux;

    /**
     * @var iterable
     */
    private $quuz;

    /**
     * @var ArrayObject
     */
    private $corge;

    /**
     * @var array<string>
     */
    private $woo;

    /**
     * @var Traversable<string>
     */
    private $wooo;

    public function food(array $arg) : array
    {
    }

    /**
     * @param array<string> $arg
     *
     * @return array<string>
     */
    public function bar(array $arg) : array
    {
    }

    /**
     * @param iterable<string> $arg
     *
     * @return iterable<string>
     */
    public function bara(iterable $arg) : iterable
    {
    }

    /**
     * @param Traversable<string> $arg
     *
     * @return Traversable<string>
     */
    public function barb(Traversable $arg) : Traversable
    {
    }
}
