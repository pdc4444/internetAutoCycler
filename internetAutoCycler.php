<?php
/**
 * The purpose of this script is to check if we can reach public dns servers via a ping.
 * If all ping attempts fail, we use an expect script to power cycle the group 1 outlets.
 * 
 * Requirements:
 * Linux based OS
 * command line php
 * command line expect
 * A Synaccess netBooter™ Model: NP-05B that is accessible via telnet.
 * The netbooter must be configured so that Group 1 outlets are connected to both your modem and router.
 * 
 * Setup:
 * Ensure the requirements are met
 * Clone this git repo
 * Change the variables in the script to your netbooter's local IP address, telnet port, username (default is admin), and password.
 * (Optional) Change the $dns_servers array to any ip address of your choosing
 * Make the internetAutoCycler script run via crontab (once a minute should be fine)
 * 
 * example crontab entry: * * * * * /usr/bin/php /path/to/script/internetAutoCycler.php > /dev/null 2>&1
 */

$script_ini = __DIR__ . DIRECTORY_SEPARATOR . 'script_config.ini';
$script_config = parse_ini_file($script_ini, TRUE, INI_SCANNER_RAW);
exitIfRunning($script_config);
$telnet_handler = __DIR__ . DIRECTORY_SEPARATOR . 'toggleNetbooterOutlets.tcl';

foreach ($script_config['dns_servers'] as $ip) {
    if (pingchecker($ip, $script_config) !== FALSE) {
        //If one server responds, clearly the internet is working so exit!
        exit();
    }
}

//If we haven't exited by this point, then let's powercycle the Netbooter outlets!
$base_cmd = $script_config['binaries']['expect'] . ' ' . $telnet_handler . ' ' . $script_config['netbooter']['ip'] . ' ' . $script_config['netbooter']['port'] . ' ' . $script_config['netbooter']['username'] . ' ' . $script_config['netbooter']['password'];

//Turn Off Grouped Outlets Via Telnet expect script
$shell_cmd = $base_cmd . ' OFF';
exec($shell_cmd);

//Wait half a minute then turn the outlets back on
sleep(30);
$shell_cmd = $base_cmd . ' ON';
exec($shell_cmd);

/**
 * Checks if the current script is already running, if so we exit.
 * 
 * @param array $script_config - The script configuration array
 */
function exitIfRunning($script_config)
{
    $grep = $script_config['binaries']['grep'];
    $file_name = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', __FILE__);
    $results = shell_exec($script_config['binaries']['ps'] . " -ef | " . $grep . " '" . $file_name . "' | " . $grep . " -v 'grep' | " . $grep . " -v '/bin/sh -c");
    $result_array = explode("\n", trim($results));
    if (count($result_array) > 1) {
        exit();
    }
}

/**
 * Takes an IP address and checks to see if the host responds
 *  
 * @param string $ip - The IP address
 * @param array $script_config - The script configuration array
 * @return boolean
 */
function pingChecker($ip, $script_config)
{
    $shell_cmd =  $script_config['binaries']['ping'] . ' -c 5 ' . $ip;
    $raw_result = shell_exec($shell_cmd);
    $result_array = explode("\n", $raw_result);
    $result = extractPingResult($result_array);

    if ($result) {
        $ping_stats = explode(',', $result);
        $received = str_replace(' received', '', trim($ping_stats[1]));

        if (intval($received) < 1) {
            $result = FALSE;
        }
    }

    return $result;
}

/**
 * Takes the raw data from the Linux command line ping utility and returns
 * the summary line.
 * 
 * @param string $result_array - The full ping result from Linux.
 * @return boolean (false) || string
 */
function extractPingResult($result_array)
{
    $result_line = FALSE;
    foreach ($result_array as $line) {
        if (strpos($line, 'transmitted') !== FALSE) {
            $result_line = $line;
        }
    }
    return $result_line;
}