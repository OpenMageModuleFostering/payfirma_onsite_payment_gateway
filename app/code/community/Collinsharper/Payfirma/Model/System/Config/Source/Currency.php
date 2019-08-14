<?php

class Collinsharper_Payfirma_Model_System_Config_Source_Currency
{
    public function toOptionArray($isMultiselect)
    {
        return array(
            array(
                'label' => Mage::helper('chpayfirma')->__("Canadian Dollar"),
                'value' => 'CAD'
            ),
            array(
                'label' => Mage::helper('chpayfirma')->__("US Dollar"),
                'value' => 'USD'
            )
        );
    }
}
