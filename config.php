<?php  // Plugin configuration file
/**
 * Link to CSV user upload
 *
 * @package    local
 * @subpackage importaloomuser
 * @copyright   2023 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//use local CA-certificate
global $cert; 
$cert= $CFG->dirroot . '/local/importaloomuser/cert/cacert.pem';