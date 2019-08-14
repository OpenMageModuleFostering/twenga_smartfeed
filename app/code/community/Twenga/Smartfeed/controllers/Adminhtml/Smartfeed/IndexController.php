<?php

class Twenga_Smartfeed_Adminhtml_Smartfeed_IndexController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Twenga-Solutions product id
     *
     * @var int
     */
    public $productId = 7;

    /**
     * Permissions
     * @param
     * @return
     */
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('sales/smartfeed');
    }

    /**
     * Initialize
     * @param
     * @return
     */
    protected function _initAction() {
        $this->loadLayout()->_setActiveMenu('sales/items');
        return $this;
    }

    /**
     * Display template
     * @param
     * @return
     */
    public function indexAction() {
        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Call webservice POST signup
     * @param array $data
     * @return mixed
     */
    public function postSignupAction()
    {
        $url = Mage::helper('smartfeed/data')->buildUrl(
            '/module/signup',
            array(
                'PRODUCT_ID' => $this->productId,
                'GEOZONE_CODE' => Mage::helper('smartfeed/data')->geoZoneId()
            )
        );

        $data = $this->getRequest()->getPost();
        $response = Mage::helper('smartfeed/data')->callPOST($url, $data);
        if($response['errors']) {
            $errorText = '';
            $countErrors = count($response['errors']);
            foreach($response['errors'] as $error) {
                if($countErrors > 1) {
                    $errorText .= $error.'<br/>';
                } else {
                    $errorText .= $error;
                }
            }

            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('smartfeed/data')->__($errorText));
            if($response['errors']['EMAIL']) {
                $param['email_exists'] = '1';
                $this->_redirect('*/*/', $param);
                return;
            } else {
                $this->_redirect('*/*/');
                return;
            }
        } else {
            //Mage::getSingleton('adminhtml/session')->addNotice($this->__('Warning: We have now taken your request into account. In order to benefit from our services you must finalise your subscription.'));

            // Refresh magento configuration cache
            Mage::getModel('core/config')->saveConfig('smartfeed/options/register_autolog_url', urldecode($response['user']['AUTO_LOG_URL']));
            Mage::app()->getCacheInstance()->cleanType('config');

            $param['signup_success'] = '1';
            $this->_redirect('*/*/', $param);
            return;
        }
    }

    /**
     * Call webservice POST /authenticate/email
     *
     * @param array $data
     *
     * @return array
     */
    public function authenticateEmailAction(array $data)
    {
        $data = $this->getRequest()->getPost();
        $url = Mage::helper('smartfeed/data')->buildUrl('/authenticate/email');
        $response = Mage::helper('smartfeed/data')->callPOST($url, $data);

        if($response['errors']) {
            $errorText = '';
            $countErrors = count($response['errors']);
            foreach($response['errors'] as $error) {
                if($countErrors > 1) {
                    $errorText .= $error.'<br/>';
                } else {
                    $errorText .= $error;
                }
            }

            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('smartfeed/data')->__($errorText));
            $param['authentication_failed'] = '1';
            $this->_redirect('*/*/', $param);
            return;
        } elseif(isset($response['auth']) && isset($response['auth']['token'])) {
            // Save information
            $siteId = $response['merchant']['EXTRANET_SITE_ID'];
            Mage::getModel('core/config')->saveConfig('smartfeed/options/site_id', $siteId);
            $apiKey = $response['merchant']['API_KEY'];
            Mage::getModel('core/config')->saveConfig('smartfeed/options/api_key', $apiKey);
            $autoLogUrl = $response['user']['AUTO_LOG_URL'];
            Mage::getModel('core/config')->saveConfig('smartfeed/options/autolog_url', urldecode($autoLogUrl));
            $token = $response['auth']['token'];
            Mage::getModel('core/config')->saveConfig('smartfeed/options/token', $token);
            $pass = md5($data['PASS']);
            Mage::getModel('core/config')->saveConfig('smartfeed/options/pass', $pass);

            // Get tracking code and save in config
            $tracking = Mage::helper('smartfeed/data')->getTrackingScript($token);
            Mage::getModel('core/config')->saveConfig('smartfeed/options/tracking_html', $tracking['tracker_script']['html']);
            Mage::getModel('core/config')->saveConfig('smartfeed/options/tracking_url', $tracking['tracker_script']['url']);

            // Refresh magento configuration cache
            Mage::app()->getCacheInstance()->cleanType('config');

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('smartfeed/data')->__('Connect with success'));
            $param['install_success'] = '1';
            $this->_redirect('*/*/', $param);
            return;
        }
    }

    /**
     * Authenticate
     *
     * @param string $extranetSiteId
     * @param string $apiKey
     * @return mixed
     */
    public function authenticateAction($extranetSiteId, $apiKey)
    {
        $url = Mage::helper('smartfeed/data')->buildUrl('/authenticate');
        Mage::helper('smartfeed/data')->curlOptions[CURLOPT_USERPWD] = $extranetSiteId . ':' . $apiKey;
        $response = Mage::helper('smartfeed/data')->callGET($url);
        if (isset($response['auth']) && isset($response['auth']['token'])) {
            $this->token = $response['auth']['token'];
        }

        return $response;
    }

    /**
     * Send form lostpassword
     * @return html
     */
    public function lostPasswordAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Call webservice POST lostpassword
     * @param string $email
     * @return array
     */
    public function postLostPasswordAction()
    {
        $data = $this->getRequest()->getPost();
        $url = Mage::helper('smartfeed/data')->buildUrl(
            '/module/lostpassword',
            array(
                'PRODUCT_ID' => $this->productId,
                'GEOZONE_CODE' => Mage::helper('smartfeed/data')->geoZoneId()
            )
        );

        $response = Mage::helper('smartfeed/data')->callPOST(
            $url,
            array(
                'EMAIL' => $data['email']
            )
        );

        if($response['errors']) {
            $errorText = '';
            foreach($response['errors'] as $error) {
                $errorText .= $error.'<br/>';
            }

            $result['success'] = false;
            $result['error'] = true;
            $result['error_message'] = Mage::helper('smartfeed/data')->__($errorText);
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
        } else {
            $successText = '';
            foreach($response['success'] as $success) {
                $successText .= $success.'<br/>';
            }

            $result['success'] = true;
            $result['error'] = false;
            $result['success_message'] = Mage::helper('smartfeed/data')->__($successText);
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
        }
    }
}

