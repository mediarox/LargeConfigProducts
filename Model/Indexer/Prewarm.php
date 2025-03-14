<?php

namespace Elgentos\LargeConfigProducts\Model\Indexer;

use Elgentos\LargeConfigProducts\Model\MessageQueues\Publisher;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class Prewarm implements IndexerActionInterface, MviewActionInterface
{
    public function __construct(
        private StoreManagerInterface $storeManager,
        private ManagerInterface $messageManager,
        private ConsoleOutput $output,
        private State $state,
        private Publisher $publisher,
        public ProductCollectionFactory $productCollectionFactory
    ) {
    }

    public function execute($productIds)
    {
        try {
            $this->state->setAreaCode(Area::AREA_GLOBAL);
        } catch (\Exception $e) {
        }

        if (!\is_array($productIds) || empty($productIds)) {
            $collection = $this->productCollectionFactory->create();
            $productIds = $collection->addAttributeToFilter('type_id', 'configurable')->getAllIds();
        }

        foreach ($productIds as $productId) {
            $this->publisher->notify([$productId]);
        }
    }

    public function executeFull()
    {
        $this->execute([]);
    }

    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}
