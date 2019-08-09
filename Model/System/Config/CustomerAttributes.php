<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    03/10/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To fetch all user defined customer attributes
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class CustomerAttributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * Constructor
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function __construct(
        \Magento\Customer\Model\Customer $customer
    ) {
        $this->customer = $customer;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->customer->getAttributes();
        $result = [];
        $skip = ['firstname', 'lastname', 'email'];
        foreach ($attributes as $attribute) {
            if (!empty($attribute["frontend_label"]) && !in_array($attribute["attribute_code"], $skip)) {
                $result[] = [
                    'value' => $attribute["attribute_code"],
                    'label' => $attribute["frontend_label"]
                ];
            }
        }
        return $result;
    }
}
