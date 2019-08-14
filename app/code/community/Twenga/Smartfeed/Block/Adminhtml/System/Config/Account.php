<?php

class Twenga_Smartfeed_Block_Adminhtml_System_Config_Account extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('twenga_smartfeed/account.phtml');
        return $this;
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

}
