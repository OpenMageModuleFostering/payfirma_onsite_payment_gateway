<?php

class Collinsharper_Payfirma_Model_Payment extends Mage_Payment_Model_Method_Cc
{
    protected $_code = 'chpayfirma';

    protected $_formBlockType = 'chpayfirma/form';

    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;

    protected $_api;

    public function authorize(Varien_Object $payment, $amount)
    {
        parent::authorize($payment, $amount);

        $this->log("AUTHORIZE");

        $order = $payment->getOrder();
        $address = $order->getBillingAddress();
        $additional = array(
            'email'       => (string) $address->getEmail(),
            'first_name'  => (string) $address->getFirstname(),
            'last_name'   => (string) $address->getLastname(),
            'address1'    => (string) $address->getStreet(1),
            'address2'    => (string) $address->getStreet(2),
            'city'        => (string) $address->getCity(),
            'province'    => (string) $address->getRegion(),
            'country'     => (string) $address->getCountryId(),
            'postal_code' => (string) $address->getPostcode(),
            'company'     => (string) $address->getCompany(),
            'telephone'   => (string) $address->getTelephone(),
            'currency'    => (string) $this->_convertCurrencyCodeForApi($order->getOrderCurrencyCode()),
            'order_id'    => (string) $order->getIncrementId()
        );

        $result = $this->_getApi()->authorize(
            $this->_formatMoney($amount, $order->getOrderCurrencyCode()),
            $payment->getCcNumber(),
            $payment->getCcExpMonth(),
            $payment->getCcExpYear(),
            $this->hasVerification() ? $payment->getCcCid() : null,
            $additional
        );

        if (!$result['result_bool']) {
            Mage::throwException(Mage::helper('chpayfirma')->__("Payment authorization was declined."));
        }

        $payment->setTransactionId($result['transaction_id']);
        $payment->setIsTransactionClosed(false);

        $this->log("DONE Authorized with Transaction ID: " . var_export($result['transaction_id'], true));

        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);

        $order = $payment->getOrder();
        $authTransactionId = $payment->getParentTransactionId();
        if ($authTransactionId) {
            $this->log("CAPTURE (capture)");
            $result = $this->_getApi()->capture(
                $this->_formatMoney($amount, $order->getOrderCurrencyCode()),
                $authTransactionId
            );
        } else {
            $this->log("CAPTURE (sale)");

            // TODO: Maybe break all this out into a separate method, since it is shared with auth()
            $address = $order->getBillingAddress();
            $additional = array(
                'email'       => (string) $address->getEmail(),
                'first_name'  => (string) $address->getFirstname(),
                'last_name'   => (string) $address->getLastname(),
                'address1'    => (string) $address->getStreet(1),
                'address2'    => (string) $address->getStreet(2),
                'city'        => (string) $address->getCity(),
                'province'    => (string) $address->getRegion(),
                'country'     => (string) $address->getCountryId(),
                'postal_code' => (string) $address->getPostcode(),
                'company'     => (string) $address->getCompany(),
                'telephone'   => (string) $address->getTelephone(),
                'currency'    => (string) $this->_convertCurrencyCodeForApi($order->getOrderCurrencyCode()),
                'order_id'    => (string) $order->getIncrementId()
            );

            $result = $this->_getApi()->sale(
                $this->_formatMoney($amount, $order->getOrderCurrencyCode()),
                $payment->getCcNumber(),
                $payment->getCcExpMonth(),
                $payment->getCcExpYear(),
                $this->hasVerification() ? $payment->getCcCid() : null,
                $additional
            );
        }

        if (!$result['result_bool']) {
            Mage::throwException(Mage::helper('chpayfirma')->__("Payment capture was declined."));
        }

        $payment->setTransactionId($result['transaction_id']);
        $payment->setCcTransId($result['transaction_id']);

        $this->log("DONE Captured with Transaction ID: " . var_export($result['transaction_id'], true));

        return $this;
    }

    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        $this->log("REFUND");

        $order = $payment->getOrder();
        $result = $this->_getApi()->refund(
            $this->_formatMoney($amount, $order->getOrderCurrencyCode()),
            $payment->getParentTransactionId()
        );
        if (!$result['result_bool']) {
            Mage::throwException(Mage::helper('chpayfirma')->__("Payment refund was declined."));
        }
        $payment->setTransactionId($result['transaction_id']);

        $this->log("DONE Refunded with Transaction ID: " . var_export($result['transaction_id'], true));

        return $this;
    }

    // TODO: shall we do anything with fetchTransactionInfo() ?

    public function canUseForCurrency($currencyCode)
    {
        $currencies = explode(',', $this->getConfigData('accepted_currency'));
        if (!in_array($currencyCode, $currencies)) {
            return false;
        }
        return true;
    }

    public function log($message)
    {
        Mage::helper('chpayfirma')->log($message);
        return $this;
    }

    public function getDebugFlag()
    {
        return $this->getConfigData('is_debug_mode');
    }

    protected function _convertCurrencyCodeForApi($currencyCode)
    {
        switch ($currencyCode) {
            case 'CAD':
                return 'CA$';
            case 'USD':
                return 'US$';
        }

        return '';
    }

    protected function _formatMoney($amount, $currencyCode)
    {
        return number_format($amount, 2, '.', '');
    }


    /**
     * @return Collinsharper_Payfirma_Model_Api
     */
    protected function _getApi()
    {
        if (!$this->_api) {
            $this->_api = Mage::getModel('chpayfirma/api', $this->getStore());
        }
        return $this->_api;
    }

    /**
     * @return Collinsharper_Payfirma_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('chpayfirma');
    }
}
