<?php

class Collinsharper_Payfirma_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function log($message)
    {
        if ($this->getConfig('is_debug_mode')) {
            Mage::log($message, Zend_Log::DEBUG, 'payment_chpayfirma.log');
        }

        return $this;
    }

    public function getConfig($code)
    {
        return Mage::getStoreConfig('payment/chpayfirma/' . $code);
    }
}
