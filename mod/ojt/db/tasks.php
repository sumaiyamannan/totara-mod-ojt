<?php

/**
 * Comment
 *
 * @package    package
 * @subpackage sub_package
 * @copyright  &copy; 2019 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'mod_ojt\task\archive',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
);