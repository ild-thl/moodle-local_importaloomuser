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


function get_data($token, $event_id)
{
    global $DB, $cert;

    //check value for aloom-connection in db
    if ($DB->get_records('config')) {
        //echo "db-records";
        $token = strval($DB->get_record('config', ['name' => 'local_importaloomuserdb_token'])->value);
        $event_id = strval($DB->get_record('config', ['name' => 'local_importaloomuser_event_id'])->value);
    }
    //use fallback from config.php
    else {
        $token = $token;
        $event_id = $event_id;
    }

    $headers = array();
    $headers[] = 'X-Auth-Token: ' . $token;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // return the results instead of outputting it
    curl_setopt($curl, CURLOPT_URL, 'https://tms.aloom.de/eventapi/geteventfull?event_id=' . $event_id);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_CAINFO, $cert);

    $data = curl_exec($curl);
    //var_dump($data);
    $data = json_decode($data);

    return $data;
}


//collect all different groups from aloom
function get_all_groups($result)
{
    global $DB;

    //check value for aloom-connection in db
    //echo "db-records";

    $all_groups = array();
    //Kurse finden = Kurs1 -> Gruppe I
    //$course_title_01 = "Terminauswahl Gruppe I";
    //$course_title_01 = "Managing Organizations";
    $course_title_01 = strval($DB->get_record('config', ['name' => 'local_importaloomuser_aloom_option_terminauswahl_gruppe_1'])->value);

    //$course_title_02 = "Terminauswahl Gruppe II";
    //$course_title_02 = "Managing a Business - a Function";
    $course_title_02 = strval($DB->get_record('config', ['name' => 'local_importaloomuser_aloom_option_terminauswahl_gruppe_2'])->value);

    //$course_title_03 = "Terminauswahl Gruppe III";
    //$course_title_03 = "Managing a Team - a Project";
    $course_title_03 = strval($DB->get_record('config', ['name' => 'local_importaloomuser_aloom_option_terminauswahl_gruppe_3'])->value);



    //geschachtelte Arrays mit struktur [[gruppenname 1,option_id, sprache], [gruppenname 2, ...]]
    //[["Gruppe III-2","560920","deutsch"],[...]]
    $all_groupes_course_01 = array();
    $all_groupes_course_02 = array();
    $all_groupes_course_03 = array();


    $nbr_questions = count($result->data->questions);

    for ($i = 0; $i < $nbr_questions; $i++) {
        if (isset($result->data->questions[$i]->label)) {
            //find group 1
            if (strpos($result->data->questions[$i]->label, $course_title_01) !== false) {

                //if ($result->data->questions[$i]->label == $course_title_01) {

                //get nbr of options
                //$nbr_options_course_title_01 = count((is_countable($result->data->questions[$i]->options) ? $result->data->questions[$i]->options : []));
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
            }
            //elseif ($result->data->questions[$i]->label == $course_title_02) {
            elseif (strpos($result->data->questions[$i]->label, $course_title_02) !== false) {

                //get nbr of options
                //$nbr_options_course_title_02 = count($result->data->questions[$i]->options);
                //$nbr_options_course_title_02 = count((is_countable($result->data->questions[$i]->options) ? $result->data->questions[$i]->options : []));
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
            }
            //elseif ($result->data->questions[$i]->label == $course_title_03) {
            elseif (strpos($result->data->questions[$i]->label, $course_title_03) !== false) {

                //$nbr_options_course_title_03 = count($result->data->questions[$i]->options);
                //$nbr_options_course_title_03 = count((is_countable($result->data->questions[$i]->options) ? $result->data->questions[$i]->options : []));
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
                        //echo "group name: " . $group_name;
                        $group_id = strval($result->data->questions[$i]->options[$j]->option_conditions[0]->option_id);
                        //echo "group id: " . $group_id;
                        $group_language = strval($result->data->questions[$i]->options[$j]->option_conditions[1]->param);
                        //echo "group language: " . $group_language;
                        //add data to group array
                        array_push($group_info, $group_name, $group_id, $group_language);
                        //add group array to course array
                        array_push($all_groupes_course_03, $group_info);
                    }
                }
            }
        }
    }
    array_push($all_groups, $all_groupes_course_01);
    array_push($all_groups, $all_groupes_course_02);
    array_push($all_groups, $all_groupes_course_03);
    //var_dump($all_groups);
    return $all_groups;
}



//return array with all ids belonging to one group = course
function collect_group_ids($arr)
{
    $out = array();
    for ($i = 0; $i < count((is_countable($arr) ? $arr : [])); $i++) {
        array_push($out, $arr[$i][1]);
    }
    //var_dump($out);
    return $out;
}


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
    $my_group = "";
    $group_name = "";
    $item_found_in_g0 = false;
    $item_found_in_g1 = false;
    $item_found_in_g2 = false;
    $item_found_in_g3 = false;


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
                    } elseif ($tem_user_choice <= $user_choice) {
                        //$user_choice = $result->data->attendees[$i]->answers[$k]->value->option_id;

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




            //dummy adress
            //$emailtest = "aloomnoreply@noreply.noreply" . $i;
            $username = strtolower($email);

            $user_csv_data .= "\n" . $username . "," . $vorname . "," . $nachname  . "," . $email . "," .  "," .  "," . $moodle_kurs . "," . $group_name;
            //echo "user csv data : " . $user_csv_data;
            //$user_csv_data .= "\n" . $username . "," . $vorname . "," . $nachname  . "," . $emailtest . "," .  "," .  "," . $moodle_kurs . "," . $group_name;
        }
    }
    return $user_csv_data;
}
