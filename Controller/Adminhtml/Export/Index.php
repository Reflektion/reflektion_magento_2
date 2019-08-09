<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    21/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Display grid for generating feeds
 */

namespace Reflektion\Catalogexport\Controller\Adminhtml\Export;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

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
     * Load the page defined in view/adminhtml/layout/reflektion_export_index.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        //Call page factory to render layout and page content
        $resultPage = $this->resultPageFactory->create();

        //Set the menu which will be active for this page
        $resultPage->setActiveMenu('Reflektion_Catalogexport::export');

        //Set the header title of grid
        $resultPage->getConfig()->getTitle()->prepend(__('Generate Feeds'));

        //Add bread crumb
        $resultPage->addBreadcrumb(__('Reflektion'), __('Reflektion'));
        $resultPage->addBreadcrumb(__('Data Feeds'), __('Generate Feeds'));

        return $resultPage;
    }

    /*
     * Check permission via ACL resource
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Reflektion_Catalogexport::export');
    }
}
