<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    10/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Helper for analytics script

 */

namespace Reflektion\Catalogexport\Helper;

use Magento\Store\Model\StoreManagerInterface;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\Message\ManagerInterface;

class Analytics extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        StoreManagerInterface $storeManager,
        Logger $logger,
        ManagerInterface $messageManager
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }

    public function getConfig($path)
    {
        $websiteCode = $this->storeManager->getWebsite()->getCode();
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * Checks if analytics is enabled or not
     * @return boolean
     */
    public function rfkAnalyticsEnabled()
    {
        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $analyticsFlag = $this->scopeConfig->getValue(
            'reflektion_analytics/script/analyticsflag',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        if ($analyticsFlag != 'enabled') {
            return true;
        } else {
            return false;
        }
    }
}
