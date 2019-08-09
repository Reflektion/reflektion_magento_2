<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To open form for download logs
 */

namespace Reflektion\Catalogexport\Controller\Adminhtml\Job;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * Initialize Job Controller
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Initiate action
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Reflektion_Catalogexport::job')
            ->_addBreadcrumb(__('Download Logs'), __('Download Logs'));
        return $this;
    }

    public function execute()
    {
        $this->_initAction();
        $this->_view->getLayout()->getBlock('catalogexport_job_edit');
        $this->_view->renderLayout();
    }
}
