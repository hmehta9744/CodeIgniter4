<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Publisher;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[Group('Others')]
final class PublisherInputTest extends CIUnitTestCase
{
    /**
     * A known, valid file
     */
    private string $file = SUPPORTPATH . 'Files/baker/banana.php';

    /**
     * A known, valid directory
     */
    private string $directory = SUPPORTPATH . 'Files/able/';

    /**
     * Initialize the helper, since some
     * tests call static methods before
     * the constructor would load it.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        helper(['filesystem']);
    }

    public function testAddPathFile(): void
    {
        $publisher = new Publisher(SUPPORTPATH . 'Files');

        $publisher->addPath('baker/banana.php');

        $this->assertSame([$this->file], $publisher->get());
    }

    public function testAddPathFileRecursiveDoesNothing(): void
    {
        $publisher = new Publisher(SUPPORTPATH . 'Files');

        $publisher->addPath('baker/banana.php', true);

        $this->assertSame([$this->file], $publisher->get());
    }

    public function testAddPathDirectory(): void
    {
        $publisher = new Publisher(SUPPORTPATH . 'Files');

        $expected = [
            $this->directory . 'apple.php',
            $this->directory . 'fig_3.php',
            $this->directory . 'prune_ripe.php',
        ];

        $publisher->addPath('able');

        $this->assertSame($expected, $publisher->get());
    }

    public function testAddPathDirectoryRecursive(): void
    {
        $publisher = new Publisher(SUPPORTPATH);

        $expected = [
            $this->directory . 'apple.php',
            $this->directory . 'fig_3.php',
            $this->directory . 'prune_ripe.php',
            SUPPORTPATH . 'Files/baker/banana.php',
            SUPPORTPATH . 'Files/baker/fig_3.php.txt',
        ];

        $publisher->addPath('Files');

        $this->assertSame($expected, $publisher->get());
    }

    public function testAddPaths(): void
    {
        $publisher = new Publisher(SUPPORTPATH . 'Files');

        $expected = [
            $this->directory . 'apple.php',
            $this->directory . 'fig_3.php',
            $this->directory . 'prune_ripe.php',
            SUPPORTPATH . 'Files/baker/banana.php',
        ];

        $publisher->addPaths([
            'able',
            'baker/banana.php',
        ]);

        $this->assertSame($expected, $publisher->get());
    }

    public function testAddPathsRecursive(): void
    {
        $publisher = new Publisher(SUPPORTPATH);

        $expected = [
            $this->directory . 'apple.php',
            $this->directory . 'fig_3.php',
            $this->directory . 'prune_ripe.php',
            SUPPORTPATH . 'Files/baker/banana.php',
            SUPPORTPATH . 'Files/baker/fig_3.php.txt',
            SUPPORTPATH . 'Log/Handlers/TestHandler.php',
        ];

        $publisher->addPaths([
            'Files',
            'Log',
        ], true);

        $this->assertSame($expected, $publisher->get());
    }

    public function testAddUri(): void
    {
        $mockCurl = $this->getMockBuilder(CURLRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $mockResponse = $this->createStub(ResponseInterface::class);

        $mockResponse->method('getBody')->willReturn('');
        $mockCurl->method('get')->willReturn($mockResponse);
        Services::injectMock('curlrequest', $mockCurl);

        $publisher = new Publisher();
        $publisher->addUri('https://raw.githubusercontent.com/codeigniter4/CodeIgniter4/develop/composer.json');

        $scratch = $this->getPrivateProperty($publisher, 'scratch');

        $this->assertSame([$scratch . 'composer.json'], $publisher->get());
        Services::resetSingle('curlrequest');
    }

    public function testAddUris(): void
    {
        $mockCurl = $this->getMockBuilder(CURLRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $mockResponse = $this->createStub(ResponseInterface::class);

        $mockResponse->method('getBody')->willReturn('');
        $mockCurl->method('get')->willReturn($mockResponse);
        Services::injectMock('curlrequest', $mockCurl);

        $publisher = new Publisher();
        $publisher->addUris([
            'https://raw.githubusercontent.com/codeigniter4/CodeIgniter4/develop/LICENSE',
            'https://raw.githubusercontent.com/codeigniter4/CodeIgniter4/develop/composer.json',
        ]);

        $scratch = $this->getPrivateProperty($publisher, 'scratch');

        $this->assertSame([$scratch . 'LICENSE', $scratch . 'composer.json'], $publisher->get());
        Services::resetSingle('curlrequest');
    }
}
