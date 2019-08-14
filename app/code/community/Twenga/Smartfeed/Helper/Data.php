<?php

class Twenga_Smartfeed_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Twenga-Solutions product id
     * @var int
     */
    public $productId = 7;

    /**
     * curl headers
     * @var array
     */
    public $headers = array(
        'Accept' => 'application/json'
    );

    /**
     * Default options used for the curl requests
     * @var array
     */
    public $curlOptions = array(
        CURLOPT_ENCODING       => 'gzip',
        CURLOPT_BINARYTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_FRESH_CONNECT  => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'magento',
        CURLOPT_REFERER        => '',
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_HEADER         => true
    );

    /**
     * Twenga Geozone id
     * @var int
     */
    public function geoZoneId() {
        $locale = Mage::app()->getLocale()->getLocaleCode();
        $geoZoneId = explode('_', $locale);
        if(isset($geoZoneId[1])) {
            return $geoZoneId[1];
        } else {
            return 'UK'; // default
        }
    }

    /**
     * Build url using path and parameters
     * @param string $path
     * @param array  $parameters
     * @return string
     */
    public function buildUrl($path, array $parameters = array())
    {
        $url = 'https://api.twenga-solutions.com' . $path;
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }

    /**
     * Get curl resource
     * @param string $url
     * @return resource
     */
    public function getCurlResource($url)
    {
        $curlResource = curl_init($url);
        curl_setopt_array($curlResource, $this->curlOptions);

        return $curlResource;
    }

    /**
     * Execute curl call using GET
     * @param $url
     * @return mixed
     * @throws Exception
     */
    public function callGET($url)
    {
        $resource = $this->getCurlResource($url);

        return $this->curlCall($resource);
    }

    /**
     * Execute curl call using POST with given data
     * @param string $url
     * @param array  $data
     * @return array
     * @throws Exception
     */
    public function callPOST($url, array $data)
    {
        $resource = $this->getCurlResource($url);
        curl_setopt_array(
            $resource,
            array(
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS => $data
            )
        );

        return $this->curlCall($resource);
    }

    /**
     * Call webservice GET /module/signup
     * @return mixed
     */
    public function getFormSignUp()
    {
        $url = $this->buildUrl(
            '/module/signup',
            array(
                'PRODUCT_ID'   => $this->productId,
                'GEOZONE_CODE' => $this->geoZoneId(),
            )
        );

        return $this->callGET($url);
    }

    /**
     * Call webservice GET /module/login
     * @return mixed
     */
    public function getFormLogin()
    {
        $url = $this->buildUrl(
            '/module/login',
            array(
                'PRODUCT_ID'   => $this->productId,
                'GEOZONE_CODE' => $this->geoZoneId(),
            )
        );

        return $this->callGET($url);
    }

    /**
     * Get tracking script
     * @return mixed
     */
    public function getTrackingScript($token)
    {
        $url = $this->buildUrl('/tracker', array('token' => $token));
        $response = $this->callGET($url);

        return $response;
    }

    /**
     * Get current account information
     * @return mixed
     */
    public function getAccountInfo()
    {
        $url = $this->buildUrl('/account', array('token' => Mage::getStoreConfig('smartfeed/options/token')));
        $response = $this->callGET($url);

        return $response;
    }

    /**
     * Get current account information
     * @return array
     */
    public function getProduct()
    {
        $url = $this->buildUrl(
            '/product',
            array(
                'token'      => Mage::getStoreConfig('smartfeed/options/token'),
                'PRODUCT_ID' => $this->productId
            )
        );
        $response = $this->callGET($url);

        if (isset($response['products'])) {
            foreach ($response['products'] as $product) {
                if ($product['PRODUCT_ID'] == $this->productId) {
                    return $product;
                }
            }
        }

        return array();
    }

    /**
     * Execute curl call
     * @param $resource
     * @return array
     */
    public function curlCall($resource)
    {
        curl_setopt($resource, CURLOPT_HTTPHEADER, $this->getFormattedHeaders());
        $response = curl_exec($resource);
        $curlInfo = curl_getinfo($resource);
        if (false === $response) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Bad curl response: '.$curlInfo['http_code']));
            return false;
        }

        $headers = substr($response, 0, $curlInfo['header_size']);
        $response = trim(substr($response, $curlInfo['header_size'] - 1));

        $this->parseHeader($headers);
        $this->lastHttpCode = $curlInfo['http_code'];

        $json = json_decode($response, true);
        if (false === $json || !is_array($json)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Can\'t decode json. An error may occurred with http code ' . $curlInfo['http_code']));
            return false;
        }

        return $json;
    }

    /**
     * Parse header string
     * @param string $header
     * @return $this
     */
    protected function parseHeader($header)
    {
        $this->lastHeaders = array();
        $headerLines = explode("\n", $header);
        foreach ($headerLines as $line) {
            $headerParts = explode(':', $line, 2);
            if (!isset($headerParts[1])) {
                continue;
            }

            $value = trim($headerParts[1]);
            $key = $headerParts[0];

            if (isset($this->lastHeaders[$key])) {
                if (!is_array($this->lastHeaders[$key])) {
                    $this->lastHeaders[$key] = array($this->lastHeaders[$key]);
                }
                $this->lastHeaders[$key][] = $value;
            } else {
                $this->lastHeaders[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Get formatted headers for curl
     * @return array
     */
    public function getFormattedHeaders()
    {
        $output = array();
        foreach ($this->headers as $key => $value) {
            $output[] = $key . ': ' . $value;
        }

        return $output;
    }

    /**
     * Add analytics parameters
     * @param array $params
     * @param string $content
     * @return string
     */
    public function addUtm($params, $content)
    {
        if ($content) {
            $trackingParameters = array(
                'utm_source' => 'magento',
                'utm_medium' => 'partner',
                'utm_campaign' => 'module_magento_smartfeed',
                'utm_content' => 'bo'
            );

            $parsedUrl = \parse_url($content);
            if (!isset($parsedUrl['query']) || empty($parsedUrl['query'])) {
                $parsedUrl['query'] = \http_build_query($trackingParameters);
            } else {
                $parsedUrl['query'] .= '&' . \http_build_query($trackingParameters);
            }

            return $parsedUrl['scheme'].'://'.$parsedUrl['host'].$parsedUrl['path'].'?'.$parsedUrl['query'];
        }

        return $content;
    }

}