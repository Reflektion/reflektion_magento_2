<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    01/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Category feed export
 */

namespace Reflektion\Catalogexport\Model\Feed;

use Magento\Framework\Model\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\ObjectManagerInterface;
use Reflektion\Catalogexport\Helper\Csvfile;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\Data\Collection as DataCollection;

class Category extends Base
{
    // Field mapping
    private $fieldMap = [
        'id' => 'Id',
        'name' => 'Name',
        'breadcrumb' => 'Breadcrumb Name',
        'id_breadcrumb' => 'Breadcrumb Ids',
        'url' => 'Url',
        'url_2' => 'Url 2',
        'url_3' => 'Url 3',
        'url_key' => 'Url Key',
        'image' => 'Image',
        'meta_title' => 'meta_title',
        'meta_description' => 'meta_description',
        'meta_keywords' => 'meta_keywords',
    ];
    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $categoryHelper;
    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $category;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    protected $collection;
    /**
     * @param \Reflektion\Catalogexport\Helper\Analytics
     */
    protected $analyticsHelper;

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
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param \Reflektion\Catalogexport\Helper\Data $rfkHelper
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $collection
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ObjectManagerInterface $objectManager,
        Csvfile $csvfile,
        DirectoryList $directoryList,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Reflektion\Catalogexport\Helper\Data $rfkHelper,
        \Magento\Catalog\Model\Category $category,
        \Reflektion\Catalogexport\Helper\Analytics $analyticsHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        Collection $collection
    ) {

        parent::__construct(
            $context,
            $storeManager,
            $scopeConfig,
            $logger,
            $objectManager,
            $csvfile,
            $directoryList,
            $rfkHelper,
            $moduleManager,
            $registry
        );
        $this->categoryHelper = $categoryHelper;
        $this->category = $category;
        $this->analyticsHelper = $analyticsHelper;
        $this->collection = $collection;
    }

    public function getFieldMap()
    {
        return $this->fieldMap;
    }

    // File name key
    public function getFileNameKey()
    {
        return 'category';
    }

    /**
     * Build collection to do query
     *
     * @param int|string $websiteId Which website to query for collection
     * @return $collection
     */
    public function getFeedCollection($websiteId)
    {
        $rootCategoryId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getRootCategoryId();
        $rootCategory = $this->category->load($rootCategoryId);
        $rootCategoryPath = $rootCategory->getPath();
        $catCollection = $this->collection
            ->addIsActiveFilter()
            ->addUrlRewriteToResult()
            ->addAttributeToSelect('meta_title')
            ->addAttributeToSelect('meta_description')
            ->addAttributeToSelect('meta_keywords')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('url_key')
            ->addFieldToFilter('path', ['like' => $rootCategoryPath . '/%'])
            ->addFieldToFilter('url_key', ['like' => '_%'])
            ->addAttributeToFilter('include_in_menu', 1);

        $catData = [];
        $i = 0;
        
        $catEnabled = $this->analyticsHelper->getConfig("reflektion_datafeeds/categorypages/fpsenabled");
        $remove = "";
        if ($catEnabled == 'enabled') {
            $option = $this->analyticsHelper->getConfig('reflektion_datafeeds/categorypages/fpsoption');
            $subDText = $this->analyticsHelper->getConfig('reflektion_datafeeds/categorypages/subdomaintext');
            $uriText = $this->analyticsHelper->getConfig('reflektion_datafeeds/categorypages/uritext');
            if ($option == \Reflektion\Catalogexport\Plugin\Category::CATEGORY_RFK_SUBDOMAIN) {
                if ($subDText != "") {
                    $remove = $subDText;
                }
            } elseif ($option == \Reflektion\Catalogexport\Plugin\Category::CATEGORY_RFK_URI) {
                if ($uriText != "") {
                    $remove = $uriText . '/';
                }

            } elseif ($option == \Reflektion\Catalogexport\Plugin\Category::CATEGORY_RFK_1) {
                $remove = '?rfk=1';
            }
        }
        $collection = $this->objectManager->create('\Magento\Framework\Data\Collection');
        $catlist = $this->rfkHelper->getTreeCategories($rootCategoryId);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $baseUrl = $storeManager->getStore()->getBaseUrl();
        $coreRewriteTable = $catCollection->getResource()->getTable('url_rewrite');
        $catCollection->getSelect()
            ->columns(["cat_seo_url" => "(select " .
                " concat('{$baseUrl}', url.request_path) " .
                " from " .
                " {$coreRewriteTable} url " .
                " where  " .
                " url.target_path = concat('catalog/category/view/id/',e.entity_id) " .
                " limit 1)"]);

        foreach ($catCollection as $category) {
            try {
                $catData[$i]['id'] = $category->getId();
                $catData[$i]['name'] = $catlist['name_' . $category->getId()];
            	$catData[$i]['meta_title'] = $category->getMetaTitle();
            	$catData[$i]['meta_description'] = $category->getMetaDescription();
            	$catData[$i]['meta_keywords'] = $category->getMetaKeywords();
                $catData[$i]['breadcrumb'] = $catlist[$category->getId()];
                $catData[$i]['id_breadcrumb'] = $catlist['id_breadcrumb_' . $category->getId()];
                $url = str_replace($remove, "", $this->categoryHelper->getCategoryUrl($category));
                $url = str_replace($baseUrl, "", $url);
                $catData[$i]['url'] = $url;
                $catData[$i]['url_2'] = $category->getRequestPath();
                $catData[$i]['url_3'] = $category->getCatSeoUrl();
                $catData[$i]['url_key'] = $category->getUrlKey();
                $catData[$i]['image'] = $category->getImageUrl() ? $category->getImageUrl() : '';
                $newItem = $collection->getNewEmptyItem();
                $newItem->setData($catData[$i]);
                $collection->addItem($newItem);
                $i++;
            } catch (\Exception $e) {
                $this->logger->warning("Error during Reflektion Category Feed export: " . $e->getMessage());
            }
        }
        return $collection;
    }

}
