<?php
/**
 * Unconditional constant/function declarations for static analysers.
 * Loaded by PHPStan via scanFiles and by Phan via file_list.
 * The runtime guards in dolibarr-runtime.php keep the real definitions
 * safe; this file exists only so analysers see the symbols unconditionally.
 */

define('DOL_DOCUMENT_ROOT', __DIR__ . '/../dolibarr/htdocs');
define('DOL_DATA_ROOT', __DIR__ . '/../dolibarr/documents');
define('DOL_URL_ROOT', '');
define('DOL_MAIN_URL_ROOT', '');
define('MAIN_DB_PREFIX', 'llx_');
define('DOL_VERSION', '24.0.0');

/**
 * @param string $head
 * @param string $title
 * @param string $help_url
 * @param string $target
 * @param int $disablejs
 * @param int $disablehead
 * @param string|array $arrayofjs
 * @param string|array $arrayofcss
 * @param string $morequerystring
 * @param string $morecssonbody
 * @param string $replacemainareaby
 * @param int $disablenofollow
 * @param int $disablenoindex
 * @return void
 */
function llxHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0)
{
}

/**
 * @param string $comment
 * @param string $zone
 * @param int $disabledoutputofmessages
 * @return void
 */
function llxFooter($comment = '', $zone = 'private', $disabledoutputofmessages = 0)
{
}
