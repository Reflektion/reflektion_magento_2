<?php

namespace Reflektion\Catalogexport\Block\Config;

class Mapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'rfk_attr',
            [
                'label' => __('RFK Attributes'),
                'style' => 'width:120px',
            ]
        );
        $this->addColumn(
            'mag_attr',
            [
                'label' => __('Magento Attributes'),
                'style' => 'width:120px',
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}