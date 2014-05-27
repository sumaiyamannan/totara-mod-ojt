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
 * Email bounce checker - check a configured mailbox for bounce messages
 * Update Moodle user bounce counts and delete mail message
 * Delete old bounce messages
 * Expunge
 *
 * @package local_bouncechecker
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function local_bouncechecker_cron() {
    global $CFG;

    if (empty($CFG->envidentifier)) {
        return; // we need the site identifier to search for the relevant bounces
    }
    $CFG->envidentifier = substr($CFG->envidentifier, 0, 12);

    $bconfig = get_config('local_bouncechecker');

    if (empty($bconfig->enabled)) {
        return;
    }

    // Get all messages for this system
    $mbox = imap_open("{{$bconfig->mailserver}/tls/novalidate-cert}", $bconfig->mailuserame, $bconfig->mailpassword);
    if (empty($mbox)) {
        mtrace('bouncechecker: Could not open imap connection');
        return;
    }

    $searchcriteria = 'UNSEEN TO "'.$CFG->mailprefix.$CFG->envidentifier.'"';
    $newbounces = imap_search($mbox, $searchcriteria);
    if (!empty($newbounces)) {
        foreach ($newbounces as $msgno) {
            $headers = imap_headerinfo($mbox, $msgno);
            if (!local_bouncechecker_process_address($headers->to[0]->mailbox)) {
                mtrace('bouncechecker: could not process '.$headers->to[0]->mailbox);
                continue;
            }

            // Delete the bounced email
            imap_delete($mbox, $msgno);
            mtrace('bouncechecker: processed and removed mail to '.$headers->to[0]->mailbox);
        }
    }

    // Retrieve and delete ANY message older than 21 days
    $deletedate = date ("d M Y", strtotime("-21 days"));
    $oldmails = imap_search($mbox, "BEFORE \"$deletedate\"");
    if (!empty($oldmails)) {
        $deletedcount = 0;
        foreach ($oldmails as $msgno) {
            imap_delete($mbox, $msgno);
            $deletedcount++;
        }
        mtrace("bouncechecker: Cleaned $deletedcount old mails");
    }

    imap_expunge($mbox);
    imap_close($mbox);
}

function local_bouncechecker_process_address($address) {
    global $CFG;

    if (empty($CFG->envidentifier)) {
        return false;
    }
    $envid = substr($CFG->envidentifier, 0, 12);
    $address = str_replace($CFG->mailprefix.$envid, $CFG->mailprefix, $address);

    $prefix = substr($address,0,4);
    $mod = substr($address,4,2);
    $modargs = substr($address,6,-16);
    $hash = substr($address,-16);

    if (substr(md5($prefix.$envid.$mod.$modargs.$CFG->siteidentifier),0,16) != $hash) {
        return false;  // hash does not match - nothing to do
    }

    moodle_process_email($modargs,'');

    return true;
}
