<?php

use PHPIntel\Logger\Logger;
use PHPIntel\Context\Context;

use \PHPUnit_Framework_Assert as PHPUnit;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    public function testParentContext()
    {
        $context = new Context(array(
            'scope'      => "instance",
            'visibility' => "public",
            'variable'   => '$a',
            'prefix'     => "me",
            'class'      => "Acme\\Flower",
        ));

        $parent_context = $context->getParentContext('Acme\\Seed');
        PHPUnit::assertEquals('instance', $parent_context['scope']);
        PHPUnit::assertEquals('$a', $parent_context['variable']);
        PHPUnit::assertEquals('public', $parent_context['visibility']);
        PHPUnit::assertEquals('Acme\\Seed', $parent_context['class']);


        $context = new Context(array(
            'scope'      => "instance",
            'visibility' => "protected",
            'variable'   => '$a',
            'prefix'     => "me",
            'class'      => "Acme\\Flower",
        ));

        $parent_context = $context->getParentContext('Acme\\Seed');
        PHPUnit::assertEquals('protected', $parent_context['visibility']);

        $context = new Context(array(
            'scope'      => "instance",
            'visibility' => "private",
            'variable'   => '$a',
            'prefix'     => "me",
            'class'      => "Acme\\Flower",
        ));

        $parent_context = $context->getParentContext('Acme\\Seed');
        PHPUnit::assertEquals('protected', $parent_context['visibility']);
    }


    ////////////////////////////////////////////////////////////////////////
    // util


}

