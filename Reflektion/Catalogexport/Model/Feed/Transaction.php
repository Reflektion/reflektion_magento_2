<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    04/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Sales attributes operations
 */

namespace Reflektion\Catalogexport\Model\Feed;

use Magento\Framework\Model\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\ObjectManagerInterface;
use Reflektion\Catalogexport\Helper\Csvfile;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Model\ResourceModel\Order\Collection;

class Transaction extends Base
{
    // Field mapping - Magento attributes to Reflektion Feed Fields

    private $fieldMap = [
        'customer_email' => 'Email Address',
        'created_at' => 'Transaction Date',
        'order_id' => 'Transaction Id',
        'base_subtotal' => 'Transaction Subtotal',
        'base_total' => 'Transaction Total',
        'item_id' => 'Item Id',
        'sku' => 'Sku',
        'product_type' => 'Item Type',
        'name' => 'Item Name',
        'parent_item_id' => 'Parent Item Id',
        'qty_ordered' => 'Item Quantity',
        'base_original_price' => 'Item Price',
        'base_row_total' => 'Item Subtotal',
        'increment_id' => 'Order Id',
    ];
    private $fieldMapShip = [
        'shipping_firstname' => 'Shipping_Firstname',
        'shipping_middlename' => 'Shipping_Middlename',
        'shipping_lastname' => 'Shipping_Lastname',
        'shipping_company' => 'Shipping_Company',
        'shipping_street' => 'Shipping_Street',
        'shipping_city' => 'Shipping_City',
        'shipping_region' => 'Shipping_Region',
        'shipping_post_code' => 'Shipping_Postcode',
        'shipping_country_id' => 'Shipping_Country_Id',
        'shipping_telephone' => 'Shipping_Telephone',
    ];
    private $fieldMapBill = [
        'billing_firstname' => 'Billing_Firstname',
        'billing_middlename' => 'Billing_Middlename',
        'billing_lastname' => 'Billing_Lastname',
        'billing_company' => 'Billing_Company',
        'billing_street' => 'Billing_Street',
        'billing_city' => 'Billing_City',
        'billing_region' => 'Billing_Region',
        'billing_post_code' => 'Billing_Postcode',
        'billing_country_id' => 'Billing_Country_Id',
        'billing_telephone' => 'Billing_Telephone',
    ];
    /**
     * @var \Reflektion\Catalogexport\Helper\Data
     */
    protected $rfkHelper;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $collection;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Reflektion\Catalogexport\Helper\Csvfile $csvfile
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Registry $registry
     * @param \Reflektion\Catalogexport\Helper\Data $rfkHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ObjectManagerInterface $objectManager,
        Csvfile $csvfile,
        DirectoryList $directoryList,
        \Magento\Framework\Registry $registry,
        \Reflektion\Catalogexport\Helper\Data $rfkHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        Collection $collection
    ) {
        parent::__construct(
            $context,
            $storeManager,
            $scopeConfig,
            $logger,
            $objectManager,
            $csvfile,
            $directoryList,
            $rfkHelper,
            $moduleManager,
            $registry
        );
        $this->collection = $collection;
    }

    public function getFieldMap($websiteId)
    {
        $fields = $this->fieldMap;
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        if ($this->scopeConfig->getValue(
            'reflektion_datafeeds/feedsenabled/shipping',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        ) == 'enabled') {
            $fields = array_merge($fields, $this->fieldMapShip);
        }
        if ($this->scopeConfig->getValue(
            'reflektion_datafeeds/feedsenabled/billing',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        ) == 'enabled') {
            $fields = array_merge($fields, $this->fieldMapBill);
        }
        return $fields;
    }

    // Feed file name key
    public function getFileNameKey()
    {
        return 'transaction';
    }

    /**
     * Build collection to do query
     *
     * @param int|string $websiteId Which website to query for collection
     * @return $collection
     */
    public function getFeedCollection($websiteId)
    {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        $configDate = $this->scopeConfig->getValue(
            'reflektion_datafeeds/feedsenabled/shipping',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        if (empty($configDate)) {
            $configDate = 30; //Default range is 30
        }
        $dateFrom = date('Y-m-d', strtotime("-$configDate days"));
        $orders = $this->collection
                    ->addAttributeToFilter('created_at', ['from' => $dateFrom])
                    ->addAttributeToSelect('*');
        $coreStore = $orders->getTable('store');
        $orders->getSelect()
            ->joinLeft($coreStore, 'main_table.store_id = '.$coreStore.'.store_id', $coreStore.'.website_id')
            ->where($coreStore.'.website_id = ' . $websiteId);
        $order_data = [];
        $i = 0;
        $collection = $this->objectManager->create('\Magento\Framework\Data\Collection');
        foreach ($orders as $order) {
            $billing = $order->getBillingAddress();
            $shipping = $order->getShippingAddress();
            $items = $order->getAllItems();

            foreach ($items as $item) {
                $order_data[$i]['order_id'] = $order->getId();
                $order_data[$i]['increment_id'] = $order->getIncrementId();
                $order_data[$i]['created_at'] = $order->getCreatedAt();
                $order_data[$i]['customer_email'] = $order->getCustomerEmail();
                $order_data[$i]['base_subtotal'] = $order->getBaseSubtotal();
                $order_data[$i]['base_total'] = $order->getGrandTotal();
                $order_data[$i]['product_type'] = $item->getProductType();
                $order_data[$i]['name'] = $item->getName();
                $order_data[$i]['parent_item_id'] = $item->getParentItemId();
                $order_data[$i]['item_id'] = $item->getItemId();
                $order_data[$i]['base_row_total'] = $item->getBaseRowTotal();
                $order_data[$i]['base_original_price'] = $item->getBaseOriginalPrice();
                $order_data[$i]['qty_ordered'] = $item->getQtyOrdered();
                $order_data[$i]['sku'] = $item->getSku();

                //Add shipping address info if enabled
                if ($this->scopeConfig->getValue(
                    'reflektion_datafeeds/feedsenabled/shipping',
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                    $websiteCode
                ) == 'enabled') {
                    if (is_object($shipping)) {
                        $order_data[$i]['shipping_firstname'] = $shipping->getFirstname();
                        $order_data[$i]['shipping_middlename'] = $shipping->getMiddlename();
                        $order_data[$i]['shipping_lastname'] = $shipping->getLastname();
                        $order_data[$i]['shipping_company'] = $shipping->getCompany();
                        $order_data[$i]['shipping_street'] = $shipping->getData('street');
                        $order_data[$i]['shipping_city'] = $shipping->getCity();
                        $order_data[$i]['shipping_region'] = $shipping->getRegion();
                        $order_data[$i]['shipping_post_code'] = $shipping->getPostcode();
                        $order_data[$i]['shipping_country_id'] = $shipping->getCountryId();
                        $order_data[$i]['shipping_telephone'] = $shipping->getTelephone();
                    }
                }
                //Add billing address info if enabled
                if ($this->scopeConfig->getValue(
                    'reflektion_datafeeds/feedsenabled/billing',
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                    $websiteCode
                ) == 'enabled') {
                    $order_data[$i]['billing_firstname'] = $billing->getFirstname();
                    $order_data[$i]['billing_middlename'] = $billing->getMiddlename();
                    $order_data[$i]['billing_lastname'] = $billing->getLastname();
                    $order_data[$i]['billing_company'] = $billing->getCompany();
                    $order_data[$i]['billing_street'] = $billing->getData('street');
                    $order_data[$i]['billing_city'] = $billing->getCity();
                    $order_data[$i]['billing_region'] = $billing->getRegion();
                    $order_data[$i]['billing_post_code'] = $billing->getPostcode();
                    $order_data[$i]['billing_country_id'] = $billing->getCountryId();
                    $order_data[$i]['billing_telephone'] = $billing->getTelephone();
                }
                $newItem = $collection->getNewEmptyItem();
                $newItem->setData($order_data[$i]);
                $collection->addItem($newItem);
                $i++;
            }
        }
        return $collection;
    }
}
