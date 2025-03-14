<?php

namespace Elgentos\LargeConfigProducts\Plugin\Product\Action;

use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\Indexer\IndexerRegistry;

class AfterUpdateAttributesPlugin
{
    public function __construct(private IndexerRegistry $indexerRegistry)
    {
    }

    /**
     * @param ProductAction $subject
     * @param ProductAction $action
     * @param $productIds
     * @param $attrData
     * @param $storeId
     *
     * @return ProductAction
     */
    public function afterUpdateAttributes(
        ProductAction $subject,
        ProductAction $action,
        $productIds,
        $attrData,
        $storeId
    ) {
        $indexer = $this->indexerRegistry->get('elgentos_lcp_prewarm');
        if (!$indexer->isScheduled()) {
            $indexer->reindexList($productIds);
        }

        return $action;
    }
}
