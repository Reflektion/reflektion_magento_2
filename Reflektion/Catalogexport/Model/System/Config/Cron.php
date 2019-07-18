<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Validate and save cron job frequency
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class Cron extends \Magento\Framework\App\Config\Value
{
    const CUSTOM_OPTION_STRING_PATH = 'reflektion_datafeeds/configurable_cron/frequency';
    protected $configValueFactory;
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configValueFactory = $configValueFactory;
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
        $time[] = $this->getData('groups/configurable_cron/fields/frequency_min/value'); //$minute
        $time[] = $this->getData('groups/configurable_cron/fields/frequency_hr/value'); //$hour
        $time[] = $this->getData('groups/configurable_cron/fields/frequency_day/value'); //$day
        $time[] = $this->getData('groups/configurable_cron/fields/frequency_month/value'); //$month
        $time[] = $this->getData('groups/configurable_cron/fields/frequency_wk/value'); //$weekday
        try {
            foreach ($time as $val) {
                if (preg_match('/[^*,\/0-9]/i', $val)) {
                    throw new \Exception();
                }
            }
            $cronExprString = join(' ', $time);
            $this->configValueFactory->create()->load(
                self::CUSTOM_OPTION_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CUSTOM_OPTION_STRING_PATH
            )->setScope(
                $scope
            )->setScopeId(
                $scopeId
            )->save();
        } catch (\Exception $e) {
            throw new \Exception(__('Unable to save the cron expression.'));
        }

        return parent::afterSave();
    }
}
