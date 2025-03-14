<?php

namespace Elgentos\LargeConfigProducts\Model\MessageQueues;

use Elgentos\LargeConfigProducts\Model\Prewarmer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Consumer
{
    private const PREWARM_PROCESS_TIMEOUT = 300;

    public function __construct(
        protected LoggerInterface $logger,
        private Prewarmer $prewarmer,
        private DirectoryList $directoryList,
    ) {
    }

    /**
     * Process message queue.
     *
     * @param  string $productId
     * @return void
     */
    public function processMessage(string $productId)
    {
        echo sprintf('Processing %s..', $productId) . PHP_EOL;

        $absolutePath = $this->directoryList->getRoot();

        if (!$absolutePath) {
            $this->logger->info(
                '[Elgentos_LargeConfigProducts] Could not prewarm through message queue; no absolute path is set in LCP configuration.'
            );

            return;
        }

        // Strip trailing slash
        if (substr($absolutePath, -1) === '/') {
            $absolutePath = substr($absolutePath, 0, -1);
        }

        try {
            $process = new Process(
                sprintf(
                    'php %s/bin/magento lcp:prewarm -p %s --force=true',
                    $absolutePath,
                    $productId
                ),
                null,
                null,
                null,
                self::PREWARM_PROCESS_TIMEOUT
            );
            $process->run();
            echo $process->getOutput();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
