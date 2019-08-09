<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    18/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Base file to manage attributes while exporting
 */
namespace Reflektion\Catalogexport\Model\Feed;

use Magento\Framework\Model\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\ObjectManagerInterface;
use Reflektion\Catalogexport\Helper\Csvfile;
use Magento\Framework\App\Filesystem\DirectoryList;

class Base extends \Magento\Framework\Model\AbstractModel
{
    protected $optionValueMap = [];
    protected $attrSetIdToName = [];
    protected $eavOption;
    protected $DS = DIRECTORY_SEPARATOR;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Reflektion\Catalogexport\Helper\Csvfile
     */
    protected $file;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $dirList;
    /**
     * @var \Reflektion\Catalogexport\Helper\Data
     */
    protected $rfkHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Reflektion\Catalogexport\Helper\Csvfile $csvfile
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Reflektion\Catalogexport\Helper\Data $rfkHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ObjectManagerInterface $objectManager,
        Csvfile $csvfile,
        DirectoryList $directoryList,
        \Reflektion\Catalogexport\Helper\Data $rfkHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\Registry $registry
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->file = $csvfile;
        $this->dirList = $directoryList;
        $this->rfkHelper = $rfkHelper;
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $registry);
    }

    protected function _initAttributeSets($storeId = 0)
    {
        $attributeValues = $this->objectManager
            ->create('\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection')
            ->setStoreFilter($storeId);
        //create an array
        foreach ($attributeValues as $values) {
            $this->attrSetIdToName[$values['option_id']] = $values['value'];
        }
        return $this;
    }

    /**
     * Generate one feed for this website and store feed file at the specified path
     *
     * @param $websiteId Id of the website for which to generate data feed file
     * @param $exportPath Path to the folder where data feed files should be stored
     * @param $bBaselineFile Should this file be a baseline file or an automated daily file
     * @param $minEntityId Number representing minimum value for entity Id to export -
     * This acts as a placeholder for where the feed export left off
     * @param $bDone Indicates when the feed generation is done
     */
    public function generate($websiteId, $exportPath, $bBaselineFile, &$minEntityId, &$bDone, $feedType)
    {
        $this->logger->info(
            'base Generating ' .
            $this->getFileNameKey() .
            ' data feed for website with Id: '
            . $websiteId
        );
        $this->logger->info("base Export path: {$exportPath}");
        $this->logger->info("base Baseline feed: {$bBaselineFile}");
        $this->logger->info("base Min entity_id: {$minEntityId}");
        $bDone = false;
        $websiteName = parse_url($this->storeManager->getWebsite($websiteId)->getDefaultStore()->getBaseUrl(), PHP_URL_HOST);

        // file generate
        $filename = $exportPath . $this->DS . $websiteName . '_' . $websiteId . '_' . $feedType . '_feed.csv';
        $this->logger->info("csv file name :  " . $filename);
        $collection = $this->getFeedCollection($websiteId);
        $headerColumns = array_values($this->getFieldMap($websiteId));
        // New file with headers
        $bSuccess = $this->file->open($filename, $headerColumns);
        if (!$bSuccess) {
            $this->logger->info('Failed to open data feed file:' . $filename);
            return false;
        }
        $iDefaultStoreId = $this->storeManager->getWebsite($websiteId)->getDefaultGroupId();
        //get all the website attribute
        $this->logger->info('Initializing attribute values');
        $this->_initAttributeSets($iDefaultStoreId); //uncomment afterwards
        $rootCatId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getRootCategoryId();
        $catlistHtml = $this->rfkHelper->getTreeCategories($rootCatId);
        $pageSize = 1000;
        $collection->setPageSize($pageSize);
        $pages = $collection->getLastPageNumber();
        $this->logger->info('------Pages------  ' . $pages);
        $currentPage = 0;
        do {
            //test code remove later on
            $this->logger->info('Started processing batch ' . $currentPage);
            $currentPage++;
            $collection->setCurPage($currentPage);
            foreach ($collection as $curRow) {
                $curRowData = $curRow->getData();
                $rowValues = [];
                foreach ($this->getFieldMap($websiteId) as $mapKey => $mapValue) {
                    // If the attribute is a select or multiselect then we need to translate the
                    // option id value into the display value
                    if (array_key_exists($mapKey, $this->optionValueMap)) {
                        $items = explode(",", $curRowData[$mapKey]);
                        $attrList = [];
                        foreach ($items as $item) {
                            if (array_key_exists($item, $this->attrSetIdToName)) {
                                $attrList[] = $this->attrSetIdToName[$item];
                            } else {
                                $attrList[] = "";
                            }
                        }
                        $rowValues[$mapValue] = implode(",", $attrList);
                    } else {
                        if ($mapKey == "category_ids") {
                            $arrayTempCat = [];
                            $arrayCats = explode(" | ", $curRowData[$mapKey]);
                            foreach ($arrayCats as $arrayCat) {
                                if (isset($catlistHtml[$arrayCat])) {
                                    $arrayTempCat[] = $catlistHtml[$arrayCat];
                                }
                            }
                            $curRowData[$mapKey] = implode(" | ", $arrayTempCat);
                        }
                        if ($mapKey == "product_url") {
                            if ($curRowData[$mapKey] == "") {
                                $mapKey = "product_url_2";
                            }
                        }
                        if (array_key_exists($mapKey, $curRowData)) {
                            $rowValues[$mapValue] = $curRowData[$mapKey];
                        } else {
                            $rowValues[$mapValue] = "";
                        }
                    }
                }
                $bSuccess = $this->file->writeRow($rowValues);
                if (!$bSuccess) {
                    $this->logger->info('Failed to write to data feed file: ' . $filename);
                    $this->file->close();
                    return false;
                }
                $bDone = true;
                // Collect last entity Id and generate new minEntityId param
                $minEntityId = $curRow->getEntityId() + 1;
            }
            $collection->clear();
            //test code remove later on
            $this->logger->info('Finished processing batch ' . $currentPage);
        } while ($currentPage <= $pages);

        // Check if collection is empty
        if (count($collection) == 0) {
            $this->logger->info('Empty collection for website id ' . $websiteId);
            $bDone = true;
        }
        $bSuccess = $this->file->close();
        if (!$bSuccess) {
            $this->logger->info('Failed to close data feed file: ' . $filename);
            return false;
        }
    }

    /**
     * Add custom attributes selected by magento admin to query
     *
     * @param $collection Collection of data which will be spit out as feed
     * @param $customAttribs Comma separated list of attribute codes
     * @param $fieldMap Reference to fieldmap where attribute codes should also be added
     * @return $collection
     */
    protected function addCustomAttributes($collection, $customAttribs, &$fieldMap)
    {
        $this->logger->info("Adding custom attributes include in query: {$customAttribs}");
        //Check if we have any custom attributes
        if (!empty(trim($customAttribs))) {
            foreach (explode(',', $customAttribs) as $curAttrib) {
                $curAttrib = trim($curAttrib);
                $_attribute = $collection->getAttribute($curAttrib);
                if ($_attribute === false) {
                    throw new \Exception("Attribte not found: {$curAttrib}");
                }
                $this->logger->info("Adding attribute to query: {$curAttrib}");
                if ($_attribute->getFrontendInput() == "select" || $_attribute->getFrontendInput() == "multiselect") {
                    $this->logger->info("Note - Attribute needs translation");
                    $this->optionValueMap['custom_' . $curAttrib] = true;
                }
                // Attribute to select
                $collection
                    ->addExpressionAttributeToSelect('custom_' . $curAttrib, "{{" . $curAttrib . "}}", $curAttrib)
                    ->addAttributeToSelect($curAttrib);
                // Attribute to map
                $fieldMap['custom_' . $curAttrib] = 'custom_' . $curAttrib;
            }
        }
        return $collection;
    }
}
