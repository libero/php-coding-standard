<?php

namespace Vendor;

use ArrayObject;
use Iterator;

class Foo
{
    var $foo;

    /**
     * @var mixed
     */
    var $bar;

    /**
     * @var string|false
     */
    public $baz;

    /**
     * @var array<string>
     */
    protected $qux;

    /**
     * @var array<int,array<string,int>>
     */
    private $quux;

    /**
     * @var iterable<string>
     */
    private $quuz;

    /**
     * @var iterable<int,array<string,int>>
     */
    private $corge;

    /**
     * @var ArrayObject<int,Iterator<string,int>>
     */
    private $grault;

    /**
     * @var array<string>|string
     */
    private $garply;

    /**
     * @var Class&iterable<string>
     */
    private $waldo;

    /**
     * @var Class<string>
     */
    private $fred;

    /**
     * @param mixed $arg
     *
     * @return mixed
     */
    public function bar($arg)
    {
    }

    /**
     * @param string|false $arg
     *
     * @return string|false
     */
    public function baz($arg)
    {
    }

    /**
     * @param array<string> $arg
     *
     * @return array<string>
     */
    public function qux($arg)
    {
    }

    /**
     * @param array<int,array<string,int>> $arg
     *
     * @return array<int,array<string,int>>
     */
    public function quux($arg)
    {
    }

    /**
     * @param iterable<string> $arg
     *
     * @return iterable<string>
     */
    public function quuz($arg)
    {
    }

    /**
     * @param iterable<int,array<string,int>> $arg
     *
     * @return iterable<int,array<string,int>>
     */
    public function corge($arg)
    {
    }

    /**
     * @param ArrayObject<int,Iterator<string,int>> $arg
     *
     * @return ArrayObject<int,Iterator<string,int>>
     */
    public function grault($arg)
    {
    }

    /**
     * @param array<string>|string $arg
     *
     * @return array<string>|string
     */
    public function garply()
    {
    }

    /**
     * @param Class&iterable<string> $arg
     *
     * @return Class&iterable<string>
     */
    public function waldo()
    {
    }

    /**
     * @param ArrayObject<string> $arg
     *
     * @return ArrayObject<string>
     */
    public function fred($arg)
    {
    }
}
