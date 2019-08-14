<?php

class Collinsharper_Payfirma_Model_Api extends Varien_Object
{
    protected $_apiUrl;

    public function __construct($storeId)
    {
        parent::__construct(array('store_id' => $storeId));
    }

    public function sale($amount, $cardNumber, $expiryMonth, $expiryYear, $cvv = null, $additional = array())
    {
        $this->_init();

        $apiUrl = $this->_apiUrl . '/sale';
        $payload = array(
            'merchant_id'       => $this->getMerchantId(),
            'key'               => $this->getApiKey(),
            'amount'            => $amount,  // TODO: Should we round/format this, or leave it plain?  For all below!
            'card_number'       => $cardNumber,
            'card_expiry_month' => $expiryMonth,
            'card_expiry_year'  => $expiryYear,
            'do_not_store'      => 'true'
        );

        if ($cvv !== null) {
            $payload['cvv2'] = $cvv;
        }

        $additional = (array) $additional;
        if ($additional) {
            // Merge in this order so $additional can't overwrite anything already in $payload
            $payload = array_merge($additional, $payload);
        }

        if ($this->getIsTestMode()) {
            $payload['test_mode'] = 'true';
        }

        return $this->_executeRequest($apiUrl, $payload);
    }

    public function authorize($amount, $cardNumber, $expiryMonth, $expiryYear, $cvv = null, $additional = array())
    {
        $this->_init();

        $apiUrl = $this->_apiUrl . '/authorize';
        $payload = array(
            'merchant_id'       => $this->getMerchantId(),
            'key'               => $this->getApiKey(),
            'amount'            => $amount,
            'card_number'       => $cardNumber,
            'card_expiry_month' => $expiryMonth,
            'card_expiry_year'  => $expiryYear,
            'do_not_store'      => 'true'
        );

        if ($cvv !== null) {
            $payload['cvv2'] = $cvv;
        }

        $additional = (array) $additional;
        if ($additional) {
            // Merge in this order so $additional can't overwrite anything already in $payload
            $payload = array_merge($additional, $payload);
        }

        if ($this->getIsTestMode()) {
            $payload['test_mode'] = 'true';
        }

        return $this->_executeRequest($apiUrl, $payload);
    }

    /**
     * @todo possibly take metadata so we can save invoice id
     */
    public function capture($amount, $transactionId)
    {
        $this->_init();

        $apiUrl = $this->_apiUrl . '/capture/' . $transactionId;
        $payload = array(
            'merchant_id' => $this->getMerchantId(),
            'key'         => $this->getApiKey(),
            'amount'      => $amount
        );

        if ($this->getIsTestMode()) {
            $payload['test_mode'] = 'true';
        }

        return $this->_executeRequest($apiUrl, $payload);
    }

    public function refund($amount, $transactionId)
    {
        $this->_init();

        $apiUrl = $this->_apiUrl . '/refund/' . $transactionId;
        $payload = array(
            'merchant_id' => $this->getMerchantId(),
            'key'         => $this->getApiKey(),
            'amount'      => $amount
        );

        if ($this->getIsTestMode()) {
            $payload['test_mode'] = 'true';
        }

        return $this->_executeRequest($apiUrl, $payload);
    }

    public function retrieve($transactionId)
    {
        $this->_init();

        $apiUrl = $this->_apiUrl . '/transaction/' . $transactionId;
        $payload = array(
            'merchant_id' => $this->getMerchantId(),
            'key'         => $this->getApiKey(),
            'method'      => 'GET'
        );

        // TODO: Not sure if test_mode applies for this endpoint.
        if ($this->getIsTestMode()) {
            $payload['test_mode'] = 'true';
        }

        return $this->_executeRequest($apiUrl, $payload);
    }

    public function getMerchantId()
    {
        if (!$this->hasMerchantId()) {
            $this->_init();
        }

        return $this->getData('merchant_id');
    }

    public function getApiKey()
    {
        if (!$this->hasApiKey()) {
            $this->_init();
        }

        return $this->getData('api_key');
    }

    public function getIsTestMode()
    {
        if (!$this->hasIsTestMode()) {
            $this->_init();
        }

        return $this->getData('is_test_mode');
    }

    public function log($message)
    {
        Mage::helper('chpayfirma')->log($message);
        return $this;
    }

    protected function _executeRequest($apiUrl, $payload)
    {
        $this->log("Request to Payfirma API at " . var_export($apiUrl, true));
        $this->log("Payload: " . print_r($payload, true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // TODO: Not ideal, but can we get them to do anything about this?
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $this->log("RAW Response from Payfirma API: " . var_export($response, true));

        $result = Zend_Json::decode($response);
        if (isset($result['error'])) {
            $this->log("***** ENCOUNTERED AN API ERROR *****");
            Mage::throwException($result['error']);
        }

        $this->log("PARSED Response from Payfirma API: " . print_r($result, true));

        return $result;
    }

    protected function _init()
    {
        if (is_array($this->getStoreId()) || (!$this->getStoreId() && $this->getStoreId() !== 0)) {
            Mage::throwException("Payfirma API handler requires a valid store_id, but currently has '{$this->getStoreId()}'.");
        }

        $apiUrl = Mage::getStoreConfig('payment/chpayfirma/api_url', $this->getStoreId());
        $this->_apiUrl = $apiUrl;

        $merchantId = Mage::getStoreConfig('payment/chpayfirma/merchant_id', $this->getStoreId());
        $apiKey = Mage::getStoreConfig('payment/chpayfirma/api_key', $this->getStoreId());
        $isTestMode = Mage::getStoreConfig('payment/chpayfirma/is_test_mode', $this->getStoreId());

        $this->setData('merchant_id', $merchantId)
            ->setData('api_key', $apiKey)
            ->setData('is_test_mode', $isTestMode);

        return $this;
    }
}
