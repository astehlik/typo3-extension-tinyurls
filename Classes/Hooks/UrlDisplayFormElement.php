<?php
namespace Tx\Tinyurls\Hooks;
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


class UrlDisplayFormElement extends \TYPO3\CMS\Backend\Form\AbstractNode implements \TYPO3\CMS\Backend\Form\NodeInterface
{
    /**
     * Main render method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $result = $this->initializeResultArray();
        $tinyUrl = new TinyUrlGenerator();
        $path = ExtensionManagementUtility::extRelPath('tinyurls').'Resources/Public';
        $result['requireJsModules'][] = $path . '/JavaScript/copytoclipboard.js';
        $result['html'] .= "<span onclick=\"return fieldtoclipboard.copyfield(event,'urldisplay')\"><img src='$path/Icons/copytoclipboard.png'/></span>";
        $result['html'] .= "<span id='urldisplay'>".$tinyUrl->buildTinyUrl($this->data['databaseRow']['urlkey'])."</span>";
        return $result;

    }
}