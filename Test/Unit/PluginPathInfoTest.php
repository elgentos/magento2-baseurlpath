<?php

namespace Elgentos\BaseUrlPath\Plugin\Frontend\Magento\Framework\App\Request;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class PluginPathInfoTest extends TestCase
{


    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $httpRequestMock;
    /**
     * @var \Magento\Framework\App\Request\PathInfo|\PHPUnit\Framework\MockObject\MockObject
     */
    private $pathInfoRequest;


    protected function setUp(): void
    {
        $scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $httpRequestMock = $this->getMockBuilder(Http::class)
                ->setMethods(['getServerValue'])
                ->disableOriginalConstructor()
                ->getMock();

        $pathInfoRequest = $this->createMock(\Magento\Framework\App\Request\PathInfo::class);


        $this->scopeConfigMock = $scopeConfigMock;
        $this->httpRequestMock = $httpRequestMock;
        $this->pathInfoRequest = $pathInfoRequest;
    }

    public function testShouldNotInitializeBaseUrlIfMageRunCodeIsNotSet()
    {
        $scopeConfigMock = $this->scopeConfigMock;
        $httpRequestMock = $this->httpRequestMock;
        $pathInfoRequest = $this->pathInfoRequest;

        $httpRequestMock->expects($this->once())
                ->method('getServerValue')
                ->with('MAGE_RUN_CODE', false)
                ->willReturn(false);

        $scopeConfigMock->expects($this->never())
                ->method('getValue');

        $pathInfo = new PathInfo(
            $scopeConfigMock,
            $httpRequestMock
        );

        [, $baseUrl] = $pathInfo->beforeGetPathInfo($pathInfoRequest, '', '');
        $this->assertSame('', $baseUrl);
    }

    public function testShouldNotSetBaseUrlIfUnsecureAndSecureBaseUrlPathAreNotTheSame()
    {
        $scopeConfigMock = $this->scopeConfigMock;
        $httpRequestMock = $this->httpRequestMock;
        $pathInfoRequest = $this->pathInfoRequest;

        $httpRequestMock->expects($this->once())
                ->method('getServerValue')
                ->willReturn('default');

        $scopeConfigMock->expects($this->exactly(2))
                ->method('getValue')
                ->withConsecutive(['web/unsecure/base_url', ScopeInterface::SCOPE_STORE, 'default'], ['web/secure/base_url', ScopeInterface::SCOPE_STORE, 'default'])
                ->willReturn('http://localhost.local.host/one', 'http://localhost.local.host/two');

        $pathInfo = new PathInfo(
            $scopeConfigMock,
            $httpRequestMock
        );

        [, $baseUrl] = $pathInfo->beforeGetPathInfo($pathInfoRequest, '', '');
        $this->assertSame('', $baseUrl);
    }

    public function testShouldNotSetBaseUrlIfUnsecureBaseUrlPathIsEmpty()
    {
        $scopeConfigMock = $this->scopeConfigMock;
        $httpRequestMock = $this->httpRequestMock;
        $pathInfoRequest = $this->pathInfoRequest;

        $httpRequestMock->expects($this->once())
                ->method('getServerValue')
                ->willReturn('default');

        $scopeConfigMock->expects($this->exactly(2))
                ->method('getValue')
                ->withConsecutive(['web/unsecure/base_url', ScopeInterface::SCOPE_STORE, 'default'], ['web/secure/base_url', ScopeInterface::SCOPE_STORE, 'default'])
                ->willReturn('', '');

        $pathInfo = new PathInfo(
            $scopeConfigMock,
            $httpRequestMock
        );

        [, $baseUrl] = $pathInfo->beforeGetPathInfo($pathInfoRequest, '', '');
        $this->assertSame('', $baseUrl);
    }

    public function testShouldSetBaseUrlPathIfDomainsDifferAndPathIsSame()
    {
        $scopeConfigMock = $this->scopeConfigMock;
        $httpRequestMock = $this->httpRequestMock;
        $pathInfoRequest = $this->pathInfoRequest;

        $httpRequestMock->expects($this->once())
                ->method('getServerValue')
                ->willReturn('default');

        $scopeConfigMock->expects($this->exactly(2))
                ->method('getValue')
                ->withConsecutive(['web/unsecure/base_url', ScopeInterface::SCOPE_STORE, 'default'], ['web/secure/base_url', ScopeInterface::SCOPE_STORE, 'default'])
                ->willReturn('http://localhost.local.host/one', 'http://localhost.remote.host/one');

        $pathInfo = new PathInfo(
            $scopeConfigMock,
            $httpRequestMock
        );

        [, $baseUrl] = $pathInfo->beforeGetPathInfo($pathInfoRequest, '', '');
        $this->assertSame('/one', $baseUrl);
    }

    public function testShouldTrimBaseUrlPathFromFinalSlash()
    {
        $scopeConfigMock = $this->scopeConfigMock;
        $httpRequestMock = $this->httpRequestMock;
        $pathInfoRequest = $this->pathInfoRequest;

        $httpRequestMock->expects($this->once())
                ->method('getServerValue')
                ->willReturn('default');

        $scopeConfigMock->expects($this->exactly(2))
                ->method('getValue')
                ->withConsecutive(['web/unsecure/base_url', ScopeInterface::SCOPE_STORE, 'default'], ['web/secure/base_url', ScopeInterface::SCOPE_STORE, 'default'])
                ->willReturn('http://localhost.local.host/one/', 'http://localhost.remote.host/one/');

        $pathInfo = new PathInfo(
            $scopeConfigMock,
            $httpRequestMock
        );

        [, $baseUrl] = $pathInfo->beforeGetPathInfo($pathInfoRequest, '', '');
        $this->assertSame('/one', $baseUrl);
    }

    public function testShouldNeverReturnASlash()
    {
        $scopeConfigMock = $this->scopeConfigMock;
        $httpRequestMock = $this->httpRequestMock;
        $pathInfoRequest = $this->pathInfoRequest;

        $httpRequestMock->expects($this->once())
            ->method('getServerValue')
            ->willReturn('default');

        $scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(['web/unsecure/base_url', ScopeInterface::SCOPE_STORE, 'default'], ['web/secure/base_url', ScopeInterface::SCOPE_STORE, 'default'])
            ->willReturn('http://localhost.local.host/', 'http://localhost.remote.host/');

        $pathInfo = new PathInfo(
            $scopeConfigMock,
            $httpRequestMock
        );

        [, $baseUrl] = $pathInfo->beforeGetPathInfo($pathInfoRequest, '', '');
        $this->assertSame('', $baseUrl);
    }

    public function testShouldNotChangeBaseUrlIfNotEmptyAndShouldLeaveRequestUriUnchanged()
    {
        $scopeConfigMock = $this->scopeConfigMock;
        $httpRequestMock = $this->httpRequestMock;
        $pathInfoRequest = $this->pathInfoRequest;

        $httpRequestMock->method('getServerValue')
            ->willReturn('default');

        $scopeConfigMock->method('getValue')
            ->withConsecutive(['web/unsecure/base_url', ScopeInterface::SCOPE_STORE, 'default'], ['web/secure/base_url', ScopeInterface::SCOPE_STORE, 'default'])
            ->willReturn('http://localhost.local.host/', 'http://localhost.remote.host/');

        $pathInfo = new PathInfo(
            $scopeConfigMock,
            $httpRequestMock
        );

        [$requestUri, $baseUrl] = $pathInfo->beforeGetPathInfo($pathInfoRequest, '', '/magento');
        $this->assertSame('', $requestUri);
        $this->assertSame('/magento', $baseUrl);

        [$requestUri, $baseUrl] = $pathInfo->beforeGetPathInfo($pathInfoRequest, '/request', '/test');
        $this->assertSame('/request', $requestUri);
        $this->assertSame('/test', $baseUrl);

        [$requestUri, $baseUrl] = $pathInfo->beforeGetPathInfo($pathInfoRequest, '/request', '');
        $this->assertSame('/request', $requestUri);
        $this->assertSame('', $baseUrl);
    }

}
