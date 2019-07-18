<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    12/01/18
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Validate and save cron job frequency
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class Search extends \Magento\Framework\App\Config\Value
{
    const SEARCH_VERSION = 3;
    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $configValueFactory;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Config\Model\Config
     */
    protected $coreConfig;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configResource;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;
    /**
     * @var \Magento\Framework\HTTP\Adapter\Curl
     */
    protected $curl;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Config\Model\Config $coreConfig
     * @param \Magento\Config\Model\ResourceModel\Config $configResource
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\HTTP\Adapter\Curl $curl
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Config\Model\Config $coreConfig,
        \Magento\Config\Model\ResourceModel\Config $configResource,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\HTTP\Adapter\Curl $curl,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configValueFactory = $configValueFactory;
        $this->messageManager = $messageManager;
        $this->coreConfig = $coreConfig;
        $this->configResource = $configResource;
        $this->jsonHelper = $jsonHelper;
        $this->curl = $curl;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}. In addition, it sets status 'invalidate' for config caches
     *
     * @return $this
     */
    public function afterSave()
    {
        $scope = $this->getData('scope');
        $scopeId = $this->getData('scope_id');
        if ($this->isValueChanged()) {
            $this->messageManager->addNotice("Clear cache for the changes to affect");
        }
        $allfeeden = $this->getData('groups/general/fields/allfeedsenabled/value');
        if (empty($allfeeden)) {
            $allfeeden = $this->_config->getValue(
                'reflektion_datafeeds/general/allfeedsenabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        try {
            $ckey = $this->getData('groups/general/fields/customerid/value');
            //code for adding js based on environment
            $testOrLive = $this->getData('groups/general/fields/testorlive/value');
            $staging = '';
            $inittest = '';
            if ($testOrLive == 'live') {
                $apikey = $this->getData('groups/general/fields/apikey/value');
            } elseif ($testOrLive == 'staging') {
                $apikey = $this->getData('groups/general/fields/apikeystaging/value');
                $staging = '-staging';
                $inittest = '?cv=staging';
            } elseif ($testOrLive == 'uat') {
                $apikey = $this->getData('groups/general/fields/apikeyuat/value');
                $staging = '-uat';
            } else {
                $apikey = $this->getData('groups/general/fields/apikeytest/value');
                $staging = '-staging';
                $inittest = '?cv=test';
            }
            if (empty($apikey) || empty($ckey)) {
                $this->messageManager->addError('Empty Customer Key and/or API Key');
            } else {
                $expCkey = explode("-", $ckey);
                $searchHost = 'https://apis' . $staging . '.rfksrv.com/search-rec/' . $ckey . '/' . self::SEARCH_VERSION;
                $rfkJs = "//" . $expCkey[1] . "-prod.rfksrv.com/rfk/js/" . $ckey . "/init.js" . $inittest;
                if ($testOrLive == 'uat') {
                    $rfkJs = "//initjs." . $testOrLive . ".rfksrv.com/rfk/js/" . $ckey . "/init.js";
                }
                $this->apiHealthCheckup($searchHost, $apikey);
                $this->configResource->saveConfig(
                    'reflektion_datafeeds/search/apiurl',
                    $searchHost,
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                    $scopeId
                );
                $this->configResource->saveConfig(
                    'reflektion_datafeeds/search/rfkjs',
                    $rfkJs,
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                    $scopeId
                );
            }
        } catch (\Exception $e) {
            throwException($e);
        }

        return parent::afterSave();
    }

    public function apiHealthCheckup($searchHost, $apikey)
    {
        //Get product data
        $apiData = urlencode(
            '{"query":{"keyphrase":[""]},"content":{"product":{"field":{"value":["sku"]}}},"n_item":10}'
        );
        $feedUrl = $searchHost . '?data=' . $apiData;
        $data = $this->getCurlData($feedUrl, $apikey);
        if (isset($data['content']['product']['value'])) {
            $products = $data['content']['product']['value'];
        } else {
            $products = '';
        }
        //Get facet codes
        $apiData = urlencode('{"facet":{"all":true,"max":0}}');
        $feedUrl = $searchHost . '?data=' . $apiData;
        $sectionJson = $this->getCurlData($feedUrl, $apikey);
        if (isset($sectionJson['facet'])) {
            $allFacetIds = $sectionJson['facet'];
        } else {
            $allFacetIds = '';
        }
        if (!is_array($products) || !is_array($allFacetIds)) {
            $this->messageManager->addError(
                'Reflektion API health check failed. Invalid Customer Key and/or API Key'
            );
        }
    }

    public function getCurlData($feedUrl, $apikey)
    {
        $this->curl->setConfig(
            [ 'timeout' => 15 ]    //Timeout in no of seconds
        );
        $this->curl->write(
            'get',
            $feedUrl,
            '1.1',
            ['Authorization:' . $apikey],
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
        $len = strpos($data, $ini) - $ini + 1;
        $data = substr($data, $ini);
        return $this->jsonHelper->jsonDecode($data);
    }
}
