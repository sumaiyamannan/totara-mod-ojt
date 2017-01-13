<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Redis based session handler.
 *
 * @package    core
 * @copyright  2017 Matt Clarkson <mattc@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\session;

use RedisException;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/redis/sentinel.php');

class redissentinel extends redis {

       /**
     * Create new instance of handler.
     */
    public function __construct() {
        global $CFG;
        parent::__construct();

        $sentinel = new \sentinel($CFG->session_redissentinel_hosts);

        $master = $sentinel->get_master_addr('mymaster');

        if (!empty($master)) {
            $this->host = $master->ip;
            $this->port = $master->port;
        }
    }
}
