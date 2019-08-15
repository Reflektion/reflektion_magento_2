<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    12/10/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Update template according to RFK script
 *
 */

namespace Reflektion\Catalogexport\Observer;

class Analytics implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Catalog\Model\Session
     */
    protected $session;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @param \Reflektion\Catalogexport\Helper\Analytics
     */
    protected $analyticsHelper;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Session $session,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Reflektion\Catalogexport\Helper\Analytics $analytics
    ) {
        $this->registry = $registry;
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->analyticsHelper = $analytics;
    }

    /**
     * observer to check every page if rfk push to insert and update layout
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $layout = $observer->getEvent()->getData("layout");
        $actionEvent = $observer->getEvent()->getData("full_action_name");

        $cartUpdated = $this->session->getData('cart_updated');
        //Fetching the page from where add to cart is triggered
        if (!$this->session->getdata("addtocart_triggered")
            && $actionEvent != "review_product_listAjax"
            && $actionEvent != "customer_section_load"
        ) {
            $this->session->setdata("cart_triggered_page", $actionEvent);
        }

        //update handle for front end javascript search experience
        $fpsSearch = $this->analyticsHelper->getConfig("reflektion_datafeeds/search/searchflag");
        $integrationExp = \Reflektion\Catalogexport\Model\CatalogSearch\Layer::FRONTEND_JAVASCRIPT;
        $rfkExp = $this->analyticsHelper->getConfig("reflektion_datafeeds/search/selectrfkexp");
        if ($rfkExp == $integrationExp && $actionEvent == "catalogsearch_result_index" && $fpsSearch == 'enabled') {
            $update = $layout->getUpdate();
            $update->addHandle('rfk_tag_search_view');
            return $this;
        }
        $productViewedFlag = $this->analyticsHelper->getConfig("reflektion_analytics/script/productviewedflag");
        $productAddedToCartFlag = $this->analyticsHelper->getConfig("reflektion_analytics/script/productaddedtocartflag");
        $statusOfCartFlag = $this->analyticsHelper->getConfig("reflektion_analytics/script/statusofcartflag");
        $userLoggedinFlag = $this->analyticsHelper->getConfig("reflektion_analytics/script/userloggedinflag");
        $confirmedOrderFlag = $this->analyticsHelper->getConfig("reflektion_analytics/script/confirmedorderflag");
         //Check whether analytics is enabled or not
        if ($this->analyticsHelper->rfkAnalyticsEnabled())
        {
            return $this;
        }
        if ($actionEvent == "checkout_onepage_success") {//Order
            if ($confirmedOrderFlag == 'enabled') {
                $update = $layout->getUpdate();
                $update->addHandle('rfk_order_analytics');
            }
        } else {
            if ($actionEvent == "catalog_product_view") {//pdp
                if ($productViewedFlag == 'enabled') {
                    $this->session->setData('rfk_product_view', "pdp");
                    $update = $layout->getUpdate();
                    $update->addHandle('rfk_product_view_analytics');
                }
            }
            if ($actionEvent == "checkout_cart_index") {//cart
                if ($statusOfCartFlag == 'enabled') {
                    $update = $layout->getUpdate();
                    $update->addHandle('rfk_cart_page_analytics_extra');
                    $update->addHandle('rfk_cart_page_analytics');
                }
                if ($cartUpdated) {
                    $this->session->unsetData('cart_updated');
                }
            } elseif ($cartUpdated) {
                if ($statusOfCartFlag == 'enabled') {
                    $update = $layout->getUpdate();
                    $update->addHandle('rfk_cart_page_analytics');
                }
                $this->session->unsetData('cart_updated');
            }
            if ($actionEvent == "catalog_product_add" && $productAddedToCartFlag == 'enabled') {//Add to cart
                $update = $layout->getupdate();
                $update->addhandle('rfk_add_to_cart_analytics');
            }
        }
        if ($this->session->getData('rfkcustomerlogin') == 'customer_login_rfk_push') {//customer login
            //push customer data
            if ($userLoggedinFlag == 'enabled') {
                $update = $layout->getupdate();
                $update->addhandle('rfk_customer_login_analytics');
            }
            $this->session->unsetData('rfkcustomerlogin');
        }
        if ($this->session->getData('addtocart_triggered') && $actionEvent != "checkout_cart_add" && $productAddedToCartFlag == 'enabled') {
            $update = $layout->getupdate();
            $update->addhandle('rfk_add_to_cart_analytics');
        }

        return $this;
    }
}
