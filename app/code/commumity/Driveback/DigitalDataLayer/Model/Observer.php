<?php

class Driveback_DigitalDataLayer_Model_Observer
{
    /**
     * Observes controller_action_layout_render_before
     * @param Varien_Event_Observer $observer
     */
    public function processListingData(Varien_Event_Observer $observer)
    {
        /** @var Driveback_DigitalDataLayer_Helper_Data $helper */
        $helper = Mage::helper('driveback_ddl');

        $layout = Mage::app()->getLayout();

        /** @var Mage_Catalog_Block_Product_List $block */
        $block = $layout->getBlock('product_list');
        if (!$block instanceof Mage_Catalog_Block_Product_List) {
            $block = $layout->getBlock('search_result_list');
            if (!$block instanceof Mage_Catalog_Block_Product_List) {
                return;
            }
        }

        $ddl = $helper->getDdl();
        $listing = $ddl->getListing();

        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = $block->getLoadedProductCollection();

        $listing['resultCount'] = $collection->getSize();
        $listing['items'] = array();
        foreach($collection as $product) {
            $listing['items'][] = $ddl->getProductData($product);
        }

        $toolbar = $block->getToolbarBlock();
        $listing['sortBy'] = $toolbar->getCurrentOrder() . '_' . $toolbar->getCurrentDirection();
        $listing['layout'] = $toolbar->getCurrentMode();

        $ddl->setListing($listing);
    }
}