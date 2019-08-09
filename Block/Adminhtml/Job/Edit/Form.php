<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  For rendering form for download logs
 */

namespace Reflektion\Catalogexport\Block\Adminhtml\Job\Edit;

use \Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Form extends Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        DateTime $dateTime,
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->dateTime = $dateTime;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('download_form');
        $this->setTitle(__('Download Logs'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $logFiles = ["reflektion.log" => "Reflektion.log",
            "system.log" => "System.log",
            "exception.log" => "Exception.log"
        ];

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/fetchlog', ['id' => $this->getRequest()->getParam('id')]),
                'method' => 'post'
            ]]
        );

        $form->setHtmlIdPrefix('download_');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Download Log File'), 'class' => 'fieldset-wide']
        );
        $fieldset->addField(
            'log_file',
            'select',
            [
                'name' => 'log_file',
                'label' => __('Select Log File'),
                'title' => __('log file'),
                'required' => true,
                'values' => $logFiles,
                'value' => 'reflektion.log'
            ]
        );
        $fieldset->addField(
            'start_date',
            'date',
            [
                'name' => 'start_date',
                'label' => __('Choose From Date'),
                'title' => __('Choose From Date'),
                'required' => true,
                'date_format' => 'yyyy-MM-dd',
                'time' => false,
                'value' => $this->dateTime->gmtDate('Y-m-d')
            ]
        );
        $fieldset->addField(
            'end_date',
            'date',
            [
                'name' => 'end_date',
                'label' => __('Choose To Date'),
                'title' => __('Choose To Date'),
                'required' => true,
                'date_format' => 'yyyy-MM-dd',
                'time' => false,
                'value' => $this->dateTime->gmtDate('Y-m-d'),
                'after_element_html' => '<br><br><button type="button"  class="primary"
                    onclick="document.getElementById(\'edit_form\').submit()">download</button>'
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
