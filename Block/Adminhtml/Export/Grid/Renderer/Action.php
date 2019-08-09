<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    12/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  For link for Export Feeds
 */

namespace Reflektion\Catalogexport\Block\Adminhtml\Export\Grid\Renderer;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
{
    /**
     * Renders column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $this->getColumn()->setActions([[
            'url' => $this->getUrl('*/*/exportone', ['id' => $row->getId()]),
            'caption' => __('Export Feed For ' . $row->getWebsiteName()),
        ]]);
        return parent::render($row);
    }
}
