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
 * Internal library of functions for module ilddigitalcert
 *
 * @package    local
 * @subpackage importaloomuser
 * @copyright   2023 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Retrieves data from the Aloom API.
 *
 * This function retrieves data from the Aloom API by making a request to the specified event ID.
 * It first checks if the necessary configuration values are present in the database.
 * If the values are found, it retrieves the token and event ID from the database.
 * It then constructs the necessary headers for the API request and initializes a cURL session.
 * The function sets the necessary cURL options, including the URL and headers.
 * It also sets the local CA certificate path for secure communication.
 * The function executes the cURL request and retrieves the response data.
 * The response data is then decoded from JSON format into a PHP object.
 * Finally, the function returns the retrieved data.
 *
 * @return object The retrieved data from the Aloom API.
 */
function get_data()
{
    global $DB, $CFG;

    //check value for aloom-connection in db
    if ($DB->get_records('config')) {
        $token = strval($DB->get_record('config', ['name' => 'local_importaloomuserdb_token'])->value);
        $event_id = strval($DB->get_record('config', ['name' => 'local_importaloomuser_event_id'])->value);
    } else {
        echo ("No data received using provided id and token");
        die();
    }

    $headers = array();
    $headers[] = 'X-Auth-Token: ' . $token;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, 'https://tms.aloom.de/eventapi/geteventfull?event_id=' . $event_id);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    //use local ca-certificate
    $cert = $CFG->dirroot . strval($DB->get_record('config', ['name' => 'local_importaloomuser_certpath'])->value);

    curl_setopt($curl, CURLOPT_CAINFO, $cert);

    $data = curl_exec($curl);
    $data = json_decode($data);

    return $data;
}



/**
 * Retrieves all groups from the given result object.
 * collect all different groups from aloom
 * return array with all groups for different courses
 * 
 * @param object $result The result object containing the groups.
 * @return array An array of all groups, organized by courses.
 */
function get_all_groups($result)
{
    global $DB;

    $all_groups = array();
    //find courses = Kurs1 -> Gruppe I
    //$course_title_01 = "Terminauswahl Gruppe I";
    //$course_title_01 = "Managing Organizations";
    $course_title_01 = strval($DB->get_record('config', ['name' => 'local_importaloomuser_aloom_option_terminauswahl_gruppe_1'])->value);

    //$course_title_02 = "Terminauswahl Gruppe II";
    //$course_title_02 = "Managing a Business - a Function";
    $course_title_02 = strval($DB->get_record('config', ['name' => 'local_importaloomuser_aloom_option_terminauswahl_gruppe_2'])->value);

    //$course_title_03 = "Terminauswahl Gruppe III";
    //$course_title_03 = "Managing a Team - a Project";
    $course_title_03 = strval($DB->get_record('config', ['name' => 'local_importaloomuser_aloom_option_terminauswahl_gruppe_3'])->value);



    /**
     * This code defines an array structure with nested arrays.
     * Each nested array represents a group and contains three elements:
     * - Group name
     * - Option ID
     * - Language
     * 
     * Example: [["Gruppe III-2","560920","deutsch"],[...]]
     */
    $all_groupes_course_01 = array();
    $all_groupes_course_02 = array();
    $all_groupes_course_03 = array();


    $nbr_questions = count($result->data->questions);

    /**
     * This code block iterates through the questions in the $result object and extracts information about course sub-groups.
     * It checks each question's label to determine the course title and then processes the options to find the sub-group information.
     * The extracted sub-group information is stored in separate arrays based on the course title.
     *
     * @param object $result The object containing the questions and options.
     * @param string $course_title_01 The title of the first course.
     * @param string $course_title_02 The title of the second course.
     * @param string $course_title_03 The title of the third course.
     * @param array $all_groupes_course_01 An array to store the sub-group information for the first course.
     * @param array $all_groupes_course_02 An array to store the sub-group information for the second course.
     * @param array $all_groupes_course_03 An array to store the sub-group information for the third course.
     * @param int $nbr_questions The total number of questions in the $result object.
     */
    for ($i = 0; $i < $nbr_questions; $i++) {
        if (isset($result->data->questions[$i]->label)) {
            //find group 1
            if (strpos($result->data->questions[$i]->label, $course_title_01) !== false) {

                $nbr_options_course_title_01 = 0;
                if (isset($result->data->questions[$i]->options) && is_countable($result->data->questions[$i]->options)) {
                    $nbr_options_course_title_01 = count($result->data->questions[$i]->options);
                }

                $nbr_questions = count((is_countable($result->data->questions) ? $result->data->questions : []));

                //check options for sub-group-selection
                for ($j = 0; $j < $nbr_options_course_title_01; $j++) {
                    //find options concerning course-sub-groups = option with label ""Gruppe I-Zahl"
                    if (strpos($result->data->questions[$i]->options[$j]->label, "Gruppe I") !== false) {
                        $group_info = array();

                        $group_name = mb_substr($result->data->questions[$i]->options[$j]->label, 0, 11);
                        $group_id = strval($result->data->questions[$i]->options[$j]->option_conditions[0]->option_id);
                        $group_language = strval($result->data->questions[$i]->options[$j]->option_conditions[1]->param);
                        //add data to group array

                        array_push($group_info, $group_name, $group_id, $group_language);
                        //add group array to course array
                        array_push($all_groupes_course_01, $group_info);
                    }
                }
            } elseif (strpos($result->data->questions[$i]->label, $course_title_02) !== false) {

                $nbr_options_course_title_02 = 0;
                if (isset($result->data->questions[$i]->options) && is_countable($result->data->questions[$i]->options)) {
                    $nbr_options_course_title_02 = count($result->data->questions[$i]->options);
                }

                //check options for sub-group-selection
                for ($j = 0; $j < $nbr_options_course_title_02; $j++) {
                    //find options concerning course-sub-groups = option with label ""Gruppe II-Zahl"
                    if (strpos($result->data->questions[$i]->options[$j]->label, "Gruppe II") !== false) {
                        $group_info = array();

                        $group_name = mb_substr($result->data->questions[$i]->options[$j]->label, 0, 12);
                        $group_id = strval($result->data->questions[$i]->options[$j]->option_conditions[0]->option_id);
                        $group_language = strval($result->data->questions[$i]->options[$j]->option_conditions[1]->param);
                        //add data to group array

                        array_push($group_info, $group_name, $group_id, $group_language);
                        //add group array to course array
                        array_push($all_groupes_course_02, $group_info);
                    }
                }
            } elseif (strpos($result->data->questions[$i]->label, $course_title_03) !== false) {

                $nbr_options_course_title_03 = 0;
                if (isset($result->data->questions[$i]->options) && is_countable($result->data->questions[$i]->options)) {
                    $nbr_options_course_title_03 = count($result->data->questions[$i]->options);
                }

                //check options for sub-group-selection
                for ($j = 0; $j < $nbr_options_course_title_03; $j++) {
                    //find options concerning course-sub-groups = option with label ""Gruppe III-Zahl"
                    if (strpos($result->data->questions[$i]->options[$j]->label, "Gruppe III") !== false) {
                        $group_info = array();

                        $group_name = mb_substr($result->data->questions[$i]->options[$j]->label, 0, 13);
                        $group_id = strval($result->data->questions[$i]->options[$j]->option_conditions[0]->option_id);
                        $group_language = strval($result->data->questions[$i]->options[$j]->option_conditions[1]->param);
                        array_push($group_info, $group_name, $group_id, $group_language);
                        array_push($all_groupes_course_03, $group_info);
                    }
                }
            }
        }
    }
    /**
     * Adds the given group arrays to the $all_groups array and returns it.
     *
     * @param array $all_groups The array to which the group arrays will be added.
     * @param array $all_groupes_course_01 The group array for course 01.
     * @param array $all_groupes_course_02 The group array for course 02.
     * @param array $all_groupes_course_03 The group array for course 03.
     * @return array The updated $all_groups array.
     */
    array_push($all_groups, $all_groupes_course_01);
    array_push($all_groups, $all_groupes_course_02);
    array_push($all_groups, $all_groupes_course_03);
    return $all_groups;
}




/**
 * Collects the group IDs from a given array.
 * return array with all ids belonging to one group = course
 *
 * @param array $arr The input array.
 * @return array The array containing the group IDs.
 */
function collect_group_ids($arr)
{
    $out = array();
    for ($i = 0; $i < count((is_countable($arr) ? $arr : [])); $i++) {
        array_push($out, $arr[$i][1]);
    }
    //var_dump($out);
    return $out;
}


/**
 * Generates CSV data for users based on the provided result and group information.
 * Also checks if the user already exists and updates the user profile data.
 *
 * @param object $result The result object containing attendee data.
 * @param array $all_groups An array of all groups for different courses.
 * @return string The generated CSV data for users.
 */
function user_csv_data($result, $all_groups)
{
    global $DB;

    $user_csv_data = "";
    $nbr_attendees = count((is_countable($result->data->attendees) ? $result->data->attendees : []));
    $all_groupes_course_01 = $all_groups[0];
    $all_groupes_course_02 = $all_groups[1];
    $all_groupes_course_03 = $all_groups[2];


    $group_ids_course_01 = collect_group_ids($all_groupes_course_01);
    $group_ids_course_02 = collect_group_ids($all_groupes_course_02);
    $group_ids_course_03 = collect_group_ids($all_groupes_course_03);

    $proceed = false;
    $group_name = "";


    /**
     * This code block iterates through the attendees' answers and determines the user's choice based on the question ID and option ID.
     * It then checks if the user's choice is valid for a specific group and retrieves the corresponding Moodle course shortname.
     * The code also handles cases where the user's choice is not valid or if there are no answers available.
     *
     * @param int $nbr_attendees The number of attendees.
     * @param object $result The result object containing the attendees' answers.
     * @param array $group_ids_course_01 The group IDs for course 1.
     * @param array $group_ids_course_02 The group IDs for course 2.
     * @param array $group_ids_course_03 The group IDs for course 3.
     * @param array $all_groupes_course_01 The details of all groups for course 1.
     * @param array $all_groupes_course_02 The details of all groups for course 2.
     * @param array $all_groupes_course_03 The details of all groups for course 3.
     * @param object $DB The Moodle database object.
     * @return void
     */
    for ($i = 0; $i < $nbr_attendees; $i++) {
        $user_choice = 0;
        $tem_user_choice = 0;
        try {
            //check if answers are in data
            $nbr_answers = count((is_countable($result->data->attendees[$i]->answers) ? $result->data->attendees[$i]->answers : []));

            //check for all answers
            for ($k = 0; $k < $nbr_answers; $k++) {

                if (isset($result->data->attendees[$i]->answers[$k]->value->question_id)) {
                    //if question_id is in option for group1
                    if ($result->data->attendees[$i]->answers[$k]->value->question_id == 706587) {
                        $proceed = true;
                        $tem_user_choice = $result->data->attendees[$i]->answers[$k]->value->option_id;
                    }

                    //if question_id is in option for group2
                    elseif ($result->data->attendees[$i]->answers[$k]->value->question_id == 706592) {
                        $proceed = true;
                        $tem_user_choice = $result->data->attendees[$i]->answers[$k]->value->option_id;
                    }

                    //if question_id is in option for group3
                    elseif ($result->data->attendees[$i]->answers[$k]->value->question_id == 709655) {
                        $proceed = true;
                        $tem_user_choice = $result->data->attendees[$i]->answers[$k]->value->option_id;
                    }

                    //remove answers if choice is "termin stornieren"
                    if ($tem_user_choice == 582946 || $tem_user_choice == 582947 || $tem_user_choice == 582948) {
                        $tem_user_choice = 0;
                    }
                    //remove answers if choice is "Bitte wählen Sie einen neuen Termin"
                    if ($tem_user_choice == 596675 || $tem_user_choice == 596676 || $tem_user_choice == 596677) {
                        $tem_user_choice = 0;
                    }

                    //use the answer that is valid for a group, exclude "no answer", "termin stornieren" and "Bitte wählen Sie einen neuen Termin"
                    //Problem: there could be several answers in different groups
                    //eg. group1: "termin stornieren" and group2: "Termin am x.x.xxxx"
                    //use the choice that is valid = highest value in answers
                    if ($tem_user_choice > $user_choice) {
                        $user_choice = $tem_user_choice;
                    }

                    //check if user_choice is in group_ids of course1
                    if (in_array($user_choice, $group_ids_course_01)) {

                        for ($j = 0; $j < count((is_countable($all_groupes_course_01) ? $all_groupes_course_01 : [])); $j++) {


                            if ($user_choice == $all_groupes_course_01[$j][1]) {
                                $item_found_in_g1 = true;
                                $group_name = $all_groupes_course_01[$j][0];
                                if ($all_groupes_course_01[$j][2] == "deutsch") {
                                    //$moodle_kurs = "Formel P1: Organizations (DE)";
                                    $moodle_kurs = strval($DB->get_record('config', ['name' => 'local_importaloomuser_course1_de_shortname'])->value);
                                } elseif ($all_groupes_course_01[$j][2] == "englisch") {
                                    //$moodle_kurs = "Formel P1: Organizations (ENG)";
                                    $moodle_kurs = strval($DB->get_record('config', ['name' => 'local_importaloomuser_course1_eng_shortname'])->value);
                                }
                            }
                        }
                    }
                    //check if user_choice is in group_ids of course2

                    elseif (in_array($user_choice, $group_ids_course_02)) {


                        for ($j = 0; $j < count((is_countable($all_groupes_course_02) ? $all_groupes_course_02 : [])); $j++) {


                            if ($user_choice == $all_groupes_course_02[$j][1]) {
                                $item_found_in_g2 = true;

                                $group_name = $all_groupes_course_02[$j][0];
                                if ($all_groupes_course_02[$j][2] == "deutsch") {
                                    //$moodle_kurs = "Formel P2: Business/Function (DE)";
                                    $moodle_kurs = strval($DB->get_record('config', ['name' => 'local_importaloomuser_course2_de_shortname'])->value);
                                } elseif ($all_groupes_course_02[$j][2] == "englisch") {
                                    //$moodle_kurs = "Formel P2: Business/Function (ENG)";
                                    $moodle_kurs = strval($DB->get_record('config', ['name' => 'local_importaloomuser_course2_eng_shortname'])->value);
                                }
                            }
                        }
                    }

                    //check if user_choice is in group_ids of course3
                    elseif (in_array($user_choice, $group_ids_course_03)) {



                        for ($j = 0; $j < count((is_countable($all_groupes_course_03) ? $all_groupes_course_03 : [])); $j++) {
                            if ($user_choice == $all_groupes_course_03[$j][1]) {
                                $item_found_in_g3 = true;

                                $group_name = $all_groupes_course_03[$j][0];
                                if ($all_groupes_course_03[$j][2] == "deutsch") {
                                    //$moodle_kurs = "Formel P3: Team/Project (DE)";
                                    $moodle_kurs = strval($DB->get_record('config', ['name' => 'local_importaloomuser_course3_de_shortname'])->value);
                                } elseif ($all_groupes_course_03[$j][2] == "englisch") {
                                    //$moodle_kurs = "Formel P3: Team/Project (ENG)";
                                    $moodle_kurs = strval($DB->get_record('config', ['name' => 'local_importaloomuser_course3_eng_shortname'])->value);
                                }
                            }
                        }
                    }
                    //else: no valid choice -> data will not be added to csv_data
                    else {
                        $proceed = false;
                        $user_choice = 0;
                        $group_name = "";
                    }
                }
            }
        } catch (Exception $e) {
            echo "keine daten; ";
            $proceed = false;
            $user_choice = 0;
            $group_name = "";
        }

        /**
         * This code block processes attendee data and updates user profile information if the user already exists.
         * It performs the following tasks:
         * 1. Extracts attendee information such as first name, last name, and email from the result data.
         * 2. Removes any commas and periods from the extracted names.
         * 3. Converts the email address to lowercase and extracts the domain name.
         * 4. Checks if a user with the same email already exists in the database.
         * 5. If the user exists, updates the user's profile data for the "userimport" and "unternehmen" fields.
         * 6. Appends the user's information to a CSV string.
         */
        if ($proceed == true) {
            $removers = array(",", ".");
            //check if name is in data

            $nbr_answers = count((is_countable($result->data->attendees[$i]->answers) ? $result->data->attendees[$i]->answers : []));

            for ($l = 0; $l < $nbr_answers; $l++) {


                if (isset($result->data->attendees[$i]->answers[$l]->question_id)) {
                    if ($result->data->attendees[$i]->answers[$l]->question_id == 706576) {
                        $vorname = $result->data->attendees[$i]->answers[$l]->value;
                        $vorname = str_replace($removers, "", $vorname);
                    }
                    if ($result->data->attendees[$i]->answers[$l]->question_id == 706575) {
                        $nachname = $result->data->attendees[$i]->answers[$l]->value;
                        $nachname = str_replace($removers, "", $nachname);
                    }
                    if ($result->data->attendees[$i]->answers[$l]->question_id == 706578) {
                        $email = trim($result->data->attendees[$i]->answers[$l]->value, ",");
                    }
                }
            }

            $username = strtolower($email);
            $maildata = " ";
            $maildata = substr(strrchr($username, "@"), 1);

            //update user profil data if user already exists
            global $DB;
            $user = $DB->get_record('user', array('email' => $email));
            if ($user) {
                //update user profil data for userimport
                $infoFieldNameImport = 'userimport';
                $newValue = "automatisch";
                
                //insert userimport data in user profile field "userimport" in db
                $DB->execute(
                    "
                    INSERT INTO {user_info_data} (userid, fieldid, data)
                    SELECT ?, uif.id, ?
                    FROM {user_info_field} uif
                    WHERE uif.name LIKE ?
                    ON DUPLICATE KEY UPDATE data = ?",
                    array($user->id, $newValue, $infoFieldNameImport, $newValue)
                );

                $infoFieldNameEnterprise = 'unternehmen';
                $newData = $maildata;
                //insert mail-domain data in user profile field "unternehmen" in db
                $DB->execute(
                    "
                    INSERT INTO {user_info_data} (userid, fieldid, data)
                    SELECT ?, uif.id, ?
                    FROM {user_info_field} uif
                    WHERE uif.name LIKE ?
                    ON DUPLICATE KEY UPDATE data = ?",
                    array($user->id, $newData, $infoFieldNameEnterprise, $newData)                );
                echo "User " . $email . ": Profilfelder wurde erfolgreich geupdated.<br/>";
            }

            $user_csv_data .= "\n" . $username . "," . $vorname . "," . $nachname  . "," . $email . "," .  $maildata .  "," . "automatisch" . "," . $moodle_kurs . "," . $group_name . "," . $maildata;
        }
    }
    return $user_csv_data;
}
