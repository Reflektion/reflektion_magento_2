<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    10/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 */

namespace Reflektion\Catalogexport\Helper;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const LOG_FILE = "reflektion.log";
    /**
     * @param \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryFactory;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $oIo;
    /**
     * @var \Magento\Search\Helper\Data
     */
    protected $searchHelper;

    protected $return;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryFactory
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Filesystem\Io\File $oIo
     * @param \Magento\Search\Helper\Data $searchHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        Logger $logger,
        ManagerInterface $messageManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryFactory,
        DirectoryList $directoryList,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Filesystem\Io\File $oIo,
        \Magento\Search\Helper\Data $searchHelper
    ) {
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        parent::__construct($context);
        $this->categoryFactory = $categoryFactory;
        $this->directoryList = $directoryList;
        $this->productMetadata = $productMetadata;
        $this->file = $file;
        $this->oIo = $oIo;
        $this->searchHelper = $searchHelper;
        $return = array();
    }

    /**
     * Validate feed configuration settings for one website or all websites
     */
    public function validateFeedConfiguration($websiteId = null)
    {
        $this->logger->info("id = " . $websiteId);
        $websites = [];
        if ($websiteId) {
            $websites[] = $this->storeManager->getWebsite($websiteId)->getCode();
        } else {
            $websiteModels = $this->storeManager->getWebsites(false, true);
            foreach ($websiteModels as $curWebsite) {
                $websites[] = $curWebsite->getCode();
            }
        }
        //Track if feeds enabled for any website
        $bFeedsEnabled = false;
        foreach ($websites as $websiteCode) {
            if ($this->scopeConfig->getValue(
                'reflektion_datafeeds/general/allfeedsenabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            ) == 'enabled') {
                $bFeedsEnabled = true;
                try {
                    $sftpHost = $this->scopeConfig->getValue(
                        'reflektion_datafeeds/connect/hostname',
                        \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                        $websiteCode
                    );
                    $sftpPort = $this->scopeConfig->getValue(
                        'reflektion_datafeeds/connect/port',
                        \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                        $websiteCode
                    );
                    $sftpUser = $this->scopeConfig->getValue(
                        'reflektion_datafeeds/connect/username',
                        \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                        $websiteCode
                    );
                    $sftpPassword = $this->encryptor->decrypt(
                        $this->scopeConfig->getValue(
                            'reflektion_datafeeds/connect/password',
                            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                            $websiteCode
                        )
                    );
                } catch (\Exception $e) {
                    $this->logger->addError($e->getMessage());
                    $this->logger->info($e->getMessage());
                    throw new \Exception('Error looking up feed transfer connectivity parameters for website :'
                        . $websiteCode);
                }
                if (strlen($sftpHost) <= 0 || strlen($sftpPort) <= 0 || $sftpPort < 1 || $sftpPort > 65535
                    || strlen($sftpUser) <= 0 || strlen($sftpPassword) <= 0
                ) {
                    $this->logger->info("Wrong SFTP detail for website " . $websiteCode);
                    $this->messageManager->addError("Wrong SFTP detail for website " . $websiteCode);
                }
            }
        }
        // Send error message
        if (!$bFeedsEnabled) {
            throw new \Exception("Data feeds not enabled");
        }
    }

    /**
     *  Description  To get the Categories list with breadcrum
     */
    public function getTreeCategories($parentId)
    {

        $categoryRepository = $this->categoryFactory->create();
        $categoryRepository
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('parent_id', ['eq' => $parentId]);

        foreach ($categoryRepository as $category) {
            $this->allCat[$category->getId()] = $category->getName();
            $this->allCatId[$category->getId()] = $category->getId();
            $array = explode('/', $category->getPath());
            unset($array[0]);
            unset($array[1]);
            foreach ($array as $arrayEle) {
                $this->tempArray[] = $this->allCat[$arrayEle];
                $this->tempIdArray[] = $this->allCatId[$arrayEle];
            }
            $this->return[$category->getId()] = implode(' > ', $this->tempArray);
            $this->return['id_breadcrumb_'.$category->getId()] = implode(' > ', $this->tempIdArray);
            $this->return['name_'.$category->getId()] = $category->getName();
            $this->tempArray = [];
            $this->tempIdArray = [];
            $subcats = $category->getChildren();
            if ($subcats != '') {
                $this->getTreeCategories($category->getId());
            }
        }
        return $this->return;
    }

    /*
   * To show result based on selected dates from log file
   */

    public function filterLogResults($filename, $sDate, $lDate)
    {
        $filepath = $this->directoryList->getPath('log') . DIRECTORY_SEPARATOR . $filename;
        $file = $this->file->fileOpen($filepath, 'r');
        $matchFound = 0;
        $content = '';
        while (!feof($file)) {
            $line = fgets($file);
            $dateInLine = ltrim(substr($line, 0, 11), '[');
            if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dateInLine)) {
                if ($sDate > $dateInLine) {
                    continue;
                } elseif ($lDate < $dateInLine) {
                    break;
                }
                $content .= $line . "\n";
                $matchFound = 1;
            } elseif ($matchFound) {
                $content .= $line . "\n";
            }
        }
        $this->file->fileClose($file);
        return $content;
    }

    /**
     * Get Magento edition EE/CE
     */
    public function getEdition()
    {
        return $this->productMetadata->getEdition();
    }
    /**
     * Check file exists
     */
    public function fileExists($filePath)
    {
        return $this->oIo->fileExists($filePath);
    }

    /**
     * Get config value from path
     */
    public function getConfig($path)
    {
        $websiteCode = $this->storeManager->getWebsite()->getCode();
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * Get result url for search form
     */
    public function getResultUrl()
    {
        $rfkExp = $this->getConfig('reflektion_datafeeds/search/selectrfkexp');
        $integrationExp = \Reflektion\Catalogexport\Model\CatalogSearch\Layer::FRONTEND_JAVASCRIPT;
        if ($rfkExp == $integrationExp) {
            return $this->_getUrl(
                'search', array(
                    '_secure' => $this->_request->isSecure()
                )
            );
        } else {
            return $this->searchHelper->getResultUrl();
        }
    }
}
