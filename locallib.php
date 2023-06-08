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
    //echo "get_data <br/>"; 
    global $DB;

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

    $data = curl_exec($curl);
    $data = json_decode($data);

    return $data;
}


//collect all different groups from aloom
function get_all_groups($result)
{
    $all_groups = array();
    //Kurse finden = Kurs1 -> Gruppe I
    //$course_title_01 = "Terminauswahl Gruppe I";
    $course_title_01 = "Managing Organizations";

    //$course_title_02 = "Terminauswahl Gruppe II";
    $course_title_02 = "Managing a Business - a Function";

    //$course_title_03 = "Terminauswahl Gruppe III";
    $course_title_03 = "Managing a Team - a Project";



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
                $nbr_options_course_title_01 = count((is_countable($result->data->questions[$i]->options) ? $result->data->questions[$i]->options : []));
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
                $nbr_options_course_title_02 = count((is_countable($result->data->questions[$i]->options) ? $result->data->questions[$i]->options : []));

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
                $nbr_options_course_title_03 = count((is_countable($result->data->questions[$i]->options) ? $result->data->questions[$i]->options : []));

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
    return $all_groups;
}



//return array with all ids belonging to one group = course
function collect_group_ids($arr)
{
    $out = array();
    for ($i = 0; $i < count((is_countable($arr) ? $arr : [])); $i++) {
        array_push($out, $arr[$i][1]);
    }
    return $out;
}


function user_csv_data($result, $all_groups)
{

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
    $user_choice = "";

    for ($i = 0; $i < $nbr_attendees; $i++) {
        try {
            if (array_key_exists(4, $result->data->attendees[$i]->answers)) {
                $proceed = true;
                $user_choice = $result->data->attendees[$i]->answers[4]->value->option_id;
                if (in_array($user_choice, $group_ids_course_01)) {



                    for ($j = 0; $j < count((is_countable($all_groupes_course_01) ? $all_groupes_course_01 : [])); $j++) {


                        if ($user_choice == $all_groupes_course_01[$j][1]) {
                            $item_found_in_g1 = true;
                            $group_name = $all_groupes_course_01[$j][0];
                            if ($all_groupes_course_01[$j][2] == "deutsch") {
                                $moodle_kurs = "Formel P1: Organizations (DE)";
                            } elseif ($all_groupes_course_01[$j][2] == "englisch") {
                                $moodle_kurs = "Formel P1: Organizations (ENG)";
                            }
                        }
                    }
                } else if (in_array($user_choice, $group_ids_course_02)) {

                   
                    for ($j = 0; $j < count((is_countable($all_groupes_course_02) ? $all_groupes_course_02 : [])); $j++) {


                        if ($user_choice == $all_groupes_course_02[$j][1]) {
                            $item_found_in_g2 = true;

                            $group_name = $all_groupes_course_02[$j][0];
                            if ($all_groupes_course_02[$j][2] == "deutsch") {
                                $moodle_kurs = "Formel P2: Business/Function (DE)";
                            } elseif ($all_groupes_course_02[$j][2] == "englisch") {
                                $moodle_kurs = "Formel P2: Business/Function (ENG)";
                            }
                        }
                    }
                } else if (in_array($user_choice, $group_ids_course_03)) {

                    

                    for ($j = 0; $j < count((is_countable($all_groupes_course_03) ? $all_groupes_course_03 : [])); $j++) {
                        if ($user_choice == $all_groupes_course_03[$j][1]) {
                            $item_found_in_g3 = true;

                            $group_name = $all_groupes_course_03[$j][0];
                            if ($all_groupes_course_03[$j][2] == "deutsch") {
                                $moodle_kurs = "Formel P3: Team/Project (DE)";
                            } elseif ($all_groupes_course_03[$j][2] == "englisch") {
                                $moodle_kurs = "Formel P3: Team/Project (ENG)";
                            }
                        }
                    }
                } else {
                    $proceed = false;
                    $user_choice = "";
                    $group_name = "";
                }
                
            } else {
                $proceed = false;
                $user_choice = "";
                $group_name = "";
            }
        } catch (Exception $e) {
            echo "keine daten; ";
            $proceed = false;
            $user_choice = "";
            $group_name = "";
        }
       
        if ($proceed == true) {
            $removers = array(",", ".");
            $vorname = $result->data->attendees[$i]->answers[1]->value;
            $vorname = str_replace($removers, "", $vorname);
            $nachname = $result->data->attendees[$i]->answers[0]->value;
            $nachname = str_replace($removers, "", $nachname);
            $email = trim($result->data->attendees[$i]->answers[2]->value, ",");
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
