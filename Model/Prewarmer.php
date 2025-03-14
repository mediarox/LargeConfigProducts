<?php

namespace Elgentos\LargeConfigProducts\Model;

use Elgentos\LargeConfigProducts\Cache\CredisClientFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ProductTypeConfigurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\Type\Collection as CacheTypeCollection;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Prewarmer.
 */
class Prewarmer
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected StoreManagerInterface $storeManager,
        protected CredisClientFactory $credisClientFactory,
        protected StoreIdStatic $storeIdValueObject,
        private CacheManager $cacheManager,
        private Emulation $emulation,
        private BlockFactory $blockFactory,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
    }

    public function prewarm($productIdsToWarm, $storeCodesToWarm, $force)
    {
        $cRedisClient = $this->credisClientFactory->create();

        $output = [];

        if (\is_array($productIdsToWarm) && \count($productIdsToWarm) > 0) {
            // Add to search criteria builder
            $this->searchCriteriaBuilder->addFilter(
                'entity_id',
                $productIdsToWarm,
                'in'
            );
        }
        $this->searchCriteriaBuilder->addFilter('type_id', 'configurable');
        $searchCriteria = $this->searchCriteriaBuilder->create();

        /** @var \Magento\Store\Api\Data\StoreInterface[] $stores */
        $stores = $this->storeManager->getStores();

        /** Use the customer-group for guests. It is currently not possible to prewarm the production options with
         * catalog price rules baesd on the customer group as condition.
         * Magento uses the Customer Session to do calculate the CatalogRulePrice and this seems hard to simulate from the CLI.
         */
        $customerGroupId = 0;

        /*
         * Remove stores from array that are not in storeCodesToWarm (if set)
         */
        foreach ($stores as $key => $store) {
            if (
                $storeCodesToWarm && !\in_array(
                    $store->getCode(),
                    $storeCodesToWarm
                )
            ) {
                unset($stores[$key]);
            }
        }

        // Clean colllections cache type, otherwise Magento will fetch the old prices
        $this->cacheManager->clean([CacheTypeCollection::TYPE_IDENTIFIER]);

        $i = 1;
        foreach ($stores as $store) {
            /*
             * Use store emulation to let Magento fetch the correct translations for in the JSON object
             * But stop any running store environment emulation first so we can run it
             */
            $this->emulation->stopEnvironmentEmulation();
            $this->emulation->startEnvironmentEmulation(
                $store->getId(),
                Area::AREA_FRONTEND,
                true
            );

            $this->storeManager->setCurrentStore($store->getId());
            $this->storeIdValueObject->setStoreId($store->getId());

            $products = $this->productRepository->getList($searchCriteria)
                ->getItems();
            foreach ($products as $product) {
                $cacheKey = 'LCP_PRODUCT_INFO_' . $store->getId(
                ) . '_' . $product->getId() . '_' . $customerGroupId;

                if ($force || !$cRedisClient->exists($cacheKey)) {
                    $output[] = 'Prewarming ' . $product->getSku(
                    ) . ' for store ' . $store->getCode(
                    ) . ' (' . $i . '/' . count($stores) . ')';
                    $productOptionInfo = $this->getJsonConfig($product);
                    $cRedisClient->set($cacheKey, $productOptionInfo);
                } else {
                    $output[] = $product->getSku(
                    ) . ' is already prewarmed for store ' . $store->getCode(
                    ) . ' (' . $i . '/' . count($stores) . ')';
                }
                $i++;
            }
            $this->emulation->stopEnvironmentEmulation();
        }

        return implode(PHP_EOL, $output);
    }

    /**
     * @param $currentProduct
     * @return mixed
     * See original method at
     * Magento\ConfigurableProduct\Block\Product\View\Type\Configurable::getJsonConfig
     */
    public function getJsonConfig($currentProduct)
    {
        /** @var ProductTypeConfigurable $block */
        $block = $this->blockFactory->createBlock(
            ProductTypeConfigurable::class
        )
            ->setData('product', $currentProduct);

        return $block->getJsonConfig();
    }
}
