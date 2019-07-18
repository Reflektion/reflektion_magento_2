<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    12/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  For rendering website name in column
 */

namespace Reflektion\Catalogexport\Block\Adminhtml\Job\Grid\Renderer;

use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

class Renderer extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @param \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * Constructor
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Backend\Block\Context $context
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }
    public function render(DataObject $row)
    {
        $websiteId = $row->getData($this->getColumn()->getIndex());
        $websiteName = $this->storeManager->getWebsite($websiteId)->getName();
        return $websiteName;
    }
}
