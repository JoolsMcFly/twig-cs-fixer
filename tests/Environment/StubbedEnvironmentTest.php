<?php

declare(strict_types=1);

namespace TwigCsFixer\Tests\Environment;

use PHPUnit\Framework\TestCase;
use Twig\Extra\Cache\TokenParser\CacheTokenParser;
use Twig\Source;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use TwigCsFixer\Environment\StubbedEnvironment;

class StubbedEnvironmentTest extends TestCase
{
    public function testFilterIsStubbed(): void
    {
        $env = new StubbedEnvironment();

        static::assertInstanceOf(TwigFilter::class, $env->getFilter('foo'));
    }

    public function testFunctionIsStubbed(): void
    {
        $env = new StubbedEnvironment();

        static::assertInstanceOf(TwigFunction::class, $env->getFunction('foo'));
    }

    public function testTestIsStubbed(): void
    {
        $env = new StubbedEnvironment();

        static::assertInstanceOf(TwigTest::class, $env->getTest('foo'));

        static::assertNull($env->getTest('divisible')); // To not conflict with `divisible by`
        static::assertNull($env->getTest('same')); // To not conflict with `same as`
    }

    public function testParse(): void
    {
        $content = file_get_contents(__DIR__.'/Fixtures/tags.html.twig');
        static::assertNotFalse($content);

        $env = new StubbedEnvironment();
        $source = new Source($content, 'tags.html.twig');

        $env->parse($env->tokenize($source));
    }

    public function testParseCacheTag(): void
    {
        if (!class_exists(CacheTokenParser::class)) {
            static::markTestSkipped('The cache tag was added in Twig 3.2.');
        }

        $content = file_get_contents(__DIR__.'/Fixtures/cacheTag.html.twig');
        static::assertNotFalse($content);

        $env = new StubbedEnvironment();
        $source = new Source($content, 'tags.html.twig');

        $env->parse($env->tokenize($source));
    }
}
