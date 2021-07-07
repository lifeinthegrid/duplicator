<?php

/**
 * Utility class managing th emigration data
 *
 * Standard: PSR_2
 * @link http://www.php_fig.org/psr/psr_2
 * @copyright (c) 2017, Snapcreek LLC
 * @license https://opensource.org/licenses/GPL_3.0 GNU Public License
 *
 */

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

class DUP_Migration
{
    const CLEAN_INSTALL_REPORT_OPTION    = 'duplicator_clean_install_report';
    const ARCHIVE_REGEX_PATTERN          = '/^(.+_[a-z0-9]{7,}_[0-9]{14})_archive\.(?:zip|daf)$/';

    /**
     * messages to be displayed in the successful migration box
     *
     * @var array
     */
    protected static $migrationCleanupReport = array(
        'removed' => array(),
        'stored'  => array(),
        'instFile' => array()
    );

    /**
     * Check the root path and in case there are installer files without hashes rename them.
     *
     * @return void
     */
    public static function renameInstallersPhpFiles()
    {
        $pathsTocheck = array(
            DupLiteSnapLibIOU::safePathTrailingslashit(ABSPATH),
            DupLiteSnapLibIOU::safePathTrailingslashit(DupLiteSnapLibUtilWp::getHomePath()),
            DupLiteSnapLibIOU::safePathTrailingslashit(WP_CONTENT_DIR)
        );

        $pathsTocheck = array_unique($pathsTocheck);

        $filesToCheck = array();
        foreach ($pathsTocheck as $cFolder) {
            if (
                !is_dir($cFolder) ||
                !is_writable($cFolder) // rename permissions
            ) {
                continue;
            }
            $cFile = $cFolder . 'installer.php';
            if (
                !is_file($cFile) ||
                !DupLiteSnapLibIOU::chmod($cFile, 'u+rw') ||
                !is_readable($cFile)
            ) {
                continue;
            }
            $filesToCheck[] = $cFile;
        }

        $installerTplCheck = '/class DUPX_Bootstrap.+const\s+ARCHIVE_FILENAME\s*=\s*[\'"](.+?)[\'"]\s*;.*const\s+PACKAGE_HASH\s*=\s*[\'"](.+?)[\'"];/s';

        foreach ($filesToCheck as $file) {
            $fileName = basename($file);
            if (($content = @file_get_contents($file, false, null, 0, 5000)) === false) {
                continue;
            }
            $matches = null;
            if (preg_match($installerTplCheck, $content, $matches) !== 1) {
                continue;
            }

            $archiveName = $matches[1];
            $hash = $matches[2];
            $matches = null;

            if (preg_match(self::ARCHIVE_REGEX_PATTERN, $archiveName, $matches) !== 1) {
                if (DupLiteSnapLibIOU::unlink($file)) {
                    self::$migrationCleanupReport['instFile'][] = "<div class='failure'>"
                        . "<i class='fa fa-check green'></i> "
                        . sprintf(__('Installer file <b>%s</b> removed for secority reasons', 'duplicator'), esc_html($fileName))
                        . "</div>";
                } else {
                    self::$migrationCleanupReport['instFile'][] = "<div class='success'>"
                        . '<i class="fa fa-exclamation-triangle red"></i> '
                        . sprintf(__('Can\'t remove installer file <b>%s</b>, please remove it for security reasons', 'duplicator'), esc_html($fileName))
                        . '</div>';
                }
                continue;
            }

            $archiveHash =  $matches[1];
            if (strpos($file, $archiveHash) === false) {
                if (DupLiteSnapLibIOU::rename($file, dirname($file) . '/' . $archiveHash . '_installer.php', true)) {
                    self::$migrationCleanupReport['instFile'][] = "<div class='failure'>"
                        . "<i class='fa fa-check green'></i> "
                        . sprintf(__('Installer file <b>%s</b> renamed with HASH', 'duplicator'), esc_html($fileName))
                        . "</div>";
                } else {
                    self::$migrationCleanupReport['instFile'][] = "<div class='success'>"
                        . '<i class="fa fa-exclamation-triangle red"></i> '
                        . sprintf(__('Can\'t rename installer file <b>%s</b> with HASH, please remove it for security reasons', 'duplicator'), esc_html($fileName))
                        . '</div>';
                }
            }
        }
    }

    /**
     * return cleanup report
     *
     * @return array
     */
    public static function getCleanupReport()
    {
        $option = get_option(self::CLEAN_INSTALL_REPORT_OPTION);
        if (is_array($option)) {
            self::$migrationCleanupReport = array_merge(self::$migrationCleanupReport, $option);
        }

        return self::$migrationCleanupReport;
    }

    /**
     * save clean up report in wordpress options
     *
     * @return boolean
     */
    public static function saveCleanupReport()
    {
        return add_option(self::CLEAN_INSTALL_REPORT_OPTION, self::$migrationCleanupReport, '', 'no');
    }
}
