<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    21/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Generate Jobs for Feed
 */

namespace Reflektion\Catalogexport\Controller\Adminhtml\Job;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory = false;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Load the page defined in view/adminhtml/layout/reflektion_job_index.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        //Call page factory to render layout and page content
        $resultPage = $this->resultPageFactory->create();

        //Set the menu which will be active for this page
        $resultPage->setActiveMenu('Reflektion_Catalogexport::job');

        //Set the header title of grid
        $resultPage->getConfig()->getTitle()->prepend(__('Feeds in Queue'));

        //Add bread crumb
        $resultPage->addBreadcrumb(__('Reflektion'), __('Reflektion'));
        $resultPage->addBreadcrumb(__('Data Feeds'), __('Feeds in Queue'));

        return $resultPage;
    }
    /*
     * Check permission via ACL resource
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Reflektion_Catalogexport::job');
    }
}
