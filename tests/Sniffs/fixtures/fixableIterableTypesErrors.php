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
     * @var string[]
     */
    private $woo;

    /**
     * @var Traversable|string[]
     */
    private $wooo;

    /**
     * @var bool|string[]
     */
    private $woooo;

    public function food(array $arg) : array
    {
    }

    /**
     * @param string[] $arg
     *
     * @return string[]
     */
    public function bar(array $arg) : array
    {
    }

    /**
     * @param string[] $arg
     *
     * @return string[]
     */
    public function bara(iterable $arg) : iterable
    {
    }

    /**
     * @param Traversable|string[] $arg
     *
     * @return Traversable|string[]
     */
    public function barb(Traversable $arg) : Traversable
    {
    }

    /**
     * @param bool|string[] $arg
     *
     * @return bool|string[]
     */
    public function barc($arg)
    {
    }
}
