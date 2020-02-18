<?php

namespace auth_catadmin\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\legacy_polyfill;

/**
 * Privacy provider for the authentication manual.
 *
 * @copyright  2018 Carlos Escobedo <carlos@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\null_provider {

    use legacy_polyfill;

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * This function is compatible with old php version. (Diff is the underscore '_' in the beginning)
     * But the get_reason is still available because of the trait legacy_polyfill.
     *
     * @return  string
     */
    public static function _get_reason() {
        return 'privacy:no_data_reason';
    }

}
