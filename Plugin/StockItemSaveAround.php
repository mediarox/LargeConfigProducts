<?php

namespace Elgentos\LargeConfigProducts\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;

class StockItemSaveAround
{
    public function __construct(private IndexerRegistry $indexerRegistry)
    {
    }

    public function aroundSave(
        \Magento\CatalogInventory\Model\ResourceModel\Stock\Item $stockItemModel,
        \Closure $proceed,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
    ) {
        $stockItemModel->addCommitCallback(
            function () use ($stockItem) {
                $indexer = $this->indexerRegistry->get('elgentos_lcp_prewarm');
                if (!$indexer->isScheduled()) {
                    $indexer->reindexRow($stockItem->getProductId());
                }
            }
        );

        return $proceed($stockItem);
    }
}
