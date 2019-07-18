<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Reflektion\Catalogexport\Model\Search;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Adapter\Mysql\ConditionManager;
use Magento\Framework\Search\Adapter\Mysql\IndexBuilderInterface;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\CatalogSearch\Model\Search\TableMapper;

/**
 * Build base Query for Index.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexBuilder implements IndexBuilderInterface
{
    /**
     * @var Resource
     */
    private $resource;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var StoreManagerInterface
     * @deprecated
     */
    private $storeManager;

    /**
     * @var IndexScopeResolver
     */
    private $scopeResolver;

    /**
     * @var ConditionManager
     */
    private $conditionManager;

    /**
     * @var TableMapper
     */
    private $tableMapper;

    /**
     * @var ScopeResolverInterface
     */
    private $dimensionScopeResolver;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var \Magento\Framework\HTTP\Adapter\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    protected $queryFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Reflektion\Catalogexport\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param ScopeConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param ConditionManager $conditionManager
     * @param IndexScopeResolver $scopeResolver
     * @param TableMapper $tableMapper
     * @param ScopeResolverInterface $dimensionScopeResolver
     * @param \Magento\Framework\HTTP\Adapter\Curl $curl
     * @param \Magento\Search\Model\QueryFactory $queryFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Reflektion\Catalogexport\Helper\Data $dataHelper
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     */
    public function __construct(
        ResourceConnection $resource,
        ScopeConfigInterface $config,
        StoreManagerInterface $storeManager,
        ConditionManager $conditionManager,
        IndexScopeResolver $scopeResolver,
        TableMapper $tableMapper,
        ScopeResolverInterface $dimensionScopeResolver,
        \Magento\Framework\HTTP\Adapter\Curl $curl,
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Reflektion\Catalogexport\Helper\Data $dataHelper,
        \Reflektion\Catalogexport\Logger\Logger $logger
    ) {
        $this->resource = $resource;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->conditionManager = $conditionManager;
        $this->scopeResolver = $scopeResolver;
        $this->tableMapper = $tableMapper;
        $this->dimensionScopeResolver = $dimensionScopeResolver;
        $this->curl = $curl;
        $this->queryFactory = $queryFactory;
        $this->dataHelper = $dataHelper;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
    }

    /**
     * Build index query
     *
     * @param RequestInterface $request
     * @return Select
     * @throws \LogicException
     */
    public function build(RequestInterface $request)
    {
        $searchIndexTable = $this->scopeResolver->resolve($request->getIndex(), $request->getDimensions());

        //fetch data from rfk server
        $result = $this->getSearchData();
        //Get entity_id of all the products from result set
        $ids = [];
        if(!empty($result)) {
            $ids = array_keys($result);
        }
        $ids = "'" . implode("','", $ids) . "'";
        $select = $this->resource->getConnection()->select()
            ->from(
                ['search_index' => $searchIndexTable],
                ['entity_id' => 'entity_id']
            )
            ->joinLeft(
                ['cea' => $this->resource->getTableName('catalog_eav_attribute')],
                'search_index.attribute_id = cea.attribute_id',
                []
            );
        $select->where("search_index.entity_id IN({$ids})");

        $select = $this->tableMapper->addTables($select, $request);

        $select = $this->processDimensions($request, $select);

        $isShowOutOfStock = $this->config->isSetFlag(
            'cataloginventory/options/show_out_of_stock',
            ScopeInterface::SCOPE_STORE
        );
        if ($isShowOutOfStock === false) {
            $select->joinLeft(
                ['stock_index' => $this->resource->getTableName('cataloginventory_stock_status')],
                'search_index.entity_id = stock_index.product_id'
                . $this->resource->getConnection()->quoteInto(
                    ' AND stock_index.website_id = ?',
                    $this->getStockConfiguration()->getDefaultScopeId()
                ),
                []
            );
            $select->where('stock_index.stock_status = ?', Stock::STOCK_IN_STOCK);
        }

        return $select;
    }

    /**
     * @return StockConfigurationInterface
     *
     * @deprecated
     */
    private function getStockConfiguration()
    {
        if ($this->stockConfiguration === null) {
            $this->stockConfiguration = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\CatalogInventory\Api\StockConfigurationInterface::class);
        }
        return $this->stockConfiguration;
    }

    /**
     * Add filtering by dimensions
     *
     * @param RequestInterface $request
     * @param Select $select
     * @return \Magento\Framework\DB\Select
     */
    private function processDimensions(RequestInterface $request, Select $select)
    {
        $dimensions = $this->prepareDimensions($request->getDimensions());

        $query = $this->conditionManager->combineQueries($dimensions, Select::SQL_OR);
        if (!empty($query)) {
            $select->where($this->conditionManager->wrapBrackets($query));
        }

        return $select;
    }

    /**
     * @param Dimension[] $dimensions
     * @return string[]
     */
    private function prepareDimensions(array $dimensions)
    {
        $preparedDimensions = [];
        foreach ($dimensions as $dimension) {
            if ('scope' === $dimension->getName()) {
                continue;
            }
            $preparedDimensions[] = $this->conditionManager->generateCondition(
                $dimension->getName(),
                '=',
                $this->dimensionScopeResolver->getScope($dimension->getValue())->getId()
            );
        }

        return $preparedDimensions;
    }

    /**
     * Gets list of skus from reklektion based on search term
     * @return array|string
     */
    protected function getSearchData()
    {
        if ($this->dataHelper->getConfig('reflektion_datafeeds/search/searchflag') == 'enabled') {
            $this->logger->info("Starting to get skus to filter");
            $text = $this->queryFactory->get()->getQueryText();
            $apiUrl = $this->dataHelper->getConfig("reflektion_datafeeds/search/apiurl");
            $testOrLive = $this->dataHelper->getConfig("reflektion_datafeeds/general/testorlive");
            if ($testOrLive == 'live') {
                $apikey = $this->dataHelper->getConfig('reflektion_datafeeds/general/apikey');
            } elseif ($testOrLive == 'staging') {
                $apikey = $this->dataHelper->getConfig('reflektion_datafeeds/general/apikeystaging');
            } elseif ($testOrLive == 'uat') {
                $apikey = $this->dataHelper->getConfig('reflektion_datafeeds/general/apikeyuat');
            } else {
                $apikey = $this->dataHelper->getConfig('reflektion_datafeeds/general/apikeytest');
            }
            $this->curl->setConfig(
                [ 'timeout' => 15 ]    //Timeout in no of seconds
            );
            //Get product data
            $apiData = urlencode(
                '{"query":{"keyphrase":["' . $text . '"]},'
                . '"content":{"product":{"field":{"value":["sku"]}}},"n_item":10000}'
            );
            $feedUrl = $apiUrl . '?data=' . $apiData;
            $this->curl->write(
                'get',
                $feedUrl,
                '1.1',
                ['x-api-key:' . $apikey],
                'Content-Type: application/json; charset=UTF-8'
            );
            $data = $this->curl->read();
            if ($data === false) {
                return false;
            }
            $start = "{";
            $data = ' ' . $data;
            $ini = strpos($data, $start);
            if ($ini == 0) {
                return '';
            }
            $ini += strlen($start) - 1;
            $len = strpos($data, $ini) - $ini + 1;
            $data = substr($data, $ini);
            $data = $this->jsonHelper->jsonDecode($data);
            $products = $data['content']['product']['value'];
            foreach ($products as $product) {
                $productIds[] = $product['sku'];
            }
            $productImplode = "'" . implode("','", $productIds) . "'";

            $myqry = $this->resource->getConnection()->select()
                ->from(
                    ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                    ['entity_id' => 'entity_id']
                )
                ->where(
                    "cpe.sku IN({$productImplode})"
                );
            $result = $this->resource->getConnection()->fetchAssoc($myqry);
            return $result;
        }
        return '';
    }
}
