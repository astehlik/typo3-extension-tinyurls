<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Domain\Validator;

use DateTime;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

class TinyUrlValidator implements ValidatorInterface
{
    protected Result $result;

    public function __construct(protected readonly TinyUrlRepository $tinyUrlRepository) {}

    /**
     * Returns the options of this validator which can be specified in the constructor.
     */
    public function getOptions(): array
    {
        return [];
    }

    /**
     * @codeCoverageIgnore
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setOptions(array $options): void {}

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param TinyUrl $value The value that should be validated
     */
    public function validate($value): Result
    {
        $this->result = new Result();

        $this->validateTargetUrl($value);
        $this->validateValidUntil($value);
        $this->validateCustomUrlKey($value);

        return $this->result;
    }

    protected function validateCustomUrlKey(TinyUrl $tinyUrl): void
    {
        if (!$tinyUrl->hasCustomUrlKey()) {
            return;
        }

        try {
            $existingTinyUrl = $this->tinyUrlRepository->findTinyUrlByKey($tinyUrl->getCustomUrlKey());
        } catch (TinyUrlNotFoundException) {
            // No matching URL found, the custom key can be used.
            return;
        }

        // @extensionScannerIgnoreLine
        if ($tinyUrl->equals($existingTinyUrl)) {
            // The existing key belongs to the TinyUrl record that is validated.
            return;
        }

        $error = new Error('The custom URL key is already used for a different URL.', 1488317930);
        $this->result->forProperty('customUrlKey')->addError($error);
    }

    protected function validateValidUntil(TinyUrl $tinyUrl): void
    {
        $now = new DateTime();
        if ($tinyUrl->hasValidUntil() && $now->diff($tinyUrl->getValidUntil())->invert) {
            $error = new Error('The validUntil DateTime must not be in the past.', 1488307858);
            $this->result->forProperty('validUntil')->addError($error);
        }
    }

    private function validateTargetUrl(TinyUrl $value): void
    {
        if ($value->getTargetUrl() !== '') {
            return;
        }

        $error = new Error('The target URL must not be empty.', 1714916406);
        $this->result->forProperty('targetUrl')->addError($error);
    }
}
