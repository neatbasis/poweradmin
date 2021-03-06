<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2009  Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2014  Poweradmin Development Team
 *      <http://www.poweradmin.org/credits.html>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  Toolkit functions
 *
 * @package Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2014 Poweradmin Development Team
 * @license     http://opensource.org/licenses/GPL-3.0 GPL
 */

// Fix for Strict Standards: Non-static method PEAR::setErrorHandling() should not be called statically
// TODO: remove after PEAR::MDB2 replacement with PDO
ini_set ('error_reporting', E_ALL & ~ (E_NOTICE | E_STRICT));

// TODO: display elapsed time and memory consumption,
// used to check improvements in refactored version 
$display_stats = false;
if ($display_stats) include('inc/benchmark.php');

ob_start();

if (! function_exists('session_start')) die(error('You have to install PHP session extension!'));
if (! function_exists('_')) die(error('You have to install PHP gettext extension!'));
if (! function_exists('mcrypt_encrypt')) die(error('You have to install PHP mcrypt extension!'));

session_start();

include_once("config-me.inc.php");

if(!@include_once("config.inc.php"))
{
	error( _('You have to create a config.inc.php!') );
}

/*************
 * Constants *
 *************/

if (isset($_GET["start"])) {
   define('ROWSTART', (($_GET["start"] - 1) * $iface_rowamount));
   } else {
   /** Starting row
    */
   define('ROWSTART', 0);
}

if (isset($_GET["letter"])) {
   define('LETTERSTART', $_GET["letter"]);
   $_SESSION["letter"] = $_GET["letter"];
} elseif(isset($_SESSION["letter"])) {
   define('LETTERSTART', $_SESSION["letter"]);
} else {
   /** Starting letter
    */
   define('LETTERSTART', "a");
}

if (isset($_GET["zone_sort_by"]) && preg_match("/^[a-z_]+$/", $_GET["zone_sort_by"] ) ) {
   define('ZONE_SORT_BY', $_GET["zone_sort_by"]);
   $_SESSION["zone_sort_by"] = $_GET["zone_sort_by"];
} elseif(isset($_POST["zone_sort_by"]) && preg_match("/^[a-z_]+$/", $_POST["zone_sort_by"] )) {
   define('ZONE_SORT_BY', $_POST["zone_sort_by"]);
   $_SESSION["zone_sort_by"] = $_POST["zone_sort_by"];
} elseif(isset($_SESSION["zone_sort_by"])) {
   define('ZONE_SORT_BY', $_SESSION["zone_sort_by"]);
} else {
   /** Field to sort zone by
    */
   define('ZONE_SORT_BY', "name");
}

if (isset($_SESSION["userlang"])) {
	$iface_lang = $_SESSION["userlang"];
}

if (isset($_GET["record_sort_by"]) && preg_match("/^[a-z_]+$/", $_GET["record_sort_by"] )) {
   define('RECORD_SORT_BY', $_GET["record_sort_by"]);
   $_SESSION["record_sort_by"] = $_GET["record_sort_by"];
} elseif(isset($_POST["record_sort_by"]) && preg_match("/^[a-z_]+$/", $_POST["record_sort_by"] )) {
   define('RECORD_SORT_BY', $_POST["record_sort_by"]);
   $_SESSION["record_sort_by"] = $_POST["record_sort_by"];
} elseif(isset($_SESSION["record_sort_by"])) {
   define('RECORD_SORT_BY', $_SESSION["record_sort_by"]);
} else {
   /** Record to sort zone by
    */
   define('RECORD_SORT_BY', "name");
}

$valid_tlds = array("ac", "ad", "ae", "aero", "af", "ag", "ai", "al", "am",
  "an", "ao", "aq", "ar", "arpa", "as", "asia", "at", "au", "aw", "ax", "az",
  "ba", "bb", "bd", "be", "bf", "bg", "bh", "bi", "bike", "biz", "bj", "bm",
  "bn", "bo", "br", "bs", "bt", "bv", "bw", "by", "bz", "ca", "camera", "cat",
  "cc", "cd", "cf", "cg", "ch", "ci", "ck", "cl", "clothing", "cm", "cn", "co",
  "com", "construction", "contractors", "coop", "cr", "cu", "cv", "cw", "cx",
  "cy", "cz", "de", "diamonds", "directory", "dj", "dk", "dm", "do", "dz", "ec",
  "edu", "ee", "eg", "enterprises", "equipment", "er", "es", "estate", "et",
  "eu", "fi", "fj", "fk", "fm", "fo", "fr", "ga", "gallery", "gb", "gd", "ge",
  "gf", "gg", "gh", "gi", "gl", "gm", "gn", "gov", "gp", "gq", "gr", "graphics",
  "gs", "gt", "gu", "guru", "gw", "gy", "hk", "hm", "hn", "holdings", "hr",
  "ht", "hu", "id", "ie", "il", "im", "in", "info", "int", "io", "iq", "ir",
  "is", "it", "je", "jm", "jo", "jobs", "jp", "ke", "kg", "kh", "ki", "kitchen",
  "km", "kn", "kp", "kr", "kw", "ky", "kz", "la", "land", "lb", "lc", "li",
  "lighting", "lk", "lr", "ls", "lt", "lu", "lv", "ly", "ma", "mc", "md", "me",
  "mg", "mh", "mil", "mk", "ml", "mm", "mn", "mo", "mobi", "mp", "mq", "mr",
  "ms", "mt", "mu", "museum", "mv", "mw", "mx", "my", "mz", "na", "name", "nc",
  "ne", "net", "nf", "ng", "ni", "nl", "no", "np", "nr", "nu", "nz", "om",
  "org", "pa", "pe", "pf", "pg", "ph", "photography", "pk", "pl", "plumbing",
  "pm", "pn", "post", "pr", "pro", "ps", "pt", "pw", "py", "qa", "re", "ro",
  "rs", "ru", "rw", "sa", "sb", "sc", "sd", "se", "sexy", "sg", "sh", "si",
  "singles", "sj", "sk", "sl", "sm", "sn", "so", "sr", "st", "su", "sv", "sx",
  "sy", "sz", "tattoo", "tc", "td", "technology", "tel", "tf", "tg", "th",
  "tips", "tj", "tk", "tl", "tm", "tn", "to", "today", "tp", "tr", "travel",
  "tt", "tv", "tw", "tz", "ua", "ug", "uk", "us", "uy", "uz", "va", "vc", "ve",
  "ventures", "vg", "vi", "vn", "voyage", "vu", "wf", "ws", "xn--3e0b707e",
  "xn--45brj9c", "xn--80ao21a", "xn--80asehdb", "xn--80aswg", "xn--90a3ac",
  "xn--clchc0ea0b2g2a9gcd", "xn--fiqs8s", "xn--fiqz9s", "xn--fpcrj9c3d",
  "xn--fzc2c9e2c", "xn--gecrj9c", "xn--h2brj9c", "xn--j1amh", "xn--j6w193g",
  "xn--kprw13d", "xn--kpry57d", "xn--l1acc", "xn--lgbbat1ad8j", "xn--mgb9awbf",
  "xn--mgba3a4f16a", "xn--mgbaam7a8h", "xn--mgbayh7gpa", "xn--mgbbh1a71e",
  "xn--mgbc0a9azcg", "xn--mgberp4a5d4ar", "xn--mgbx4cd0ab", "xn--ngbc5azd",
  "xn--o3cw4h", "xn--ogbpf8fl", "xn--p1ai", "xn--pgbs0dh", "xn--q9jyb4c",
  "xn--s9brj9c", "xn--unup4y", "xn--wgbh1c", "xn--wgbl6a", "xn--xkc2al3hye2a",
  "xn--xkc2dl3a5ee0h", "xn--yfro4i67o", "xn--ygbi2ammx", "xxx", "ye", "yt",
  "za", "zm", "zw");

// Special TLDs for testing and documentation purposes
// http://tools.ietf.org/html/rfc2606#section-2
array_push($valid_tlds, 'test', 'example', 'invalid', 'localhost');

/* Database connection */

require_once("database.inc.php");
// Generates $db variable to access database.


// Array of the available zone types
$server_types = array("MASTER", "SLAVE", "NATIVE");

// $rtypes - array of possible record types
$rtypes = array('A', 'AAAA', 'CNAME', 'HINFO', 'MX', 'NAPTR', 'NS', 'PTR', 'SOA', 'SPF', 'SRV', 'SSHFP', 'TXT', 'RP');

// If fancy records is enabled, extend this field.
if($dns_fancy) {
	$rtypes[14] = 'URL';
	$rtypes[15] = 'MBOXFW';
	$rtypes[16] = 'CURL';
	$rtypes[17] = 'LOC';
}


/*************
 * Includes  *
 *************/

require_once("i18n.inc.php");
require_once("error.inc.php");
require_once("auth.inc.php");
require_once("users.inc.php");
require_once("dns.inc.php");
require_once("record.inc.php");
require_once("templates.inc.php");

$db = dbConnect();
doAuthenticate();


/*************
 * Functions *
 *************/

/** Print paging menu
 *
 * Display the page option: [1] [2] .. [n]
 *
 * @param int $amount Total number of items
 * @param int $rowamount Per page number of items
 * @param int $id Page specific ID (Zone ID, Template ID, etc)
 *
 * @return null
 */
function show_pages($amount,$rowamount,$id='')
{
   if ($amount > $rowamount) {
      if (!isset($_GET["start"])) $_GET["start"]=1;
      echo _('Show page') . ":<br>";
      for ($i=1;$i<=ceil($amount / $rowamount);$i++) {
         if ($_GET["start"] == $i) {
            echo "[ <b>".$i."</b> ] ";
         } else {
            echo " <a href=\"".htmlentities($_SERVER["PHP_SELF"], ENT_QUOTES)."?start=".$i;
	    if ($id!='') echo "&id=".$id;
	    echo "\">[ ".$i." ]</a> ";
         }
      }
   }
}

/** Print alphanumeric paging menu
 *
 * Display the alphabetic option: [0-9] [a] [b] .. [z]
 *
 * @param string $letterstart Starting letter/number or 'all'
 * @param boolean $userid unknown usage
 *
 * @return null
 */
function show_letters($letterstart,$userid=true)
{
        echo _('Show zones beginning with') . ":<br>";

	$letter = "[[:digit:]]";
	if ($letterstart == "1")
	{
		echo "[ <span class=\"lettertaken\">0-9</span> ] ";
	}
	elseif (zone_letter_start($letter,$userid))
	{
		echo "<a href=\"".htmlentities($_SERVER["PHP_SELF"], ENT_QUOTES)."?letter=1\">[ 0-9 ]</a> ";
	}
	else
	{
		echo "[ <span class=\"letternotavailable\">0-9</span> ] ";
	}

        foreach (range('a','z') as $letter)
        {
                if ($letter == $letterstart)
                {
                        echo "[ <span class=\"lettertaken\">".$letter."</span> ] ";
                }
                elseif (zone_letter_start($letter,$userid))
                {
                        echo "<a href=\"".htmlentities($_SERVER["PHP_SELF"], ENT_QUOTES)."?letter=".$letter."\">[ ".$letter." ]</a> ";
                }
                else
                {
                        echo "[ <span class=\"letternotavailable\">".$letter."</span> ] ";
                }
        }

	if ($letterstart == 'all')
	{
		echo "[ <span class=\"lettertaken\"> Show all </span> ] ";
	} else {
		echo "<a href=\"".htmlentities($_SERVER["PHP_SELF"], ENT_QUOTES)."?letter=all\">[ Show all ]</a> ";
	}
}

/** Check if any zones start with letter
 *
 * @param string $letter Starting Letter
 * @param boolean $userid unknown usage
 *
 * @return int 1 if rows found, 0 otherwise
 */
function zone_letter_start($letter,$userid=true)
{
        global $db;
	global $sql_regexp;
        $query = "SELECT 
			domains.id AS domain_id,
			zones.owner,
			domains.name AS domainname
			FROM domains
			LEFT JOIN zones ON domains.id=zones.domain_id 
			WHERE substring(domains.name,1,1) ".$sql_regexp." ".$db->quote("^".$letter, 'text');
	$db->setLimit(1);
        $result = $db->queryOne($query);
        return ($result ? 1 : 0);
}

/** Print error message (toolkit.inc)
 *
 * @param string $msg Error message
 *
 * @return null
 */
function error($msg) {
	if ($msg) {
		echo "     <div class=\"error\">Error: " . $msg . "</div>\n";
	} else {
		echo "     <div class=\"error\">" . _('An unknown error has occurred.') . "</div>\n"; 
	}
}

/** Print success message (toolkit.inc)
 *
 * @param string $msg Success message
 *
 * @return null
 */
function success($msg) {
	if ($msg) {
		echo "     <div class=\"success\">" . $msg . "</div>\n";
	} else {
		echo "     <div class=\"success\">" . _('Something has been successfully performed. What exactly, however, will remain a mystery.') . "</div>\n"; 
	}
}


/** Print message
 *
 * Something has been done nicely, display a message and a back button.
 *
 * @param string $msg Message
 *
 * @return null
 */
function message($msg)
{
    include_once("header.inc.php");
    ?>
    <P><TABLE CLASS="messagetable"><TR><TD CLASS="message"><H2><?php echo _('Success!'); ?></H2>
    <BR>
	<FONT STYLE="font-weight: Bold">
	<P>
	<?php
    if($msg)
    {
        echo nl2br($msg);
    }
    else
    {
        echo _('Successful!');
    }
    ?>
    </P>
    <BR>
    <P>
    <a href="javascript:history.go(-1)">&lt;&lt; <?php echo _('back'); ?></a></FONT>
    </P>
    </TD></TR></TABLE></P>
    <?php
    include_once("footer.inc.php");
}


/** Send 302 Redirect with optional argument
 *
 * Reroute a user to a cleanpage of (if passed) arg
 * 
 * @param string $arg argument string to add to url
 *
 * @return null
 */

function clean_page($arg='')
{
	if (!$arg)
	{
		header("Location: ".htmlentities($_SERVER['SCRIPT_NAME'], ENT_QUOTES)."?time=".time());
		exit;
	}
	else
	{
		if (preg_match('!\?!si', $arg))
		{
			$add = "&time=";
		}
		else
		{
			$add = "?time=";
		}
		header("Location: $arg$add".time());
		exit;
	}
}

/** Print active status
 *
 * @param int $res status, 0 for inactive, 1 active
 *
 * @return string html containing status
 */
function get_status($res)
{
	if ($res == '0')
	{
		return "<FONT CLASS=\"inactive\">" . _('Inactive') . "</FONT>";
	}
	elseif ($res == '1')
	{
		return "<FONT CLASS=\"active\">" . _('Active') . "</FONT>";
	}
}

/** Parse string and substitute domain and serial
 *
 * @param string $val string to parse containing tokens '[ZONE]' and '[SERIAL]'
 * @param string $domain domain to subsitute for '[ZONE]'
 *
 * @return string interpolated/parsed string
 */
function parse_template_value($val, $domain)
{
	$serial = date("Ymd");
	$serial .= "00";

	$val = str_replace('[ZONE]', $domain, $val);
	$val = str_replace('[SERIAL]', $serial, $val);
	return $val;
}

/** Validate email address string
 *
 * @param string $address email address string
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_email($address) {
	$fields = preg_split("/@/", $address, 2);
	if((!preg_match("/^[0-9a-z]([-_.]?[0-9a-z])*$/i", $fields[0])) || (!isset($fields[1]) || $fields[1] == '' || !is_valid_hostname_fqdn($fields[1], 0))) {
		return false;
	}
	return true;
}

/** Validate numeric string
 *
 * @param string $string number
 *
 * @return boolean true if number, false otherwise
 */
function v_num($string) {
	if (!preg_match("/^[0-9]+$/i", $string)) { 
		return false ;
	} else {
		return true ;
	}
}

/** Debug print
 *
 * @param string $var debug statement
 *
 * @return null
 */
function debug_print($var) {
	echo "<pre style=\"border: 2px solid blue;\">\n";
	if (is_array($var)) { print_r($var) ; } else { echo $var ; } 
	echo "</pre>\n";
}

/** Set timezone (required for PHP5)
 *
 * Set timezone to configured tz or UTC it not set
 *
 * @return null
 */
function set_timezone() {
	global $timezone;
	
	if (function_exists('date_default_timezone_set')) {
		if (isset($timezone)) {
			date_default_timezone_set($timezone);
		} else if (!ini_get('date.timezone')) {
			date_default_timezone_set('UTC');	
		}
	}
}

/** Generate random salt for encryption
 *
 * @param int $len salt length (default=5)
 *
 * @return string salt string
 */
function generate_salt($len = 5) {
	$valid_characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890@#$%^*()_-!';
	$valid_len = strlen($valid_characters) - 1;
	$salt = "";

	for($i = 0; $i < $len; $i++) {
		$salt .= $valid_characters[rand(0, $valid_len)];
	}

	return $salt;
}

/** Extract salt from password
 *
 * @param string $password salted password
 *
 * @return string salt
 */
function extract_salt($password) {
	return substr(strchr($password, ':'), 1);
}

/** Generate salted password
 *
 * @param string $salt salt
 * @param string $pass password
 *
 * @return string salted password
 */
function mix_salt($salt, $pass) {
	return md5($salt.$pass).':'.$salt;
}

/** Generate random salt and salted password
 *
 * @param string $pass password
 *
 * @return salted password
 */
function gen_mix_salt($pass) {
	$salt = generate_salt();
	return mix_salt($salt, $pass);
}

?>
