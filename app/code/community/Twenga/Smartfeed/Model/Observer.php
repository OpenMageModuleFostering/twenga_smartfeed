<?php

class Twenga_Smartfeed_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function deleteData(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('smartfeed/options/token')) {
            // Delete current information at login (except tracking code)
            Mage::getModel('core/config')->saveConfig('smartfeed/options/site_id', '');
            Mage::getModel('core/config')->saveConfig('smartfeed/options/api_key', '');
            Mage::getModel('core/config')->saveConfig('smartfeed/options/autolog_url', '');
            Mage::getModel('core/config')->saveConfig('smartfeed/options/token', '');
            Mage::getModel('core/config')->saveConfig('smartfeed/options/pass', '');

            // Refresh magento configuration cache
            Mage::app()->getCacheInstance()->cleanType('config');
        }
    }
}