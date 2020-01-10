<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    12/01/18
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  FPS layout load
 */

namespace Reflektion\Catalogexport\Controller\Index;

use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;

    public function __construct(Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $searchedKeyword = $this->getRequest()->getParam('q');
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set("Search results for: '" . $searchedKeyword . "'");
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
