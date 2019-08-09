<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    18/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Manage to transfer feed from magento to other server through SFTP
 */

namespace Reflektion\Catalogexport\Model;

use Magento\Store\Model\StoreManagerInterface;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Reflektion\Catalogexport\Helper\SftpConnection;

class Transferfeeds
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
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directory_list;
    /**
     * @var \Reflektion\Catalogexport\Helper\SftpConnection
     */
    protected $rfkHelper;
    /**
     * @var \Reflektion\Catalogexport\Helper\Csvfile
     */
    protected $fileHelper;

    public function __construct(
        StoreManagerInterface $storeManager,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list,
        ObjectManagerInterface $objectManager,
        SftpConnection $rfkHelper,
        \Reflektion\Catalogexport\Helper\Csvfile $csvfile
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->directory_list = $directory_list;
        $this->_objectManager = $objectManager;
        $this->rfkHelper = $rfkHelper;
        $this->fileHelper = $csvfile;
    }

    /**
     * Transfer data feeds to Reflektion, triggered by cron
     *
     * @param $websiteId Id of the website for which to generate data feeds
     */
    public function transfer($websiteId)
    {
        try {
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
            $this->logger->info('Transferring data feeds for website with Id: ' . $websiteId);
            $this->logger->info('Memory usage: ' . memory_get_usage());
            $allFeedsEn = $this->scopeConfig->getValue(
                'reflektion_datafeeds/general/allfeedsenabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );
            if ($allFeedsEn != 'enabled') {
                throw new \Exception('Data feeds not enabled for website: ' . $websiteId);
            }
            $fileList = $this->buildFileList($websiteId);
            $bSuccess = $this->transferFileList($websiteId, $fileList);
            if (!$bSuccess) {
                throw new \Exception('Transfer file(s) list failed!');
            }
            $this->logger->info('Sucessfully transferred data feeds for website.');
        } catch (\Exception $e) {
            $this->logger->info('Failed to transfer data feeds for website.');
            $this->logger->info($e->getMessage());
            throw $e;
        }
    }
    protected function buildFileList($websiteId)
    {
        $feedPath = $this->directory_list->getPath('var') . DIRECTORY_SEPARATOR . Generatefeeds::REFLEKTION_FEED_PATH;
        $fileList = [];
        // Open directory
        $dh = opendir($feedPath);
        if ($dh === false) {
            throw new \Exception('Failed to open feed directory: ' . $feedPath);
        }
        // Files in directory
        while (($entry = readdir($dh)) !== false) {
            $fullpath = $feedPath . DIRECTORY_SEPARATOR . $entry;
            // Check if have a file
            if ($this->fileHelper->isFile($fullpath)) {
               $fileList[] = $fullpath;
            }
        }
        closedir($dh);
        $this->logger->info('Found ' . count($fileList) . ' feed files for website id: ' . $websiteId);
        return $fileList;
    }

    /**
     * Transfer this list of files to the SFTP site for Reflektion
     *
     * @param $websiteId Id of the website for which to generate data feeds
     * @param $fileList List of file names (full path) to transfer
     * @return bool Indicates if files successfully transfered or not
     */
    protected function transferFileList($websiteId, array $fileList)
    {
        $this->logger->info('Transferring ' . count($fileList) . ' files for website id: ' . $websiteId);
        try {
            // Get hostname, port & credentials
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
            $curWebsite = $this->scopeConfig;
            $sftpHost = $curWebsite->getValue(
                'reflektion_datafeeds/connect/hostname',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );
            $sftpPort = $curWebsite->getValue(
                'reflektion_datafeeds/connect/port',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );
            $sftpUser = $curWebsite->getValue(
                'reflektion_datafeeds/connect/username',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );
            $sftpPassword = $curWebsite->getValue(
                'reflektion_datafeeds/connect/password',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );
        } catch (\Exception $e) {
            throwException($e);
            $this->logger->info($e->getMessage());
            return false;
        }
        // Connect to server
        $bSuccess = $this->rfkHelper->connect($sftpHost, $sftpPort, $sftpUser, $sftpPassword);
        if (!$bSuccess) {
            $this->logger->info('Failed to connect to Reflektion!');
            return false;
        }
        $sftpFolder = $curWebsite->getValue(
            'reflektion_datafeeds/connect/path',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        $bSuccess = $this->rfkHelper->changeDir($sftpFolder);
        if (!$bSuccess) {
            $this->logger->info('Failed to change folders to: ' . $sftpFolder);
            return false;
        }
        //Iterate file list and put each file
        $bTransferSucceeded = true;
        foreach ($fileList as $curFile) {
            $this->logger->info('Transferring file: ' . $curFile);
            $this->logger->info('Memory usage: ' . memory_get_usage());
            $bSuccess = $this->rfkHelper->putFeedFile($curFile);
            if (!$bSuccess) {
                $this->logger->info('Failed to transfer and delete file: ' . $curFile);
                $bTransferSucceeded = false;
            }
        }
        $this->rfkHelper->close();
        if (!$bTransferSucceeded) {
            $this->logger->info('Some file transfers failed!');
            return false;
        } else {
            $this->logger->info('Successfully transferred all files.');
            return true;
        }
    }
}
