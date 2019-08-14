<?php

class Twenga_Smartfeed_Block_Tag extends Mage_Core_Block_Text {

    protected function _toHtml() {
        $html = '';

        if(Mage::getStoreConfig('smartfeed/options/active') == 1) {
            // Get tracking code from config
            if (Mage::getStoreConfig('smartfeed/options/tracking_html')) {
                $html = Mage::getStoreConfig('smartfeed/options/tracking_html');
            } elseif (Mage::getStoreConfig('smartfeed/options/tracking_url')) {
                $html = '<script type="text/javascript" src="' . Mage::getStoreConfig('smartfeed/options/tracking_url') . '"></script>';
            }
        }

        $this->addText($html);
        return parent::_toHtml();
    }

}
