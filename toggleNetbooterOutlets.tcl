#!/usr/bin/expect
set ip [lindex $argv 0]
set port [lindex $argv 1]
set user [lindex $argv 2]
set password [lindex $argv 3]
set action [lindex $argv 4]
#Example of expected arguments to run script usage "/usr/bin/expect telnet.tcl IP PORT USER PASS ON"

if {$action eq "ON"} {
	#gpset n v Sets outlet group #n to v(0 or 1)
	#Group 2 will be set to 1 which is on state.
	set command "gpset 1 1"
} else {
	set command "gpset 1 0"
}

spawn telnet "$ip" "$port"

set timeout 15
expect {
	"*User ID:*" {
		send "$user\n"
		sleep 1
	}
}
set timeout 15 
expect {
	"*assword*" {
		send "$password\n"
		sleep 1
	}
}
set timeout 5
expect {
	"*>*" {
		sleep 5
		send "$command\n"
	}
}
set timeout 5
expect {
	"*>*" {
		#this sends ^] to exit the telnet session
		sleep 5
		send "\x1d"
	}
}
set timeout 5
expect {
	"*telnet*" {
		sleep 5
		send "quit\n"
	}
}
expect eof
exit