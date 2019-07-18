<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    03/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Generate catalog product feeds output(CSV) in queue
 */
namespace Reflektion\Catalogexport\Model;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\ObjectManagerInterface;

class Generatefeeds
{
    protected $DS = DIRECTORY_SEPARATOR;
    const REFLEKTION_FEED_PATH = 'reflektion/feeds';
    protected static $feedTypes = [
        'product',
        'category',
        'transaction'
    ];
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $oIo;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        File $oIo,
        ObjectManagerInterface $objectManager
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->oIo = $oIo;
        $this->objectManager = $objectManager;
    }

    /**
     * Return a list of possible feed types
     *
     * @returns array Array of all known feeds types
     */
    public static function getFeedTypes()
    {
        return self::$feedTypes;
    }

    /**
     * Generate data feeds for this specific website
     *
     * @param $websiteId Id of the website for which to generate data feeds
     * @param $bBaselineFile Should this file be a baseline file or an automated daily file
     * @param $feedType Type of feed to generate, null = generate all feeds
     * @param $minEntityId Number representing minimum value for entity Id to export -
     * This acts as a placeholder for where the feed export left off
     */
    public function generateForWebsite($websiteId, $bBaselineFile, $feedType, &$minEntityId, &$bDone)
    {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        if ($this->scopeConfig->getValue(
            'reflektion_datafeeds/general/allfeedsenabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        ) != 'enabled'
        || $this->scopeConfig->getValue(
            'reflektion_datafeeds/feedsenabled/' . $feedType,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        ) != 'enabled') {
            throw new \Exception('Data feeds or feedtype ' . $feedType . ' not enabled for website: ' . $websiteId);
        }
        $feedExportPath = $this->directoryList->getPath('var') . $this->DS . self::
            REFLEKTION_FEED_PATH;
        $this->oIo->checkAndCreateFolder($feedExportPath);
        $modelFeed = $this->objectManager->create("Reflektion\\Catalogexport\\Model\\Feed\\" . ucfirst($feedType));
        $modelFeed->generate($websiteId, $feedExportPath, $bBaselineFile, $minEntityId, $bDone, $feedType);
    }
}
