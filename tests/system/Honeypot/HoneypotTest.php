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

namespace CodeIgniter\Honeypot;

use CodeIgniter\Config\Factories;
use CodeIgniter\Filters\Filters;
use CodeIgniter\Honeypot\Exceptions\HoneypotException;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\Honeypot as HoneypotConfig;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[BackupGlobals(true)]
#[Group('Others')]
final class HoneypotTest extends CIUnitTestCase
{
    private HoneypotConfig $config;
    private Honeypot $honeypot;

    /**
     * @var CLIRequest|IncomingRequest
     */
    private RequestInterface $request;

    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config   = new HoneypotConfig();
        $this->honeypot = new Honeypot($this->config);

        unset($_POST[$this->config->name]);
        $_SERVER['REQUEST_METHOD']  = 'POST';
        $_POST[$this->config->name] = 'hey';

        $this->request  = service('request', null, false);
        $this->response = service('response');
    }

    public function testAttachHoneypot(): void
    {
        $this->response->setBody('<form></form>');

        $this->honeypot->attachHoneypot($this->response);
        $this->assertStringContainsString($this->config->name, (string) $this->response->getBody());

        $this->response->setBody('<div></div>');
        $this->assertStringNotContainsString($this->config->name, (string) $this->response->getBody());
    }

    public function testAttachHoneypotBodyNull(): void
    {
        $this->response->setBody(null);

        $this->honeypot->attachHoneypot($this->response);

        $this->assertNull($this->response->getBody());
    }

    public function testAttachHoneypotAndContainer(): void
    {
        $this->response->setBody('<form></form>');
        $this->honeypot->attachHoneypot($this->response);
        $expected = '<form><div style="display:none"><label>Fill This Field</label><input type="text" name="honeypot" value=""></div></form>';
        $this->assertSame($expected, $this->response->getBody());

        $this->config->container = '<div class="hidden">{template}</div>';
        $this->response->setBody('<form></form>');
        $this->honeypot->attachHoneypot($this->response);
        $expected = '<form><div class="hidden"><label>Fill This Field</label><input type="text" name="honeypot" value=""></div></form>';
        $this->assertSame($expected, $this->response->getBody());
    }

    public function testAttachHoneypotAndContainerWithCSP(): void
    {
        $this->resetServices();

        $config             = new App();
        $config->CSPEnabled = true;
        Factories::injectMock('config', 'App', $config);
        $this->response = service('response', $config, false);

        $this->config   = new HoneypotConfig();
        $this->honeypot = new Honeypot($this->config);

        $this->response->setBody('<head></head><body><form></form></body>');
        $this->honeypot->attachHoneypot($this->response);

        $regex = '!<head><style nonce="[0-9a-f]+">#hpc { display:none }</style></head><body><form><div style="display:none" id="hpc"><label>Fill This Field</label><input type="text" name="honeypot" value=""></div></form></body>!u';
        $this->assertMatchesRegularExpression($regex, $this->response->getBody());
    }

    public function testNotAttachHoneypotWithCSP(): void
    {
        $this->resetServices();

        $config             = new App();
        $config->CSPEnabled = true;
        Factories::injectMock('config', 'App', $config);
        $this->response = service('response', $config, false);

        $this->config   = new HoneypotConfig();
        $this->honeypot = new Honeypot($this->config);

        $this->response->setBody('<head></head><body></body>');
        $this->honeypot->attachHoneypot($this->response);

        $this->assertSame('<head></head><body></body>', $this->response->getBody());
    }

    public function testHasntContent(): void
    {
        unset($_POST[$this->config->name]);
        $this->request = service('request');

        $this->assertFalse($this->honeypot->hasContent($this->request));
    }

    public function testHasContent(): void
    {
        $this->assertTrue($this->honeypot->hasContent($this->request));
    }

    public function testConfigTemplate(): void
    {
        $this->config->template = '';
        $this->expectException(HoneypotException::class);
        $this->honeypot = new Honeypot($this->config);
    }

    public function testConfigName(): void
    {
        $this->config->name = '';
        $this->expectException(HoneypotException::class);
        $this->honeypot = new Honeypot($this->config);
    }

    public function testHoneypotFilterBefore(): void
    {
        $config = [
            'aliases' => ['trap' => \CodeIgniter\Filters\Honeypot::class],
            'globals' => [
                'before' => ['trap'],
                'after'  => [],
            ],
        ];

        $filters = new Filters((object) $config, $this->request, $this->response);
        $uri     = 'admin/foo/bar';

        $this->expectException(HoneypotException::class);
        $filters->run($uri, 'before');
    }

    public function testHoneypotFilterAfter(): void
    {
        $config = [
            'aliases' => ['trap' => \CodeIgniter\Filters\Honeypot::class],
            'globals' => [
                'before' => [],
                'after'  => ['trap'],
            ],
        ];

        $filters = new Filters((object) $config, $this->request, $this->response);
        $uri     = 'admin/foo/bar';

        $this->response->setBody('<form></form>');
        $this->response = $filters->run($uri, 'after');
        $this->assertStringContainsString($this->config->name, (string) $this->response->getBody());
    }

    public function testEmptyConfigContainer(): void
    {
        $config            = new HoneypotConfig();
        $config->container = '';
        $honeypot          = new Honeypot($config);

        $this->assertSame(
            '<div style="display:none">{template}</div>',
            $this->getPrivateProperty($honeypot, 'config')->container,
        );
    }

    public function testNoTemplateConfigContainer(): void
    {
        $config            = new HoneypotConfig();
        $config->container = '<div></div>';
        $honeypot          = new Honeypot($config);

        $this->assertSame(
            '<div style="display:none">{template}</div>',
            $this->getPrivateProperty($honeypot, 'config')->container,
        );
    }
}
