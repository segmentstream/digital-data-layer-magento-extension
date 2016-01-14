<?php

/**
 * Class Driveback_DigitalDataLayer_Model_Ddl
 *
 * @method Driveback_DigitalDataLayer_Model_Ddl setVersion() setVersion(mixed $version)
 * @method Driveback_DigitalDataLayer_Model_Ddl setEvents() setEvents(array $events)
 * @method Driveback_DigitalDataLayer_Model_Ddl setProduct() setProduct($data)
 * @method Driveback_DigitalDataLayer_Model_Ddl setPage() setPage($data)
 * @method Driveback_DigitalDataLayer_Model_Ddl setUser() setUser($data)
 * @method Driveback_DigitalDataLayer_Model_Ddl setListing() setListing($data)
 * @method Driveback_DigitalDataLayer_Model_Ddl setCart() setCart($data)
 * @method Driveback_DigitalDataLayer_Model_Ddl setTransaction() setTransaction($data)
 * @method Driveback_DigitalDataLayer_Model_Ddl setMagentoVersion() setMagentoVersion(mixed $version)
 */

class Driveback_DigitalDataLayer_Model_Ddl extends Varien_Object
{

    /**
     * This is the DDL specification Version
     * @var string
     */
    protected $_version = "1.0.0";

    protected function _construct()
    {
        $this
            ->setVersion($this->_version)
            ->setEvents(array())
            ->_initUser()
            ->_initPage();

        if ($this->helper()->isProductPage()) {
            $this->_initProduct();
        }

        if ($this->helper()->isCategoryPage() || $this->helper()->isSearchPage()) {
            $this->_initListing();
        }

        if (!$this->helper()->isConfirmationPage()) {
            $this->_initCart();
        }

        if ($this->helper()->isConfirmationPage()) {
            $this->_initTransaction();
        }
    }

    /**
     * @return string json encoded DDL data
     */
    public function getDigitalData()
    {
        $data = $this->toArray(array('version', 'page', 'user', 'product', 'cart', 'listing', 'transaction', 'events'));

        foreach ($data as $key => $value) {
            if ($key !== 'events' && empty($data[$key])) {
                unset($data[$key]);
            }
        }

        $transport = new Varien_Object($data);
        Mage::dispatchEvent('driveback_ddl_before_to_json', array('ddl' => $transport));

        return Zend_Json::encode($transport->getData());
    }

    /**
     * @return Driveback_DigitalDataLayer_Helper_Data
     */
    protected function helper() {
        return Mage::helper('driveback_ddl');
    }

    protected function _initPage()
    {
        $breadcrumb = array();
        foreach (Mage::helper('catalog')->getBreadcrumbPath() as $category) {
            $breadcrumb[] = $category['label'];
        }

        if ($this->helper()->isHomePage()) {
            $type = 'home';
        } elseif ($this->helper()->isContentPage()) {
            $type = 'content';
        } elseif ($this->helper()->isCategoryPage()) {
            $type = 'category';
        } elseif ($this->helper()->isSearchPage()) {
            $type = 'search';
        } elseif ($this->helper()->isProductPage()) {
            $type = 'product';
        } elseif ($this->helper()->isCartPage()) {
            $type = 'cart';
        } elseif ($this->helper()->isCheckoutPage()) {
            $type = 'checkout';
        } elseif ($this->helper()->isConfirmationPage()) {
            $type = 'confirmation';
        } else {
            $type = trim(Mage::app()->getRequest()->getRequestUri(), '/');
        }

        $page = array(
            'type' => $type,
            'breadcrumb' => $breadcrumb,
        );

        if ($type === 'category') {
            $page = array_merge($page, array(
                'categoryId' => Mage::registry('current_category')->getId()
            ));
        }

        $this->setPage($page);

        return $this;
    }

    protected function _initUser()
    {
        /** @var Mage_Customer_Model_Customer $user */
        $user = Mage::helper('customer')->getCustomer();

        if ($this->helper()->isConfirmationPage()) {
            if ($orderId = Mage::getSingleton('checkout/session')->getLastOrderId()) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $email = $order->getCustomerEmail();
                $firstName = $order->getCustomerFirstname();
                $lastName = $order->getCustomerLastname();
            }
        } else {
            $email = $user->getEmail();
            $firstName = $user->getFirstname();
            $lastName = $user->getLastname();
        }

        $data = array();
        if (!empty($email)) {
            $data['email'] = $email;
        }

        if (!empty($firstName)) {
            $data['firstName'] = $firstName;
        }
        if (!empty($lastName)) {
            $data['lastName'] = $lastName;
        }

        $data['hasTransacted'] = false;
        if ($user->getId()) {
            $data['userId'] = (string)$user->getId();
            $data['hasTransacted'] = $this->helper()->hasCustomerTransacted($user);
        }

        $data['customerGroup'] = Mage::getSingleton('customer/session')->getCustomerGroupId();
        $data['isReturning'] = $user->getId() ? true : false;
        $data['language'] = Mage::getStoreConfig('general/locale/code');

        $this->setUser($data);
        return $this;
    }

    /**
     * @param Mage_Customer_Model_Address_Abstract $address
     * @return array
     */
    protected function _getAddressData(Mage_Customer_Model_Address_Abstract $address)
    {
        $data = array();
        if ($address) {
            $data['firstName'] = $address->getFirstname();
            $data['lastName'] = $address->getLastname();
            $data['address'] = $address->getStreetFull();
            $data['city'] = $address->getCity();
            $data['postalCode'] = $address->getPostcode();
            $data['country'] = $address->getCountry();
            $data['stateProvince'] = $address->getRegion() ? $address->getRegion() : '';
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function _getCurrency()
    {
        return Mage::app()->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getProductData(Mage_Catalog_Model_Product $product)
    {
        $data = array(
            'id' => $product->getId(),
            'url' => $product->getProductUrl(),
            'imageUrl' => (string)Mage::helper('catalog/image')->init($product, 'image')->resize(265),
            'thumbnailUrl' => (string)Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(75, 75),
            'name' => $product->getName(),
            'unitPrice' => (float)$product->getPrice(),
            'unitSalePrice' => (float)$product->getFinalPrice(),
            'currency' => $this->_getCurrency(),
            'description' => strip_tags($product->getShortDescription()),
            'skuCode' => $product->getSku()
        );

        $data['stock'] = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();

        $catIndex = $catNames = array();
        $limit = 2; $k = 0;
        foreach ($product->getCategoryIds() as $catId) {
            if (++$k > $limit) {
                break;
            }
            if (!isset($catIndex[$catId])) {
                $catIndex[$catId] = Mage::getModel('catalog/category')->load($catId);
            }
            $catNames[] = $catIndex[$catId]->getName();
        }

        if (isset($catNames[0])) {
            $data['category'] = $catNames[0];
        }
        if (isset($catNames[1])) {
            $data['subcategory'] = $catNames[1];
        }

        return $data;
    }

    protected function _getLineItems($items, $pageType)
    {
        $data = array();
        foreach ($items as $item) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            // product needs to be visible
            if (!$product->isVisibleInSiteVisibility()) {
                continue;
            }

            $line = array();
            $line['product'] = $this->getProductData($product);
            $line['subtotal'] = (float)$item->getRowTotalInclTax();
            $line['totalDiscount'] = (float)$item->getDiscountAmount();

            $line['quantity'] = $pageType == 'cart' ? (float)$item->getQty() : (float)$item->getQtyOrdered();

            $line['product']['unitSalePrice'] -= $line['totalDiscount'] / $line['quantity'];

            $data[] = $line;
        }

        return $data;
    }

    /**
     * None of the listing variables can be properly set here
     * since the product collection is being loaded
     * in the Mage_Catalog_Block_Product_List::_beforeToHtml method
     *
     * @see Driveback_DigitalDataLayer_Model_Observer::processListingData
     */
    protected function _initListing()
    {
        $data = array();
        if ($this->helper()->isSearchPage()) {
            $data['query'] = Mage::helper('catalogsearch')->getQueryText();
        }

        $this->setListing($data);
        return $this;
    }

    protected function _initProduct()
    {
        $product = Mage::registry('current_product');
        if (!$product instanceof Mage_Catalog_Model_Product || !$product->getId()) {
            return false;
        }

        $this->setProduct($this->getProductData($product));
        return $this;
    }

    protected function _initCart()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        if ($quote->getItemsCount() > 0) {

            $data = array();
            if ($quote->getId()) {
                $data['id'] = (string)$quote->getId();
            }

            $data['currency'] = $this->_getCurrency();
            $data['subtotal'] = (float)$quote->getSubtotal();
            $data['tax'] = (float)$quote->getShippingAddress()->getTaxAmount();
            $data['subtotalIncludeTax'] = (boolean)$this->_doesSubtotalIncludeTax($quote, $data['tax']);
            $data['shippingCost'] = (float)$quote->getShippingAmount();
            $data['shippingMethod'] = $quote->getShippingMethod() ? $quote->getShippingMethod() : '';
            $data['total'] = (float)$quote->getGrandTotal();
            $data['lineItems'] = $this->_getLineItems($quote->getAllVisibleItems(), 'cart');
        } else {
            $data['subtotal'] = 0;
            $data['total'] = 0;
            $data['lineItems'] = array();
        }

        $this->setCart($data);
        return $this;
    }

    /**
     * @param $order
     * @param $tax
     * @return bool
     */
    protected function _doesSubtotalIncludeTax($order, $tax)
    {
        /* Conditions:
            - if tax is zero, then set to false
            - Assume that if grand total is bigger than total after subtracting shipping, then subtotal does NOT include tax
        */
        $grandTotalWithoutShipping = $order->getGrandTotal() - $order->getShippingAmount();
        return !($tax == 0 || $grandTotalWithoutShipping > $order->getSubtotal());
    }

    protected function _initTransaction()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if (!$orderId) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        $transaction = array();

        $transaction['orderId'] = $order->getIncrementId();
        $transaction['currency'] = $this->_getCurrency();
        $transaction['subtotal'] = (float)$order->getSubtotal();
        $transaction['tax'] = (float)$order->getTaxAmount();
        $transaction['subtotalIncludeTax'] = $this->_doesSubtotalIncludeTax($order, $transaction['tax']);
        $transaction['paymentType'] = $order->getPayment()->getMethodInstance()->getTitle();
        $transaction['total'] = (float)$order->getGrandTotal();

        if ($order->getCouponCode()) {
            $transaction['voucher'] = array($order->getCouponCode());
        }

        if ($order->getDiscountAmount() > 0) {
            $transaction['voucherDiscount'] = -1 * $order->getDiscountAmount();
        }

        $transaction['shippingCost'] = (float)$order->getShippingAmount();
        $transaction['shippingMethod'] = $order->getShippingMethod() ? $order->getShippingMethod() : '';

        // Get addresses
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress instanceof Mage_Sales_Model_Order_Address) {
            $transaction['delivery'] = $this->_getAddressData($shippingAddress);
        }

        $transaction['billing'] = $this->_getAddressData($order->getBillingAddress());

        // Get items
        $transaction['lineItems'] = $this->_getLineItems($order->getAllVisibleItems(), 'transaction');

        $this->setTransaction($transaction);
        return $this;
    }

}
