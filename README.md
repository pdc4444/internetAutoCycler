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
* Change the variables in the script to your netbooter's local IP address, telnet port, username (default is admin), and password.
* (Optional) Change the $dns_servers array to any ip address of your choosing
* Make the internetAutoCycler script run via crontab (once a minute should be fine)
 
example crontab entry: 
* * * * * /usr/bin/php /path/to/script/internetAutoCycler.php > /dev/null 2>&1
