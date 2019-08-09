<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Block for analytics script
 */

namespace Reflektion\Catalogexport\Block;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Number;

class Analytics extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;
    /**
     * @var \Magento\Catalog\Model\Session
     */
    protected $session;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Magento\CatalogInventory\Model\Stock\Item
     */
    protected $stockItemModel;
    /**
     * @var \Reflektion\Catalogexport\Helper\Analytics
     */
    protected $helper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    public $pageNames = [
        "checkout_cart_add" => "cart",
        "catalog_category_view" => "category",
        "catalog_product_view" => "pdp",
        "cms_index_index" => "home"
    ];

    public $attrType = "custom";

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Model\Session $session,
        \Magento\Checkout\Model\Cart $cart,
        \Reflektion\Catalogexport\Helper\Analytics $analytics,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order $order,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\CatalogInventory\Model\Stock\Item $item,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $context->getStoreManager();
        $this->product = $product;
        $this->session = $session;
        $this->cart = $cart;
        $this->helper = $analytics;
        $this->checkoutSession = $checkoutSession;
        $this->order = $order;
        $this->customerSession = $customerSession;
        $this->stockItemModel = $item;
        parent::__construct($context, $data);
    }

    /**
     * GETS ADD TO CART ATTRIBUTES FROM CONFIG AND RETURNS AN ARRAY
     *
     * @param $attributeToLoad CAN BE SKU OR ID
     * @param $qty QTY ADDED TO CART
     * @param $groupProd CHECKS WHETHER GROUP PRODUCT OR NOT
     */
    public function getAttributes($attributeToLoad, $qty, $groupProd = false, $bundleOptions = NULL)
    {
        if ($groupProd) {
            $product = $this->product->load($attributeToLoad);
        } elseif (isset($bundleOptions)) {
            $product = $this->product->load($attributeToLoad);
        } else {
            $product = $this->product->loadByAttribute('sku', $attributeToLoad);
        }
        $stockItem = $this->stockItemModel->load($product->getId(),'product_id')->getData();
        //GET ATTRIBUTE TEMPLATE FROM CONFIG AND CONVERT INTO ARRAY
        $a2cTemplate = $this->getConfig('reflektion_analytics/script/productaddedtocart_template');
        //IF TEMPLATE FOR ADD TO CART IS NOT DEFINED, LOAD GLOBAL TEMPLATE
        if (!$a2cTemplate) {
            $a2cTemplate = $this->getConfig('reflektion_analytics/script/global_product_template');
        }
        $a2cAttributes = (array) json_decode($a2cTemplate);
        $result = $this->createResult($product, $stockItem, $qty, $a2cAttributes, "global");

        if (isset($bundleOptions)) {
            $bundleOptions = array_unique($bundleOptions);
            foreach ($bundleOptions as $bundleOption) {
                if (is_array($bundleOption)) {
                    foreach ($bundleOption as $bundleOptionEle) {
                        if ($productoption = $this->product->load($bundleOptionEle)) {
                            $result['bundle_skus'][] = $productoption->getSku();
                        }
                    }
                } else {
                    if ($productoption = $this->product->load($bundleOption)) {
                        $result['bundle_skus'][] = $productoption->getSku();
                    }
                }
            }
        }

        //GET ADDITIONAL ATTRIBUTES SELECTED FROM MULTISELECT
        if (in_array("{ATTRIBUTES}", $a2cAttributes)) {
            $additionalAttributes = $this->getConfig('reflektion_analytics/script/product_attributes');
            $additionalAttributeKey = array_search("{ATTRIBUTES}", $a2cAttributes);
            if ($additionalAttributes == "") {
                $result[$additionalAttributeKey] = (object) array();
            } else {
                $additionalAttributes = explode(",", $additionalAttributes);
                $result[$additionalAttributeKey] = $this->createResult(
                    $product,
                    $stockItem,
                    $qty,
                    $additionalAttributes,
                    $this->attrType
                );
            }
        }
        return $result;
    }
    /**
     * ITERATES THROUGH PRODUCT ATTRIBUTES AND FETCHES THEIR VALUE AND RETURNS AN ARRAY
     *
     * @param $product \Magento\Catalog\Model\Product PRODUCT WHOSE ATTRIBUTES ARE REQUESTED
     * @param $qty QTY ADDED TO CART
     * @param $stockItem  array STOCK ITEM INFO OF PRODUCT
     * @param $prodAttributes  array ATTRIBUTES OF THE PRODUCT
     * @param $type String CAN BE GLOBAL FOR TEMPLATE OR CUSTOM FOR MULTISELECT ATTRIBUTES
     * @param $price PRICE OF PRODUCT IN CART
     * @return array
     */
    public function createResult($product, $stockItem, $qty, $prodAttributes, $type, $price = false)
    {
        $result = [];
        foreach ($prodAttributes as $prodAttribute => $val) {
            $code = strtolower(trim($val, "{}"));
            if ($code == "price") {
                if ($price) {
                    $result[$prodAttribute] = $price;
                    continue;
                }
            }

            $attribute = $product->getResource()->getAttribute($code);
            if ($attribute) {
                $value = $attribute->getFrontend()->getValue($product);
                $value = $value == "No" || $value == null ? '' : $value;
                if ($value == '') {
                    continue;
                }
            } else {
                $value = isset($stockItem[$code]) ? $stockItem[$code] : '';
            }
            if ($type == "global") {
                if ($code == "qty") {
                    $result[$prodAttribute] = $qty;
                } else {
                    $result[$prodAttribute] = $value;
                }
            } else {
                $result[$code] = $value;
            }
        }
        return $result;
    }

    public function getCartAnalyticsData()
    {
        $additionalAttributesFlag = 0;
        $cart = $this->cart->getQuote();
        $items = $cart->getAllVisibleItems();
        $cartTemplate = $this->getConfig('reflektion_analytics/script/statusofcart_template');
        //IF TEMPLATE FOR ADD TO CART IS NOT DEFINED, LOAD GLOBAL TEMPLATE
        if (!$cartTemplate) {
            $cartTemplate = $this->getConfig('reflektion_analytics/script/global_product_template');
        }

        $cartTemplateParsed = json_decode($cartTemplate);
        $cartAttributes = (array)$cartTemplateParsed;
        if (in_array("{ATTRIBUTES}", $cartAttributes)) {
            $additionalAttributes = $this->getConfig('reflektion_analytics/script/product_attributes');
            $additionalAttributeKey = array_search("{ATTRIBUTES}", $cartAttributes);
            if ($additionalAttributes == "") {
                $additionalAttributesFlag = 0;
            } else {
                $additionalAttributes = explode(",", $additionalAttributes);
                $additionalAttributesFlag = 1;
            }
        }

        $prodArr = [];
        $i = 0;
        foreach ($items as $item) {
            $result = [];
            $sku = $item->getProduct()['sku'];
            $product = $this->product->loadByAttribute('sku', $sku);
            $stockItem = $this->stockItemModel->load($product->getId(),'product_id')->getData();
            $qty = $item->getQty();
            $price = $item->getPrice();
            $result = $this->createResult($product, $stockItem, $qty, $cartAttributes, "global", $price);
            if ($additionalAttributesFlag) {
                $result[$additionalAttributeKey] =
                    $this->createResult($product, $stockItem, $qty, $additionalAttributes, $this->attrType, $price);
            } else {
                $result[$additionalAttributeKey] = (object)[];
            }
            $i++;
            $prodArr[] = $result;
        }
        return $prodArr;
    }

    /*
    * FETCH SKU OF ALL PRODUCTS IN CART
    *
    * @return array
    */
    public function getCartSkus()
    {
        $cart = $this->cart->getQuote();
        $items = $cart->getAllVisibleItems();
        $cartSkus = [];
        foreach ($items as $item) {
            $cartSkus[] = $item->getProduct()['sku'];
        }

        return $cartSkus;
    }

    /*
     * Get page name from action identifier
     *
     * @param $actionName is a action identifier
     * @return string
     */
    public function getPageName($actionName)
    {
        if (!$actionName) {
            return $actionName;
        }
        $name = isset($this->pageNames[$actionName]) ? $this->pageNames[$actionName] : $actionName;
        return $name;
    }

    /*
     * Get configuration from path
     *
     * @param $path is a path to configuration
     */
    public function getConfig($path)
    {
        return $this->helper->getConfig($path);
    }

    /*
     * Get current product
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /*
     * Get product data using product id
     *
     * @param $id is product id
     * @return array
     */
    public function getProductData($id)
    {
        $product = $this->product->load($id);
        return $product->getData();
    }

    /*
     * Get product data using product id
     *
     * @param $id is product id
     * @return array
     */
    public function getProductModel($id)
    {
        return $this->product->load($id);
    }

    /*
     * @return \Magento\Catalog\Model\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /*
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->order->load($this->checkoutSession->getLastOrderId());
    }

    /*
    * @return \Magento\Checkout\Model\Session
    */
    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /*
    * @return \Magento\Customer\Model\Session
    */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    /*
     * Fetch current full action page name
     * @return string
     */
    public function getCurrentPage()
    {
        return $this->_request->getFullActionName();
    }
}
