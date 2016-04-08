<?php
namespace Tx\Tinyurls\Hooks;
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;

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
        $result['html'] = $tinyUrl->buildTinyUrl($this->data['databaseRow']['urlkey']);
        return $result;
    }
}