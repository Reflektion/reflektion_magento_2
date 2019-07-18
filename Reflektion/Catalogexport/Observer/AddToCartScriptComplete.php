<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    12/10/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  fetches products added to cart and saves them in session
 *
 */

namespace Reflektion\Catalogexport\Observer;

class AddToCartScriptComplete implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Catalog\Model\Session
     */
    protected $session;
    /**
     * @var \Reflektion\Catalogexport\Helper\Analytics
     */
    protected $helper;

    public function __construct(
        \Magento\Catalog\Model\Session $session,
        \Reflektion\Catalogexport\Helper\Analytics $analytics
    ) {
        $this->session = $session;
        $this->helper = $analytics;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * if there are untracked products already in session, it merges them with current added product
     * @return $this
     *
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->rfkAnalyticsEnabled()) {
            $event = $observer->getEvent();
            $product = $event->getProduct();
            $request = $event->getRequest();
            $requestparams = $request->getparams();
            $qty = isset($request->getparams()['qty']) ? $request->getparams()['qty'] : 1;
            //get untracked products added to cart from session and merge with current added product
            if ($this->session->getdata('addtocart_triggered')) {
                $result = $this->session->getdata('addtocart_triggered');
            }
            $resulttemp = [
                "id" => $product->getid(),
                "price" => $product->getfinalprice(),
                "qty" => $qty,
                "sku" => $product['sku']
            ];
            if (isset($requestparams['super_group'])) { //if group product
                $resulttemp['super_group'] = $requestparams['super_group'];
            } elseif (isset($requestparams['bundle_option'])) { //if bundle product
                $resulttemp['bundle_option'] = $requestparams['bundle_option'];
            }
            $result[] = $resulttemp;
            $this->session->setdata('addtocart_triggered', $result);
        }

        return $this;
    }
}
