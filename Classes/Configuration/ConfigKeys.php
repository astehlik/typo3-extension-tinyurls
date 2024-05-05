<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Configuration;

class ConfigKeys
{
    public const BASE62_DICTIONARY = 'base62Dictionary';

    public const BASE_URL = 'baseUrl';

    public const BASE_URL_FROM_SITE_BASE = 'baseUrlFromSiteBase';

    public const CREATE_SPEAKING_URLS = 'createSpeakingURLs';

    public const MINIMAL_RANDOM_KEY_LENGTH = 'minimalRandomKeyLength';

    public const MINIMAL_TINYURL_KEY_LENGTH = 'minimalTinyurlKeyLength';

    public const SPEAKING_URL_TEMPLATE = 'speakingUrlTemplate';

    public const URL_RECORD_STORAGE_PID = 'urlRecordStoragePID';
}
