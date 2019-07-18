<?php
namespace Reflektion\Catalogexport\Model\Layer\Filter;

class Attribute extends \Magento\CatalogSearch\Model\Layer\Filter\Attribute
{
    /**
     * @var array
     */
    protected $rfkFilters;

    /**
     * @var \Magento\Framework\HTTP\Adapter\Curl
     */
    protected $curl;

    /**
     * @var \Reflektion\Catalogexport\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    protected $queryFactory;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Framework\Filter\StripTags $tagFilter
     * @param \Magento\Framework\HTTP\Adapter\Curl $curl
     * @param \Reflektion\Catalogexport\Helper\Data $dataHelper
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Search\Model\QueryFactory $queryFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \Magento\Framework\HTTP\Adapter\Curl $curl,
        \Reflektion\Catalogexport\Helper\Data $dataHelper,
        \Reflektion\Catalogexport\Logger\Logger $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Search\Model\QueryFactory $queryFactory,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $tagFilter,
            $data
        );
        $this->curl = $curl;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->queryFactory = $queryFactory;
        $this->rfkFilters = $this->getRfkFilters();
    }

    /**
     * Initialize filter items
     *
     * @return  \Magento\Catalog\Model\Layer\Filter\AbstractFilter
     */
    protected function _initItems()
    {
        $attribute = $this->getAttributeModel();
        $attributeCode = $attribute->getAttributeCode();
        if (!empty($this->rfkFilters)) {
            if(!in_array($attributeCode, $this->rfkFilters)) {
                return $this;
            }
        }
        $data = $this->_getItemsData();
        $items = [];
        foreach ($data as $itemData) {
            $items[] = $this->_createItem($itemData['label'], $itemData['value'], $itemData['count']);
        }
        $this->_items = $items;

        return $this;
    }

    protected function getRfkFilters()
    {
        $fpsSearch = $this->dataHelper->getConfig("reflektion_datafeeds/search/searchflag");
        if ($fpsSearch != 'enabled'  || $this->queryFactory->get()->getQueryText() == '') {
            return '';
        } else {
            $apikey = $this->dataHelper->getConfig('reflektion_datafeeds/general/apikey');
            $apiUrl = $this->dataHelper->getConfig('reflektion_datafeeds/search/apiurl');
            $apiData = urlencode('{"facet":{"all":true,"max":0}}');
            $apiUrl = $apiUrl . '?data=' . $apiData;

            $this->curl->setConfig(
                [ 'timeout' => 15 ]    //Timeout in no of seconds
            );
            $this->curl->write(
                'get',
                $apiUrl,
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
            if ($ini == 0)
                return '';
            $ini += strlen($start) - 1;
            $len = strpos($data, $ini) - $ini + 1;
            $data = substr($data, $ini);
            $sectionJson = $this->jsonHelper->jsonDecode($data);
            $allFacetIds = $sectionJson['facet'];
            foreach ($allFacetIds as $id => $array) {
                $allIds[] = $id;
            }
            $replacements = array();
            $listValues = $this->dataHelper->getConfig('reflektion_datafeeds/general/addfiltermap');
            if ($listValues) {
                $listValues = unserialize($listValues);

                if (is_array($listValues)) {

                    foreach ($listValues as $listValue) {
                        $replacements[$listValue['rfk_attr']] = $listValue['mag_attr'];
                    }
                }
            }
            $attributesToShow = [];
            foreach ($allIds as $key => $value) {
                if (isset($replacements[$value])) {
                    $attributesToShow[] = $replacements[$value];
                }
            }

            return $attributesToShow;
        }
    }

}