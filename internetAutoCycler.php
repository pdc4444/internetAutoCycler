<?php
/*
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
exitIfRunning();

//Public DNS Servers
$dns_servers = [
    '8.8.8.8',          //Google
    '1.1.1.1',          //Cloudflare
    '75.75.75.75',      //Comcast
    '198.101.242.72',   //Alternate DNS
    '216.146.35.35',    //Dyn
    '64.6.64.6'         //Verisign
];

$netbooter_ip = '';
$telnet_port = '';
$username = '';
$password = '';
$telnet_handler = __DIR__ . DIRECTORY_SEPARATOR . 'toggleNetbooterOutlets.tcl';

foreach ($dns_servers as $ip) {
    if (pingchecker($ip) !== FALSE) {
        //If one server responds, clearly the internet is working so exit!
        exit();
    }
}

//If we haven't exited by this point, then let's powercycle the Netbooter outlets!
$base_cmd = '/usr/bin/expect ' . $telnet_handler . ' ' . $netbooter_ip . ' ' . $telnet_port . ' ' . $username . ' ' . $password;

//Turn Off Grouped Outlets Via Telnet expect script
$shell_cmd = $base_cmd . ' OFF';
exec($shell_cmd);

//Wait half a minute then turn the outlets back on
sleep(30);
$shell_cmd = $base_cmd . ' ON';
exec($shell_cmd);

/*
 * Checks if the current script is already running, if so we exit.
 */
function exitIfRunning()
{
    $file_name = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', __FILE__);
    $results = shell_exec("ps -ef | grep '" . $file_name . "' | grep -v 'grep'");
    $result_array = explode("\n", trim($results));
    if (count($result_array) > 1) {
        exit();
    }
}

/*
 * Takes an IP address and checks to see if the host responds
 *  
 * @string $ip - The IP address
 * return boolean
 */
function pingChecker($ip)
{
    $shell_cmd = 'ping -c 5 ' . $ip;
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

/*
 * Takes the raw data from the Linux command line ping utility and returns
 * the summary line.
 * 
 * @string $result_array - The full ping result from Linux.
 * return FALSE || string
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