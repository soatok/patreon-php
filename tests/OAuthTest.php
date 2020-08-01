<?php
namespace Patreon\Tests;

use ParagonIE\HiddenString\HiddenString;
use Patreon\OAuth;
use PHPUnit\Framework\TestCase;

/**
 * Class OAuthTest
 * @package Patreon\Tests
 */
class OAuthTest extends TestCase
{
    public function testOAuthConstructor()
    {
        $this->assertInstanceOf(
            OAuth::class,
            new OAuth('a', 'b')
        );

        $this->assertInstanceOf(
            OAuth::class,
            new OAuth(new HiddenString('a'), new HiddenString('b'))
        );
    }

    public function testCaCertBundlePersist()
    {
        $path = realpath(__DIR__ . '/certs');
        $oa = new OAuth(new HiddenString('a'), new HiddenString('b'), $path);
        $file = $oa->getLatestCaCerts();
        $this->assertSame(
            $path,
            realpath(dirname($file))
        );
    }
}
