<?php
declare(strict_types=1);

namespace Elgentos\BaseUrlPath\Plugin\Frontend\Magento\Framework\App\Request;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\PathInfo as PathInfoRequest;
use Magento\Store\Model\ScopeInterface;

class PathInfo
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Http
     */
    private $httpRequest;
    /**
     * @var string
     */
    private $baseUrl = '';

    /**
     * PathInfo constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $httpRequest
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $httpRequest
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->httpRequest = $httpRequest;

        $this->resolveBaseUrlFromStoreConfig();
    }


    public function beforeGetPathInfo(
        PathInfoRequest $subject,
        string $requestUri,
        string $baseUrl
    ) {
        if ($baseUrl === '') {
            $baseUrl = $this->baseUrl;
        }

        return [
            $requestUri,
            $baseUrl
        ];
    }

    /**
     * Allow to setup a deeper path in Magento backend to resolve the frontend
     *
     * @return string
     */
    public function resolveBaseUrlFromStoreConfig(): void
    {
        // Because config isn't loaded yet, we need to resolve this with the Magento run code
        $mageRunCode = $this->httpRequest->getServerValue('MAGE_RUN_CODE', false);
        if (! $mageRunCode) {
            return;
        }

        $unsecureBaseUrl = $this->scopeConfig->getValue('web/unsecure/base_url', ScopeInterface::SCOPE_STORE, $mageRunCode);
        $unsecurePath = parse_url($unsecureBaseUrl, PHP_URL_PATH);

        $secureBaseUrl = $this->scopeConfig->getValue('web/secure/base_url', ScopeInterface::SCOPE_STORE, $mageRunCode);
        $securePath = parse_url($secureBaseUrl, PHP_URL_PATH);

        // Only allow if set for both, secure and unsecure to avoid conflicts
        if ($unsecurePath !== $securePath) {
            return;
        }

        if (! $unsecurePath) {
            return;
        }

        if ($unsecurePath === '/') {
            return;
        }

        $this->baseUrl = '/' . trim($unsecurePath, '/');
    }
}

