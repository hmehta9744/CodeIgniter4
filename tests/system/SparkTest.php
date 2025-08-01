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

namespace CodeIgniter;

use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[Group('Others')]
final class SparkTest extends CIUnitTestCase
{
    public function testCanUseOption(): void
    {
        ob_start();
        passthru('php spark list --simple');
        $output = ob_get_clean();

        $this->assertStringContainsString('cache:clear', (string) $output);
    }
}
