<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 21-2-18
 * Time: 13:53.
 */

namespace Elgentos\LargeConfigProducts\Plugin\Model;

use Elgentos\LargeConfigProducts\Model\StoreIdStatic;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class AttributeOptionProviderPlugin.
 */
class AttributeOptionProviderPlugin
{
    public function __construct(
        protected StoreManagerInterface $storeManager,
        public StoreIdStatic $storeIdValueObject
    ) {
        $this->storeManager = $storeManager;
        $this->storeIdValueObject = $storeIdValueObject;
    }

    public function beforeGetAttributeOptions(
        \Magento\ConfigurableProduct\Model\AttributeOptionProvider $subject,
        \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $superAttribute,
        $productId
    ) {
        /**
         * The currentStoreId that is being set in the emulation in PrewarmerCommand is somehow lost in the call stack.
         * This plugin uses the store ID found in the static store id value object to re-set the current store so the
         * translated attribute option labels are retrieved correctly.
         */
        $prewarmCurrentStore = $this->storeIdValueObject->getStoreId();
        if ($prewarmCurrentStore) {
            $this->storeManager->setCurrentStore($prewarmCurrentStore);
        }
    }
}
