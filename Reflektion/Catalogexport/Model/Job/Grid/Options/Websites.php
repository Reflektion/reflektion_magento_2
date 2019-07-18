<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    12/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Show all the available websites in grid filter
 */

namespace Reflektion\Catalogexport\Model\Job\Grid\Options;

use Magento\Store\Model\StoreManagerInterface;

class Websites implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Construct
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $websiteArr = [];
        $websites = $this->storeManager->getWebsites();
        foreach ($websites as $website) {
            $websiteArr[$website->getId()] = $website->getName();
        }
        return $websiteArr;
    }
}
