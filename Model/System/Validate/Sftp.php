<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    03/10/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Validate JSON syntax
 */
namespace Reflektion\Catalogexport\Model\System\Validate;

class Sftp extends \Magento\Framework\App\Config\Value
{
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        $scope = $this->getScope();
        if ($scope == "websites") {
            $allfeedsenabled = $this->getValue();
            $hostname = trim($this->getData('groups/connect/fields/hostname/value'));
            $port = trim($this->getData('groups/connect/fields/port/value'));
            $path = trim($this->getData('groups/connect/fields/path/value'));
            if ($allfeedsenabled == "enabled") {
                if (empty($hostname) || empty($port) || empty($path)) {
                    throw new \Exception("Please enter SFTP details");
                }
            } else {
                if (!empty($hostname) && !empty($port) && !empty($path)) {
                    throw new \Exception("Remove SFTP details and disable");
                }
            }
        }

        return parent::afterSave();
    }
}
