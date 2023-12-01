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
 * Proxy lock factory, task to clean history.
 *
 * @package    local_importaloomuser
 * @copyright   2023 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_importaloomuser\task;

require_once($CFG->libdir . '/clilib.php');

use stdClass;
use tool_uploaduser\local\cli_progress_tracker;

require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uploaduser/locallib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uploaduser/user_form.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uploaduser/locallib.php');
require_once($CFG->dirroot . '/local/importaloomuser/user_form.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/importaloomuser/locallib.php');

//use local CA-certificate
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->libdir . '/clilib.php');
//require_once($CFG->dirroot . '/local/importaloomuser/config.php');


class aloomuser_cron extends \core\task\scheduled_task
{

    public function get_name()
    {
        return get_string('aloomuser_cron', 'local_importaloomuser');
    }


    public function execute()
    {
        start_process(get_aloom_data());
    }
}


function get_aloom_data()
{    
    global $DB, $CFG;
    //check value for aloom-connection in db
    if ($DB->get_records('config')) {
        echo "db-records";
        $token = strval($DB->get_record('config', ['name' => 'local_importaloomuserdb_token'])->value);

        $event_id = strval($DB->get_record('config', ['name' => 'local_importaloomuser_event_id'])->value);

    }
    else {
        echo("No data received using provided id and token"); 
        die(); 
    }
    


    $headers = array();
    $headers[] = 'X-Auth-Token: ' . $token;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // return the results instead of outputting it
    curl_setopt($curl, CURLOPT_URL, 'https://tms.aloom.de/eventapi/geteventfull?event_id=' . $event_id);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    //use local cert
    $cert = $CFG->dirroot . strval($DB->get_record('config', ['name' => 'local_importaloomuser_certpath'])->value);

    curl_setopt($curl, CURLOPT_CAINFO, $cert);


    $data = curl_exec($curl);
    $data = json_decode($data);


    $result = $data; 
    $all_group_data = get_all_groups($result);
    $table_header = "username,firstname,lastname,email,profile_field_unternehmen,course1,group1,cohort1";
    $csv_data = $table_header;
    $csv_data = $csv_data . user_csv_data($result, $all_group_data);
    return $csv_data;
}



function start_process($data): void
{
    echo "function start_process";

    $iid         = optional_param('iid', '', PARAM_INT);
    $formdata1 = new stdClass;
    $formdata1->encoding = "UTF-8";
    $formdata1->delimiter_name = "comma";


    // Read the CSV file.
    $iid = \csv_import_reader::get_new_iid('uploaduser');
    $cir = new \csv_import_reader($iid, 'uploaduser');

    $content = $data;
    $readcount = $cir->load_csv_content($content, $formdata1->encoding, $formdata1->delimiter_name);
    $csvloaderror = $cir->get_error();
    unset($content);

    if (!is_null($csvloaderror)) {
        print_error('csvloaderror', '', $csvloaderror);
    }

    $process = new \tool_uploaduser\process($cir);

    $formdata = new stdClass;
    $formdata->uutype = 2;
    $formdata->uupasswordnew = 1;
    $formdata->uuupdatetype = 0;
    $formdata->uupasswordold = 0;
    $formdata->uuallowrenames = 0;
    $formdata->uuallowdeletes = 0;
    $formdata->uuallowsuspends = 1;
    $formdata->uunoemailduplicates = 1;
    $formdata->uustandardusernames = 1;
    $formdata->uubulk = 0;
    $formdata->auth = "manual";
    $formdata->maildisplay = 2;
    $formdata->emailstop = 0;
    $formdata->mailformat = 1;
    $formdata->maildigest = 0;
    $formdata->autosubscribe = 1;
    $formdata->city = "";
    $formdata->country = "";
    $formdata->timezone = 99;
    $formdata->lang = "de";
    $formdata->description = "";
    $formdata->institution = "";
    $formdata->department = "";
    $formdata->phone1 = "";
    $formdata->phone2 = "";
    $formdata->address = "";
    $formdata->profile_field_cluster = "bitte auswählen";
    $formdata->profile_field_position = "bitte auswählen";
    $formdata->profile_field_geschaeftsbereich = "bitte auswählen";
    $formdata->iid = $iid;
    $formdata->previewrows = 10;
    $formdata->submitbutton = "Upload Aloom Users";
    $formdata->descriptionformat = 1;

    $process->set_form_data($formdata);

    $process->process();
}
