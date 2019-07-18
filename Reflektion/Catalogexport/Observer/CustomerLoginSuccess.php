<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    25/10/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  observer to add rfk push when customer login successfully
 *
 */

namespace Reflektion\Catalogexport\Observer;

class CustomerLoginSuccess implements \Magento\Framework\Event\ObserverInterface
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
     * @return $this
     *
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->rfkAnalyticsEnabled()) {
            $this->session->setData('rfkcustomerlogin', 'customer_login_rfk_push');
        }

        return $this;
    }
}
