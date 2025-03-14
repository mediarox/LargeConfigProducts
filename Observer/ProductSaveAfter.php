<?php

namespace Elgentos\LargeConfigProducts\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;

class ProductSaveAfter implements ObserverInterface
{
    public function __construct(private IndexerRegistry $indexerRegistry)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $indexer = $this->indexerRegistry->get('elgentos_lcp_prewarm');
        if (!$indexer->isScheduled()) {
            $indexer->reindexRow(
                $observer->getProduct()
                    ->getId()
            );
        }
    }
}
