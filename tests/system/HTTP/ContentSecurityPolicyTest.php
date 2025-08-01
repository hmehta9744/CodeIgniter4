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

namespace CodeIgniter\HTTP;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\TestResponse;
use Config\App;
use Config\ContentSecurityPolicy as CSPConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * Test the CSP policy directive creation.
 *
 * See https://apimirror.com/http/headers/content-security-policy
 * See https://cspvalidator.org/
 *
 * @internal
 */
#[Group('SeparateProcess')]
final class ContentSecurityPolicyTest extends CIUnitTestCase
{
    private ?Response $response         = null;
    private ?ContentSecurityPolicy $csp = null;

    #[WithoutErrorHandler]
    protected function setUp(): void
    {
        parent::setUp();
    }

    // Having this method as setUp() doesn't work - can't find Config\App !?
    protected function prepare(bool $CSPEnabled = true): void
    {
        $this->resetServices();

        $config             = config('App');
        $config->CSPEnabled = $CSPEnabled;
        $this->response     = new Response($config);
        $this->response->pretend(false);
        $this->csp = $this->response->getCSP();
    }

    protected function work(string $parm = 'Hello'): bool
    {
        $body = $parm;
        $this->response->setBody($body);
        $this->response->setCookie('foo', 'bar');

        ob_start();
        $this->response->send();
        $buffer = ob_clean();
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        return $buffer;
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testExistence(): void
    {
        $this->prepare();
        $this->work();

        $this->assertHeaderEmitted('Content-Security-Policy:');
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testReportOnly(): void
    {
        $this->prepare();
        $this->csp->reportOnly(false);
        $this->work();

        $this->assertHeaderEmitted('Content-Security-Policy:');
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testDefaults(): void
    {
        $this->prepare();

        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("base-uri 'self';", (string) $result);
        $this->assertStringContainsString("connect-src 'self';", (string) $result);
        $this->assertStringContainsString("default-src 'self';", (string) $result);
        $this->assertStringContainsString("img-src 'self';", (string) $result);
        $this->assertStringContainsString("script-src 'self';", (string) $result);
        $this->assertStringContainsString("style-src 'self';", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testChildSrc(): void
    {
        $this->prepare();
        $this->csp->addChildSrc('evil.com', true);
        $this->csp->addChildSrc('good.com', false);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('child-src evil.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("child-src 'self' good.com;", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testConnectSrc(): void
    {
        $this->prepare();
        $this->csp->reportOnly(true);
        $this->csp->addConnectSrc('iffy.com');
        $this->csp->addConnectSrc('maybe.com');
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("connect-src 'self' iffy.com maybe.com;", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testFontSrc(): void
    {
        $this->prepare();
        $this->csp->reportOnly(true);
        $this->csp->addFontSrc('iffy.com');
        $this->csp->addFontSrc('fontsrus.com', false);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('font-src iffy.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('font-src fontsrus.com;', (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testFormAction(): void
    {
        $this->prepare();
        $this->csp->reportOnly(true);
        $this->csp->addFormAction('surveysrus.com');
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("form-action 'self' surveysrus.com;", (string) $result);

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringNotContainsString("form-action 'self';", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testFrameAncestor(): void
    {
        $this->prepare();
        $this->csp->addFrameAncestor('self');
        $this->csp->addFrameAncestor('them.com', true);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('frame-ancestors them.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'self';", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testFrameSrc(): void
    {
        $this->prepare();
        $this->csp->addFrameSrc('self');
        $this->csp->addFrameSrc('them.com', true);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('frame-src them.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("frame-src 'self';", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testImageSrc(): void
    {
        $this->prepare();
        $this->csp->addImageSrc('cdn.cloudy.com');
        $this->csp->addImageSrc('them.com', true);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('img-src them.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("img-src 'self' cdn.cloudy.com;", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testMediaSrc(): void
    {
        $this->prepare();
        $this->csp->addMediaSrc('self');
        $this->csp->addMediaSrc('them.com', true);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('media-src them.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("media-src 'self';", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testManifestSrc(): void
    {
        $this->prepare();
        $this->csp->addManifestSrc('cdn.cloudy.com');
        $this->csp->addManifestSrc('them.com', true);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('manifest-src them.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('manifest-src cdn.cloudy.com;', (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testPluginType(): void
    {
        $this->prepare();
        $this->csp->addPluginType('self');
        $this->csp->addPluginType('application/x-shockwave-flash', true);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('plugin-types application/x-shockwave-flash;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("plugin-types 'self';", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testPluginArray(): void
    {
        $this->prepare();
        $this->csp->addPluginType('application/x-shockwave-flash');
        $this->csp->addPluginType('application/wacky-hacky');
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('plugin-types application/x-shockwave-flash application/wacky-hacky;', (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testObjectSrc(): void
    {
        $this->prepare();
        $this->csp->addObjectSrc('cdn.cloudy.com');
        $this->csp->addObjectSrc('them.com', true);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('object-src them.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("object-src 'self' cdn.cloudy.com;", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testScriptSrc(): void
    {
        $this->prepare();
        $this->csp->addScriptSrc('cdn.cloudy.com');
        $this->csp->addScriptSrc('them.com', true);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('script-src them.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' cdn.cloudy.com;", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testStyleSrc(): void
    {
        $this->prepare();
        $this->csp->addStyleSrc('cdn.cloudy.com');
        $this->csp->addStyleSrc('them.com', true);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('style-src them.com;', (string) $result);
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("style-src 'self' cdn.cloudy.com;", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testBaseURIDefault(): void
    {
        $this->prepare();
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("base-uri 'self';", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testBaseURI(): void
    {
        $this->prepare();
        $this->csp->addBaseURI('example.com');
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('base-uri example.com;', (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testBaseURIRich(): void
    {
        $this->prepare();
        $this->csp->addBaseURI(['self', 'example.com']);
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("base-uri 'self' example.com;", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testDefaultSrc(): void
    {
        $this->prepare();
        $this->csp->reportOnly(false);
        $this->csp->setDefaultSrc('maybe.com');
        $this->csp->setDefaultSrc('iffy.com');
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('default-src iffy.com;', (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testReportURI(): void
    {
        $this->prepare();
        $this->csp->reportOnly(false);
        $this->csp->setReportURI('http://example.com/csptracker');
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('report-uri http://example.com/csptracker;', (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testRemoveReportURI(): void
    {
        $this->prepare();
        $this->csp->reportOnly(false);
        $this->csp->setReportURI('');
        $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringNotContainsString('report-uri ', (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testSandboxFlags(): void
    {
        $this->prepare();
        $this->csp->reportOnly(false);
        $this->csp->addSandbox(['allow-popups', 'allow-top-navigation']);
        //      $this->csp->addSandbox('allow-popups');
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('sandbox allow-popups allow-top-navigation;', (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testUpgradeInsecureRequests(): void
    {
        $this->prepare();
        $this->csp->upgradeInsecureRequests();
        $result = $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('upgrade-insecure-requests;', (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testBodyEmpty(): void
    {
        $this->prepare();
        $body = '';
        $this->response->setBody($body);
        $this->csp->finalize($this->response);
        $this->assertSame($body, $this->response->getBody());
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testBodyScriptNonce(): void
    {
        $this->prepare();
        $body = 'Blah blah {csp-script-nonce} blah blah';
        $this->response->setBody($body);
        $this->csp->addScriptSrc('cdn.cloudy.com');

        $result     = $this->work($body);
        $nonceStyle = array_filter(
            $this->getPrivateProperty($this->csp, 'styleSrc'),
            static fn ($value): bool => str_starts_with($value, 'nonce-'),
        );

        $this->assertStringContainsString('nonce=', (string) $this->response->getBody());
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('nonce-', (string) $result);
        $this->assertSame([], $nonceStyle);
    }

    public function testBodyScriptNonceCustomScriptTag(): void
    {
        $config                 = new CSPConfig();
        $config->scriptNonceTag = '{custom-script-nonce-tag}';
        $csp                    = new ContentSecurityPolicy($config);

        $response = new Response(new App());
        $response->pretend(true);
        $body = 'Blah blah {custom-script-nonce-tag} blah blah';
        $response->setBody($body);

        $csp->finalize($response);

        $this->assertStringContainsString('nonce=', (string) $response->getBody());
    }

    public function testBodyScriptNonceDisableAutoNonce(): void
    {
        $config            = new CSPConfig();
        $config->autoNonce = false;
        $csp               = new ContentSecurityPolicy($config);

        $response = new Response(new App());
        $response->pretend(true);
        $body = 'Blah blah {csp-script-nonce} blah blah';
        $response->setBody($body);

        $csp->finalize($response);

        $this->assertStringContainsString('{csp-script-nonce}', (string) $response->getBody());

        $result = new TestResponse($response);
        $result->assertHeader('Content-Security-Policy');
    }

    public function testBodyStyleNonceDisableAutoNonce(): void
    {
        $config            = new CSPConfig();
        $config->autoNonce = false;
        $csp               = new ContentSecurityPolicy($config);

        $response = new Response(new App());
        $response->pretend(true);
        $body = 'Blah blah {csp-style-nonce} blah blah';
        $response->setBody($body);

        $csp->finalize($response);

        $this->assertStringContainsString('{csp-style-nonce}', (string) $response->getBody());

        $result = new TestResponse($response);
        $result->assertHeader('Content-Security-Policy');
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testBodyStyleNonce(): void
    {
        $this->prepare();
        $body = 'Blah blah {csp-style-nonce} blah blah';
        $this->response->setBody($body);
        $this->csp->addStyleSrc('cdn.cloudy.com');

        $result      = $this->work($body);
        $nonceScript = array_filter(
            $this->getPrivateProperty($this->csp, 'scriptSrc'),
            static fn ($value): bool => str_starts_with($value, 'nonce-'),
        );

        $this->assertStringContainsString('nonce=', (string) $this->response->getBody());
        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString('nonce-', (string) $result);
        $this->assertSame([], $nonceScript);
    }

    public function testBodyStyleNonceCustomStyleTag(): void
    {
        $config                = new CSPConfig();
        $config->styleNonceTag = '{custom-style-nonce-tag}';
        $csp                   = new ContentSecurityPolicy($config);

        $response = new Response(new App());
        $response->pretend(true);
        $body = 'Blah blah {custom-style-nonce-tag} blah blah';
        $response->setBody($body);

        $csp->finalize($response);

        $this->assertStringContainsString('nonce=', (string) $response->getBody());
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testHeaderWrongCaseNotFound(): void
    {
        $this->prepare();
        $result = $this->work();

        $result = $this->getHeaderEmitted('content-security-policy');
        $this->assertNull($result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testHeaderIgnoreCase(): void
    {
        $this->prepare();
        $result = $this->work();

        $result = $this->getHeaderEmitted('content-security-policy', true);
        $this->assertStringContainsString("base-uri 'self';", (string) $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testCSPDisabled(): void
    {
        $this->prepare(false);
        $this->work();
        $this->response->getCSP()->addStyleSrc('https://example.com');

        $this->assertHeaderNotEmitted('content-security-policy', true);
    }

    public function testGetScriptNonce(): void
    {
        $this->prepare();

        $nonce = $this->csp->getScriptNonce();

        $this->assertMatchesRegularExpression('/\A[0-9a-z]{24}\z/', $nonce);
    }

    public function testGetStyleNonce(): void
    {
        $this->prepare();

        $nonce = $this->csp->getStyleNonce();

        $this->assertMatchesRegularExpression('/\A[0-9a-z]{24}\z/', $nonce);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testHeaderScriptNonceEmittedOnceGetScriptNonceCalled(): void
    {
        $this->prepare();

        $this->csp->getScriptNonce();
        $this->work();

        $result = $this->getHeaderEmitted('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'nonce-", (string) $result);
    }

    public function testClearDirective(): void
    {
        $this->prepare();

        $this->csp->addStyleSrc('css.example.com');
        $this->csp->clearDirective('style-src');

        $this->csp->setReportURI('http://example.com/csp/reports');
        $this->csp->clearDirective('report-uri');
        $this->csp->finalize($this->response);

        $header = $this->response->getHeaderLine('Content-Security-Policy');

        $this->assertStringNotContainsString('style-src ', $header);
        $this->assertStringNotContainsString('css.example.com', $header);
        $this->assertStringNotContainsString('report-uri', $header);
    }
}
