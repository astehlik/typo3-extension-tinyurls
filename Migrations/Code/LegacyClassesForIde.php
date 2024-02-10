<?php

declare(strict_types=1);

namespace {
    use Tx\Tinyurls\Hooks\EidProcessor;
    use Tx\Tinyurls\Hooks\TceDataMap;
    use Tx\Tinyurls\Hooks\TypoLink;
    use Tx\Tinyurls\TinyUrl\Api;
    use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
    use Tx\Tinyurls\Utils\ConfigUtils;
    use Tx\Tinyurls\Utils\UrlUtils;

    exit('Access denied');

    /**
     * @deprecated since 1.0.0 will be removed in 3.0.0
     */
    class Tx_Tinyurls_Hooks_EidProcessor extends EidProcessor {}

    /**
     * @deprecated since 1.0.0 will be removed in 3.0.0
     */
    class Tx_Tinyurls_Hooks_Tce extends TceDataMap {}

    /**
     * @deprecated since 1.0.0 will be removed in 3.0.0
     */
    class Tx_Tinyurls_Hooks_TypoLink extends TypoLink {}

    /**
     * @deprecated since 1.0.0 will be removed in 3.0.0
     */
    class Tx_Tinyurls_TinyUrl_Api extends Api {}

    /**
     * @deprecated since 1.0.0 will be removed in 3.0.0
     */
    class Tx_Tinyurls_TinyUrl_TinyUrlGenerator extends TinyUrlGenerator {}

    /**
     * @deprecated since 1.0.0 will be removed in 3.0.0
     */
    class Tx_Tinyurls_Utils_ConfigUtils extends ConfigUtils {}

    /**
     * @deprecated since 1.0.0 will be removed in 3.0.0
     */
    class Tx_Tinyurls_Utils_UrlUtils extends UrlUtils {}
}

namespace Tx\Tinyurls\Hooks {
    use Tx\Tinyurls\Controller\EidController;

    /**
     * @deprecated since 2.0.0 will be removed in 4.0.0
     */
    class EidProcessor extends EidController {}
}

namespace Tx\Tinyurls\Utils {
    use Tx\Tinyurls\Configuration\ExtensionConfiguration;

    /**
     * @deprecated since 2.0.0 will be removed in 4.0.0
     */
    class ConfigUtils extends ExtensionConfiguration {}
}
