<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    16/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Cleanup RFK data - logs and DB
 */

namespace Reflektion\Catalogexport\Controller\Adminhtml\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\Exception\FileSystemException;

class Cleanup extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;
    /**
     * @var \Reflektion\Catalogexport\Model\Job
     */
    protected $job;
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     * @param \Magento\Framework\Filesystem\Driver\File $file
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        DirectoryList $directoryList,
        Logger $logger,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Reflektion\Catalogexport\Model\Job $job
    ) {
        parent::__construct($context);
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->file = $file;
        $this->job = $job;
    }

    public function execute()
    {
        try {
            $table = $this->job->getResourceCollection()->getTable('reflektion_job');
            $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $connection->truncateTable($table);
            $logFilePath = $this->directoryList->getPath('log') . DIRECTORY_SEPARATOR .
                \Reflektion\Catalogexport\Helper\Data::LOG_FILE;
            $fileHandle = $this->file->fileOpen($logFilePath, "w");
            $this->file->fileWrite($fileHandle, "");
            $this->file->fileClose($fileHandle);
        } catch (\Exception $e) {
            throw new FileSystemException(
                new \Magento\Framework\Phrase($e->getMessage())
            );
        }
        $this->messageManager->addSuccess("Cleanup is done.");
        $this->logger->info("RFK cleanup is successfully done");
        $this->_redirect("*/*/index");
    }
}
