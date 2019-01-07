<?php

namespace Vendor;

use ArrayObject;

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

    public function foo(array $arg) : array
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
}
