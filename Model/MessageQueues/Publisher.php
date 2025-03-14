<?php

namespace Elgentos\LargeConfigProducts\Model\MessageQueues;

use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResourceModel;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Module\Manager as ModuleManager;

class Publisher
{
    /**
     * @param PublisherInterface        $publisher
     * @param ConfigurableResourceModel $configurableResourceModel
     * @param ModuleManager             $moduleManager
     * @param ProductFactory            $productFactory
     */
    public function __construct(
        private PublisherInterface $publisher,
        private ConfigurableResourceModel $configurableResourceModel,
        private ModuleManager $moduleManager,
        private ProductFactory $productFactory
    ) {
    }

    public function notify(array $productIds): void
    {
        foreach ($productIds as $productId) {
            $product = $this->productFactory->create()->load($productId);
            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                $this->publisher->publish('elgentos.magento.lcp.product.prewarm', $productId);
            } elseif ($product->getTypeId() === 'simple') {
                $parentIds = $this->configurableResourceModel->getParentIdsByChild($productId);
                foreach ($parentIds as $parentId) {
                    $this->publisher->publish('elgentos.magento.lcp.product.prewarm', $parentId);
                }
            }
        }
    }
}
