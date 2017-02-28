<?php
namespace Tx\Tinyurls\Domain\Validator;

use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Object\ImplementationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

class TinyUrlValidator implements ValidatorInterface
{
    /**
     * @var Result
     */
    protected $result;

    /**
     * Returns the options of this validator which can be specified in the constructor
     *
     * @return array
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param TinyUrl $value The value that should be validated
     * @return Result
     */
    public function validate($value)
    {
        $this->result = new Result();

        $this->validateValidUntil($value);
        $this->validateCustomUrlKey($value);

        return $this->result;
    }

    protected function validateCustomUrlKey(TinyUrl $tinyUrl)
    {
        if (!$tinyUrl->hasCustomUrlKey()) {
            return;
        }

        $tinyUrlRepository = ImplementationManager::getInstance()->getTinyUrlRepository();

        try {
            $existingTinyUrl = $tinyUrlRepository->findTinyUrlByKey($tinyUrl->getCustomUrlKey());
        } catch (TinyUrlNotFoundException $e) {
            // No matching URL found, the custom key can be used.
            return;
        }

        if (!$tinyUrl->equals($existingTinyUrl)) {
            // The existing key belongs to the TinyUrl record that is validated.
            return;
        }

        $error = new Error('The custom URL key is already used for a different URL.', 1488317930);
        $this->result->addError($error);
    }

    protected function validateValidUntil(TinyUrl $tinyUrl)
    {
        if ($tinyUrl->hasValidUntil() && $tinyUrl->getValidUntil()->diff(new \DateTime())->invert) {
            $error = new Error('The validUntil DateTime must not be in the past.', 1488307858);
            $this->result->forProperty('validUntil')->addError($error);
        }
    }
}
