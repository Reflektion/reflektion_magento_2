<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    01/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To download latest feed out
 */

namespace Reflektion\Catalogexport\Controller\Adminhtml\Job;

use Magento\Backend\App\Action\Context;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;

class Downloadfeed extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $readFile;

    protected $DS = DIRECTORY_SEPARATOR;

    public function __construct(
        Context $context,
        Logger $logger,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        DirectoryList $directoryList,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        $this->logger = $logger;
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->storeManager = $storeManager;
        $this->readFile = $file;
        parent::__construct($context);
    }

    /**
     * Load the page defined in view/adminhtml/layout/reflektion_job_index.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('website_id');
        $feedType = $this->getRequest()->getParam('feed_type');
        try {
            $exportPath = $this->directoryList->getPath('var') . $this->DS .
                \Reflektion\Catalogexport\Model\Generatefeeds::REFLEKTION_FEED_PATH;
            $websiteName = parse_url($this->storeManager->getWebsite($websiteId)->getDefaultStore()->getBaseUrl(), PHP_URL_HOST);
            $downloadName = $websiteName . '_' . $websiteId . '_' . $feedType . '_feed' . '_' . date('Y_M_d') . '.csv';
            $filepath = $exportPath . $this->DS . $websiteName . '_' . $websiteId . '_' . $feedType . '_feed.csv';
            $this->logger->info("web name: ".$websiteName);
            $this->logger->info("download name: ".$downloadName);
            $this->logger->info("filepath: ".$filepath);
            $csvContent = $this->readFile->fileGetContents($filepath);
            if ($csvContent) {
                $this->fileFactory->create(
                    basename($downloadName),
                    $csvContent
                );
                $resultRaw = $this->resultRawFactory->create();
                $resultRaw->setContents($csvContent); //set content for download file here
                return $resultRaw;
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $this->_redirect("*/*/index");
    }
}
