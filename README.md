# internetAutoCycler
The purpose of this script is to check if we can reach public dns servers via a ping.

If all ping attempts fail, we use an expect script to power cycle the group 1 outlets.

Requirements:
* Linux based OS
* command line php
* command line expect
* A Synaccess netBooter™ Model: NP-05B that is accessible via telnet.
* The netbooter must be configured so that Group 1 outlets are connected to both your modem and router.
 
Setup:
* Ensure the requirements are met
* Clone this git repo
* Fill out the script_config.ini with your Netbooters information
* (Optional) Change the dns_servers in the script_config.ini to any ip address of your choosing
* (Optional) Change the binaries file locations in the script_config.ini if the binaries are in a diff location on your distro
* Make the internetAutoCycler script run via crontab (once a minute should be fine)
 
example crontab entry: 
"* * * * * /usr/bin/php /path/to/script/internetAutoCycler.php > /dev/null 2>&1"
