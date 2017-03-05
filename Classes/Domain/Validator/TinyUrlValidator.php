<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Domain\Validator;

use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Object\ImplementationManager;
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
     * @var TinyUrlRepository
     */
    protected $tinyUrlRepository;

    public function injectTinyUrlRepository(TinyUrlRepository $tinyUrlRepository)
    {
        $this->tinyUrlRepository = $tinyUrlRepository;
    }

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

    /**
     * @return TinyUrlRepository
     * @codeCoverageIgnore
     */
    protected function getTinyUrlRepository(): TinyUrlRepository
    {
        if ($this->tinyUrlRepository === null) {
            $this->tinyUrlRepository = ImplementationManager::getInstance()->getTinyUrlRepository();
        }
        return $this->tinyUrlRepository;
    }

    protected function validateCustomUrlKey(TinyUrl $tinyUrl)
    {
        if (!$tinyUrl->hasCustomUrlKey()) {
            return;
        }

        $tinyUrlRepository = $this->getTinyUrlRepository();

        try {
            $existingTinyUrl = $tinyUrlRepository->findTinyUrlByKey($tinyUrl->getCustomUrlKey());
        } catch (TinyUrlNotFoundException $e) {
            // No matching URL found, the custom key can be used.
            return;
        }

        if ($tinyUrl->equals($existingTinyUrl)) {
            // The existing key belongs to the TinyUrl record that is validated.
            return;
        }

        $error = new Error('The custom URL key is already used for a different URL.', 1488317930);
        $this->result->forProperty('customUrlKey')->addError($error);
    }

    protected function validateValidUntil(TinyUrl $tinyUrl)
    {
        $now = new \DateTime();
        if ($tinyUrl->hasValidUntil() && $now->diff($tinyUrl->getValidUntil())->invert) {
            $error = new Error('The validUntil DateTime must not be in the past.', 1488307858);
            $this->result->forProperty('validUntil')->addError($error);
        }
    }
}
