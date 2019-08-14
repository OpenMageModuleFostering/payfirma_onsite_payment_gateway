<?php

class Collinsharper_Payfirma_Model_System_Config_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Collinsharper_Payfirma_Model_Payment::ACTION_AUTHORIZE,
                'label' => Mage::helper('chpayfirma')->__("Authorize Only")
            ),
            array(
                'value' => Collinsharper_Payfirma_Model_Payment::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('chpayfirma')->__("Sale")
            )
        );
    }
}
