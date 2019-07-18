<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Make cron text field read only
 */
namespace Reflektion\Catalogexport\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Renderer extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setReadonly(true);
        return $element->getElementHtml();
    }
}
