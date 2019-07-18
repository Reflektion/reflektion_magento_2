<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To download log file
 */

namespace Reflektion\Catalogexport\Controller\Adminhtml\Job;

use Magento\Backend\App\Action\Context;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\App\Filesystem\DirectoryList;
use Reflektion\Catalogexport\Helper\Data;

class Fetchlog extends \Magento\Backend\App\Action
{
    protected $DS = DIRECTORY_SEPARATOR;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Reflektion\Catalogexport\Helper\Data
     */
    protected $rfkHelper;
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        Logger $logger,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        Data $helper
    ) {
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->rfkHelper = $helper;
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPost();
        $sDate = isset($data["start_date"]) ? $data["start_date"] : '';
        $lDate = isset($data["end_date"]) ? $data["end_date"] : '';
        $filename = isset($data["log_file"]) ? $data["log_file"] : '';
        if (!$filename) {
            $this->messageManager->addError('Please select a log file');
            $this->_redirect($this->_redirect->getRefererUrl());
            return;
        }
        $filepath = $this->directoryList->getPath('log') . $this->DS . $filename;
        if ($this->rfkHelper->fileExists($filepath)) {
            try {
                $result = $this->rfkHelper->filterLogResults($filename, $sDate, $lDate);
                if ($result) {
                    $this->fileFactory->create(
                        basename($filename),
                        $result
                    );
                    $resultRaw = $this->resultRawFactory->create();
                    $resultRaw->setContents($result); //set content for download file here
                    return $resultRaw;
                } else {
                    $this->messageManager->addError('no result found for file ' . $filename .
                        ' between dates ' . $sDate . ' and ' . $lDate);
                    $this->_redirect($this->_redirect->getRefererUrl());
                    return;
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError($filepath . ' not found');
            $this->_redirect($this->_redirect->getRefererUrl());
            return;
        }
    }
}
