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
     * @var array<string>
     */
    private $woo;

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
}
