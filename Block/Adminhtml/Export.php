<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    29/06/17
 * @time:        11:57 AM
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To display grid container in generate feeds admin grid
 */
namespace Reflektion\Catalogexport\Block\Adminhtml;

class Export extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = "adminhtml_export";
        $this->_blockGroup = 'Reflektion_Catalogexport';
        $this->_headerText = __('Generate Feeds');
        parent::_construct();
        // Remove add button
        $this->removeButton('add');
        // Export all button
        $this->buttonList->add(
            'exportall',
            [
                'label' => __('Export Feeds For All Sites'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/exportall') . '\')',
                'class' => "exportall primary"
            ],
            0,
            1
        );
        //Clean RFK DB and log data
        $this->buttonList->add(
            'cleanup',
            [
                'label' => __('Cleanup RFK data'),
                'onclick' => "confirmSetLocation('Warning : reflektion.log and DB jobs entries will be cleared." .
                    " Do you want to clean jobs?','".$this->getUrl('*/*/cleanup')."')",
                'class' => "cleanup primary"
            ],
            0,
            0
        );
    }
}
