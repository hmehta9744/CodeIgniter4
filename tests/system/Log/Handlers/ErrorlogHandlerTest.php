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

namespace CodeIgniter\Log\Handlers;

use CodeIgniter\Log\Exceptions\LogException;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
#[Group('Others')]
final class ErrorlogHandlerTest extends CIUnitTestCase
{
    public function testHandlerThrowsOnInvalidMessageType(): void
    {
        $this->expectException(LogException::class);
        $this->getMockedHandler(['messageType' => 2]);
    }

    public function testErrorLoggingWithErrorLog(): void
    {
        $logger = $this->getMockedHandler(['handles' => ['critical', 'error']]);
        $logger->method('errorLog')->willReturn(true);
        $logger->expects($this->once())->method('errorLog')->with("ERROR --> Test message.\n", 0);
        $this->assertTrue($logger->handle('error', 'Test message.'));
    }

    /**
     * @param array{handles?: list<string>, messageType?: int} $config
     *
     * @return ErrorlogHandler&MockObject
     */
    private function getMockedHandler(array $config = [])
    {
        return $this->getMockBuilder(ErrorlogHandler::class)
            ->onlyMethods(['errorLog'])
            ->setConstructorArgs([$config])
            ->getMock();
    }
}
