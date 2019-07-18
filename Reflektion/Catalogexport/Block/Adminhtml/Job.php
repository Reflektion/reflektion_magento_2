<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To display grid container in feeds in queue admin grid
 */
namespace Reflektion\Catalogexport\Block\Adminhtml;

class Job extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = "adminhtml_job";
        $this->_blockGroup = 'Reflektion_Catalogexport';
        $this->_headerText = __('Feeds in Queue');
        parent::_construct();
        $this->removeButton('add'); // Add this code to remove the button
        $this->buttonList->add(
            'log',
            [
                'label' => __('Download logs'),
                'onclick' => "setLocation('{$this->getUrl('*/*/log')}')",
                'class' => "add primary"
            ],
            0
        );
    }
}
