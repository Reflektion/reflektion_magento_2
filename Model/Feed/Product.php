<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    18/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Product attributes operations
 */

namespace Reflektion\Catalogexport\Model\Feed;

class Product extends Base
{
    // Magento product fields to Reflektion Product Feed
    private $fieldMap = [
        'sku' => 'Id',
        'name' => 'Name',
        'description' => 'Description',
        'product_url' => 'Product Url',
        'product_url_2' => 'Product Url 2',
        'product_url_3' => 'Product Url 3',
        'image_url' => 'Image Url',
        'thumbnail' => 'Thumbnail',
        'small_image' => 'Small Image',
        'additional_images' => 'Additional Images',
        'category_ids' => 'Breadcrumbs - Category Ids',
        'price' => 'Price',
        'final_price' => 'Special Price',
        'status' => 'Status',
        'adj_qty' => 'Inventory Quantity',
        'is_in_stock' => 'Inventory Status',
        'type_id' => 'Product Type',
        'visibility' => 'Visibility',
        'parent_id' => 'Parent Id',
        'parent_id_list' => 'parent_id_list',
        'parent_id_relation' => 'parent_id_relation',
        'parent_id_relation_list' => 'parent_id_relation_list',
        'entity_id' => 'Prod Id',
    ];
    protected $attrSetIdToNameForSwatch = [];
    protected $swatchDetail = [];

    public function getFieldMap($websiteId = null)
    {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        $enRating = $this->scopeConfig->getValue(
            'reflektion_datafeeds/feedsenabled/product_rating',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        $enDirectPriceExport = $this->scopeConfig->getValue(
            'reflektion_datafeeds/feedsenabled/product_price',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        if ($enRating == 'enabled') {
            $this->fieldMap['review_count'] = 'review_count';
            $this->fieldMap['review_average'] = 'review_average';
        }
        if ($enDirectPriceExport == 'enabled') {
            $this->fieldMap['price_2'] = 'Price 2';
            $this->fieldMap['final_price_2'] = 'Special Price 2';
        }
        return $this->fieldMap;
    }

    // File name key
    public function getFileNameKey()
    {
        return 'product';
    }

    /**
     * Build collection to do query
     *
     * @param $websiteId Which website to query for collection
     */
    public function getFeedCollection($websiteId)
    {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        $iDefaultStoreId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $collection = $this->objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        $collection
            ->addAttributeToSelect('name');

        $collection
            ->addAttributeToSelect('description');

        $collection
            ->addExpressionAttributeToSelect(
                'visibility', '{{visibility}}', 'visibility'
            );
        if ($this->scopeConfig->getValue(
                'reflektion_datafeeds/feedsenabled/store_level',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            ) == 'enabled') {
            $collection->addStoreFilter($iDefaultStoreId);
        } else {
            $collection->addWebsiteFilter($websiteId);
        }
        $enDirectPriceExport = $this->scopeConfig->getValue(
            'reflektion_datafeeds/feedsenabled/product_price',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        if($enDirectPriceExport == 'enabled') {
            $prodPriceTable = $collection->getResource()->getTable('catalog_product_entity_decimal');
            $eavAttribute = $collection->getResource()->getTable('eav_attribute');
            $catalogProductIndexPrice = $collection->getResource()->getTable('catalog_product_index_price');
            if ($this->moduleManager->isOutputEnabled('Magento_Enterprise')) {
                $decimalTableCol = 'row_id';
            } else {
                $decimalTableCol = 'entity_id';
            }
            $collection->getSelect()
                ->columns(["price_2" => "(select value " .
                    "  from " .
                    "    {$prodPriceTable} t1 " .
                    "  join {$eavAttribute} t2 on t1.attribute_id = t2.attribute_id " .
                    "  where  " .
                    "    t1.{$decimalTableCol} = e.entity_id AND" .                  
                    " t2.attribute_code= 'price'" .
                    "  limit 1)"]);
            $collection->getSelect()
                ->columns(["final_price_2" => "(select value " .
                    "  from " .
                    "    {$prodPriceTable} t1 " .
                    "  join {$eavAttribute} t2 on t1.attribute_id = t2.attribute_id " .
                    "  where  " .
                    "    t1.{$decimalTableCol} = e.entity_id AND" .                 
                    " t2.attribute_code= 'special_price'" .
                    "  limit 1)"]);
            $collection->getSelect()
                ->columns(["price" => "(select price " .
                    "  from " .
                    "    {$catalogProductIndexPrice} t1 " .
                    "  where  " .
                    "    t1.entity_id = e.entity_id AND" .
                    " t1.website_id = $websiteId " .                    
                    "  limit 1)"]);
            $collection->getSelect()
                ->columns(["final_price" => "(select final_price " .
                    "  from " .
                    "    {$catalogProductIndexPrice} t1 " .
                    "  where  " .
                    "    t1.entity_id = e.entity_id AND" .
                    " t1.website_id = $websiteId " .                    
                    "  limit 1)"]);
        } else {
            $collection
                ->addPriceData(null, $websiteId); //Have to use this version so you can set the website id
        }
        $cataloginv = $collection->getResource()->getTable('cataloginventory_stock_item');
        $collection->joinTable(
            ['at_qty' => $cataloginv],
            'product_id=entity_id',
            ['qty' => 'qty', 'is_in_stock' => 'is_in_stock'],
            '{{table}}.stock_id=1',
            'left'
        );
        $coreSuperLink = $collection->getResource()->getTable('catalog_product_super_link');
        $catalogProductRelation = $collection->getResource()->getTable('catalog_product_relation');
        $catalogProduct = $collection->getResource()->getTable('catalog_product_entity');

        if ($this->moduleManager->isOutputEnabled('Magento_Enterprise')) {

            $collection->getSelect()
                    ->columns(["parent_id" => "(select " .
                    "    catalogProduct.entity_id " .
                    "  from " .
                    "    {$coreSuperLink} link " .
                    "  join {$catalogProduct} catalogProduct on catalogProduct.row_id = link.parent_id" .
                    "  where  " .
                    "    link.product_id = e.entity_id " .
                    "  limit 1)"]);

            $collection->getSelect()
                ->columns(["parent_id_list" => "(select group_concat(distinct" .
                    "    catalogProduct.entity_id "
                    . " separator ',') " .
                    "  from " .
                    "    {$coreSuperLink} link " .
                    "  join {$catalogProduct} catalogProduct on catalogProduct.row_id = link.parent_id" .
                    "  where  " .
                    "    link.product_id = e.entity_id" .
                    "  limit 1)"]);

            $collection->getSelect()
                ->columns(["parent_id_relation" => "(select " .
                    "    catalogProduct.entity_id " .
                    "  from " .
                    "    {$catalogProductRelation} link " .
                    "  join {$catalogProduct} catalogProduct on catalogProduct.row_id = link.parent_id" .
                    "  where  " .
                    "    link.child_id = e.entity_id " .
                    "  limit 1)"]);

            $collection->getSelect()
                ->columns(["parent_id_relation_list" => "(select group_concat(distinct" .
                    "    catalogProduct.entity_id "
                    . " separator ',') " .
                    "  from " .
                    "    {$catalogProductRelation} link " .
                    "  join {$catalogProduct} catalogProduct on catalogProduct.row_id = link.parent_id" .
                    "  where  " .
                    "    link.child_id = e.entity_id " .
                    "  limit 1)"]);
            
        } else {
            $collection->getSelect()
                ->columns(["parent_id_list" => "(select group_concat(distinct" .
                    "    link.parent_id "
                    . " separator ',') " .
                    "  from " .
                    "    {$coreSuperLink} link " .
                    "  where  " .
                    "    link.product_id = e.entity_id" .
                    "  limit 1)"]);

            $collection->getSelect()
                ->columns(["parent_id" => "(select " .
                    "    parent_id " .
                    "  from " .
                    "    {$coreSuperLink} link " .
                    "  where  " .
                    "    link.product_id = e.entity_id " .
                    "  limit 1)"]);

            $collection->getSelect()
                ->columns(["parent_id_relation" => "(select " .
                    "    parent_id " .
                    "  from " .
                    "    {$catalogProductRelation} link " .
                    "  where  " .
                    "    link.child_id = e.entity_id " .
                    "  limit 1)"]);

            $collection->getSelect()
                ->columns(["parent_id_relation_list" => "(select group_concat(distinct" .
                    "    link.parent_id "
                    . " separator ',') " .
                    "  from " .
                    "    {$catalogProductRelation} link " .
                    "  where  " .
                    "    link.child_id = e.entity_id " .
                    "  limit 1)"]);
        }

        $prodCatTable = $collection->getResource()->getTable('catalog_category_product');
        $collection->getSelect()
            ->columns(["category_ids" => "(select group_concat(distinct" .
                "    category_id "
                . " separator ' | ') " .
                "  from " .
                "    {$prodCatTable} pc " .
                "  where  " .
                "    pc.product_id = e.entity_id " .
                "  )"]);
        $enRating = $this->scopeConfig->getValue(
            'reflektion_datafeeds/feedsenabled/product_rating',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );;
        if ($enRating == 'enabled') {
            $reviewSummary = $collection->getResource()->getTable('review_entity_summary');
            $collection->getSelect()
                    ->columns(array(
                        'review_count' =>
                        "  (select " .
                        "    rv.reviews_count " .
                        "  from " .
                        "    {$reviewSummary} rv " .
                        "  where  " .
                        "    rv.entity_pk_value = e.entity_id AND" .
                        " rv.store_id = $iDefaultStoreId" .
                        "  limit 1) "
                            ,));
            $collection->getSelect()
                    ->columns(array(
                        'review_average' =>
                        "  (select " .
                        "    rv.rating_summary " .
                        "  from " .
                        "    {$reviewSummary} rv " .
                        "  where  " .
                        "    rv.entity_pk_value = e.entity_id AND" .
                        " rv.store_id = $iDefaultStoreId" .
                        "  limit 1) "
                            ,));
        }
        $baseUrl = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getBaseUrl();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        if($storeManager->getStore()->isFrontUrlSecure()){
            $baseUrl = preg_replace("/^http:/i", "https:", $baseUrl);
        }
        $catalogSuffix = $this->scopeConfig->getValue(
            'catalog/seo/product_url_suffix',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        $mediaUrl = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
        $baseUrlWp = preg_replace('#^https?:#', '', $mediaUrl); // base url without http: or https:
        $coreRewriteTable = $collection->getResource()->getTable('url_rewrite');
        
        $collection->getSelect()
            ->columns(["product_url" => "(select " .
                " concat('{$baseUrl}', url.request_path) " .
                " from " .
                " {$coreRewriteTable} url " .
                " where  " .
                " url.target_path = concat('catalog/product/view/id/',e.entity_id) AND" .
                " url.store_id = $iDefaultStoreId " .
                " limit 1)"]);

         $collection
            ->addExpressionAttributeToSelect('product_url_2', "if({{url_key}} <> '', " .
                "  concat('{$baseUrl}', {{url_key}}, '{$catalogSuffix}'), " .
                "  '')", 'url_key');

         $collection->getSelect()
            ->columns(["product_url_3" => "(select " .
                " concat('{$baseUrl}', url.request_path) " .
                " from " .
                " {$coreRewriteTable} url " .
                " where  " .
                " target_path = concat('catalog/product/view/id/', " .
                "IF(parent_id_relation IS NULL , e.entity_id, parent_id_relation)) AND" .
                " url.store_id = $iDefaultStoreId " .
                " limit 1)"]);

        /*$collection
            ->addExpressionAttributeToSelect(
                'product_url', "if({{entity_id}} <> '', " .
                "  concat('{$baseUrl}', 'catalog/product/view/id/' ,{{entity_id}}), " .
                "  '')", 'entity_id'
        );*/


        $collection
            ->addExpressionAttributeToSelect('cur_status', "{{status}}", 'status');

        $collection
            ->addExpressionAttributeToSelect(
                'image_url', "if({{image}} <> '', " .
                "  concat('{$baseUrlWp}', 'catalog/product' ,{{image}}), " .
                "  '')", 'image'
        );
        $collection
            ->addExpressionAttributeToSelect(
                'small_image', "if({{small_image}} <> '', " .
                "  concat('{$baseUrlWp}', 'catalog/product' ,{{small_image}}), " .
                "  '')", 'small_image'
        );
        $collection
            ->addExpressionAttributeToSelect(
                'thumbnail', "if({{thumbnail}} <> '', " .
                "  concat('{$baseUrlWp}', 'catalog/product' ,{{thumbnail}}), " .
                "  '')", 'thumbnail'
        );


        $collection
            ->addExpressionAttributeToSelect(
                'adj_qty',
                "if({{type_id}} = 'simple', " .
                "at_qty.qty, " .
                "if (at_qty.is_in_stock=1, " .
                "if (at_qty.qty>0, " .
                "at_qty.qty, at_qty.is_in_stock)," .
                "at_qty.is_in_stock))",
                'type_id'
            );

        // Custom attributes to feed
        $customAttribs = $this->scopeConfig->getValue(
            'reflektion_datafeeds/feedsenabled/product_attributes',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        $collection = $this->addCustomAttributes($collection, $customAttribs, $this->fieldMap);
        return $collection;
    }
}
