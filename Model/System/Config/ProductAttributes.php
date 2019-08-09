<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    28/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To fetch all user defined attributes
 */
namespace Reflektion\Catalogexport\Model\System\Config;

use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Store\Model\StoreManagerInterface;

class ProductAttributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $attributeCollection;

    /**
     * Constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        Collection $collection
    ) {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->attributeCollection = $collection;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $storeId = $this->storeManager->getStore()->getStoreId();
        $attributes = $this->attributeCollection
                                    ->addVisibleFilter()
                                    ->addStoreLabel($storeId)
                                    ->setOrder('main_table.attribute_id', 'asc')
                                    ->load();
        $result = [];
        foreach ($attributes as $_attribute) {
            $result[] = [
                'value' => $_attribute["attribute_code"],
                'label' => $_attribute["frontend_label"]
            ];
        }
        return $result;
    }
}
