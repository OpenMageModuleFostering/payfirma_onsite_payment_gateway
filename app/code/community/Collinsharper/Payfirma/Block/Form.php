<?php

class Collinsharper_Payfirma_Block_Form extends Mage_Payment_Block_Form_Cc
{
    protected function _toHtml()
    {
        $html = parent::_toHtml();

        // Find the last </ul> in the template, so we can sneak our logo in before it
        $lastUlPos = strrpos($html, '</ul>');
        if ($lastUlPos !== false) {
            $logoHtml = $this->getLayout()->createBlock('core/template')
                ->setTemplate('chpayfirma/logo.phtml')
                ->toHtml();

            // Insert our logo HTML before the last </ul>
            $html = substr_replace($html, $logoHtml, $lastUlPos, 0);
        }

        return $html;
    }
}
