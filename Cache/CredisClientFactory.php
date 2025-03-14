<?php

declare(strict_types=1);

namespace Elgentos\LargeConfigProducts\Cache;

use CredisException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;

class CredisClientFactory
{
    public function __construct(
        private DeploymentConfig $deploymentConfig,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @throws FileSystemException
     * @throws CredisException
     * @throws RuntimeException
     */
    public function create(): \Credis_Client
    {
        $cacheSetting = $this->deploymentConfig->get('cache');

        $timeout = null;
        $persistent = '';
        if (isset($cacheSetting['frontend']['elgentos_largeconfigproducts']['backend_options'])) {
            $backendOptions = $cacheSetting['frontend']['elgentos_largeconfigproducts']['backend_options'];

            $server = $backendOptions['server'];
            $database = $backendOptions['database'];
            $port = $backendOptions['port'];
        } else {
            $server = $this->scopeConfig->getValue(
                'elgentos_largeconfigproducts/prewarm/redis_host'
            ) ?? 'localhost';

            $port = $this->scopeConfig->getValue(
                'elgentos_largeconfigproducts/prewarm/redis_port'
            ) ?? 6379;
            $database = $this->scopeConfig->getValue(
                'elgentos_largeconfigproducts/prewarm/redis_db_index'
            ) ?? 4;
        }

        return new \Credis_Client(
            $server,
            $port,
            $timeout,
            $persistent,
            $database
        );
    }
}
