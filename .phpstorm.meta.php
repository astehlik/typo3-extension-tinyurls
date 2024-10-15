<?php

namespace PHPSTORM_META {
    use Psr\Container\ContainerInterface;

    override(
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(0),
        map(
            [
                '' => '@',
            ]
        )
    );

    override(
        ContainerInterface::get(0),
        map(
            [
                '' => '@',
            ]
        )
    );
}
