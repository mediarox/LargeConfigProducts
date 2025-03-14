<?php

namespace Elgentos\LargeConfigProducts\Controller\Fetch;

use CredisException;
use Elgentos\LargeConfigProducts\Cache\CredisClientFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ProductTypeConfigurable;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class ProductOptions extends Action
{
    /**
     * ProductOptions constructor.
     *
     * @param  Context $context
     * @param  ProductRepositoryInterface $productRepository
     * @param  CredisClientFactory $credisClientFactory
     * @param  StoreManagerInterface $storeManager
     * @param  CustomerSession $customerSession
     * @param  ScopeConfigInterface $scopeConfig
     * @internal param Product $catalogProduct
     */
    public function __construct(
        protected Context $context,
        protected ProductRepositoryInterface $productRepository,
        protected CredisClientFactory $credisClientFactory,
        private StoreManagerInterface $storeManager,
        private CustomerSession $customerSession,
        private ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $productId = $this->_request->getParam('productId');

        echo $this->getProductOptionInfo($productId);
        exit;
    }

    /**
     * @param $productId
     * @return bool|mixed|string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws CredisException
     */
    public function getProductOptionInfo($productId)
    {
        $cRedis = $this->credisClientFactory->create();
        $storeId = $this->storeManager->getStore()
            ->getId();
        $customerGroupId = 0;

        $enableCustomerGroupId = $this->scopeConfig->getValue(
            'elgentos_largeconfigproducts/options/enable_customer_groups'
        );
        if ($enableCustomerGroupId) {
            $customerGroupId = $this->customerSession->getCustomerGroupId();
        }

        $cacheKey = 'LCP_PRODUCT_INFO_' . $storeId . '_' . $productId . '_' . $customerGroupId;

        if ($cRedis->exists($cacheKey)) {
            return $cRedis->get($cacheKey);
        }

        $product = $this->productRepository->getById($productId);
        if ($product->getId()) {
            $productOptionInfo = $this->getJsonConfig($product);
            $cRedis->set($cacheKey, $productOptionInfo);

            return $productOptionInfo;
        }

        return false;
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
        $block = $this->_view->getLayout()
            ->createBlock(ProductTypeConfigurable::class)
            ->setData('product', $currentProduct);

        return $block->getJsonConfig();
    }
}
