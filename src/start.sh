#!/bin/bash

#===============================================================================
#===============================================================================
#
#      PROJECT: LiMiT1
#         FILE: start.sh
#          SEE: https://github.com/ulkuehn/LiMiT1
#       AUTHOR: Ulrich Kuehn
#
#        USAGE: start.sh
#
#  DESCRIPTION: this script is executed by /etc/rc.local on boot time
#               it takes care of all necessary setup (interfaces, servers etc)
#
#===============================================================================
#===============================================================================


#===  FUNCTION  ================================================================
#         NAME: subnet
#  DESCRIPTION: subnet calculation
#  PARAMETER 1: ip (as dotted quad, e.g. 192.168.1.35)
#  PARAMETER 2: netmask (as dotted quad, e.g. 255.255.255.0)
#       RESULT: echo subnet (as dotted quad, e.g. 192.168.1.0)
#===============================================================================

subnet ()
{
  local ip1
  local ip2
  local ip3
  local ip4
  
  local nm1
  local nm2
  local nm3
  local nm4

  local sn1
  local sn2
  local sn3
  local sn4
  
  local x
  local subnet
  
  ip4="${1##*.}" ; x="${1%.*}"
  ip3="${x##*.}" ; x="${x%.*}"
  ip2="${x##*.}" ; x="${x%.*}"
  ip1="${x##*.}"   

  nm4="${2##*.}" ; x="${2%.*}"
  nm3="${x##*.}" ; x="${x%.*}"
  nm2="${x##*.}" ; x="${x%.*}"
  nm1="${x##*.}"

  let sn1="$ip1&$nm1"
  let sn2="$ip2&$nm2"
  let sn3="$ip3&$nm3"
  let sn4="$ip1&$nm4"

  subnet=$sn1.$sn2.$sn3.$sn4
  echo $subnet
}


#===  FUNCTION  ================================================================
#         NAME: rsubnet
#  DESCRIPTION: reverse subnet calculation
#  PARAMETER 1: ip (as dotted quad, e.g. 192.168.1.35)
#  PARAMETER 2: netmask (as dotted quad, e.g. 255.255.0.0)
#       RESULT: echo reverse subnet (as dotted quad, e.g. 168.192)
#===============================================================================

rsubnet ()
{
  local ip1
  local ip2
  local ip3
  local ip4
  
  local nm1
  local nm2
  local nm3
  local nm4

  local sn1
  local sn2
  local sn3
  local sn4
  
  local x
  local subnet
  
  ip4="${1##*.}" ; x="${1%.*}"
  ip3="${x##*.}" ; x="${x%.*}"
  ip2="${x##*.}" ; x="${x%.*}"
  ip1="${x##*.}"   

  nm4="${2##*.}" ; x="${2%.*}"
  nm3="${x##*.}" ; x="${x%.*}"
  nm2="${x##*.}" ; x="${x%.*}"
  nm1="${x##*.}"

  subnet=$ip1
  if [ $nm2 -ne 0 ]
  then
    subnet=$ip2.$subnet
    if [ $nm3 -ne 0 ]
    then
      subnet=$ip3.$subnet
    fi
  fi

  echo $subnet
}


#===  FUNCTION  ================================================================
#         NAME: maskcount
#  DESCRIPTION: calculate number of network bits from netmask
#  PARAMETER 1: netmask (as dotted quad, e.g. 255.255.255.0)
#       RESULT: echo number of network bits (as integer 0..31, e.g. 24)
#===============================================================================

maskcount ()
{
  local count=0
  local mask=".$1"
  local digits
  local bit

  while [ "$mask" != "" ]
  do
    digits="${mask##*.}"
    mask="${mask%.*}"
    while [ $digits -ge 1 ]
    do
      let "bit = $digits%2"
      let "count += bit"
      let "digits = $digits/2"
    done
  done

  echo $count
}



#===  FUNCTION  ================================================================
#         NAME: abort
#  DESCRIPTION: abort due to fatal errors
#  PARAMETER 1: message text
#  PARAMETER 2: if present, instead of blinking LED put it permanently on
#       RESULT: echo message and signal ACT LED
#===============================================================================

abort ()
{
  if [ $# -ne 0 ]
  then
    log aborting: $1
  fi
  
  if [ -w $data_dir ]
  then
    /bin/cp $logfile $data_dir
  fi
  
  blink
  exit 0
}


#===  FUNCTION  ================================================================
#         NAME: manage non fatal error
#  DESCRIPTION: add error bit and log error message
#  PARAMETER 1: index of error bit (0 .. 15)
#  PARAMETER 2: optional message text
#       RESULT: add error bit
#===============================================================================

error ()
{
  if [[ -n "$1" ]] && [[ $1 =~ ^[0-9]+$ ]] && [[ $1 -lt 15 ]]
  then
    ((errorValue|=1<<$1))
    echo $errorValue > $bootfile
    
    if [ $# -gt 1 ]
    then
      log +++ error bit $*
    fi
  fi
}


#===  FUNCTION  ================================================================
#         NAME: log
#  DESCRIPTION: print a log entry
#  PARAMETER 1: message to log
#       RESULT: message is logged with timestamp
#===============================================================================

log ()
{
  if [ -n "$1" ]
  then
    echo -e `date +"%Y.%m.%d %H:%M:%S"`: "$*"
    echo -e `date +"%Y.%m.%d %H:%M:%S"`: "$*" >> $logfile
  else
    echo
    echo >> $logfile
  fi
}



#===============================================================================
#===============================================================================
#===  MAIN SCRIPT  =============================================================
#===============================================================================
#===============================================================================

# switch LEDs off
echo "none" >/sys/class/leds/led0/trigger
echo "0" >/sys/class/leds/led0/brightness
# no led1 on pi zeroes
if [ -d /sys/class/leds/led1 ]
then
  echo "none" >/sys/class/leds/led1/trigger
  echo "0" >/sys/class/leds/led1/brightness
fi

# set green system LED to heartbeat mode
echo "heartbeat" >/sys/class/leds/led0/trigger


#-------------------------------------------------------------------------------
# do some self-awareness
#-------------------------------------------------------------------------------

_mysource="${BASH_SOURCE[0]}"
_mydir="${_mysource%/*}"
_myname="${_mysource##*/}"
_myname="${_myname%.*}"


#-------------------------------------------------------------------------------
# move into empty directory, so that "echo *" does not expand to a file listing
#-------------------------------------------------------------------------------

/bin/mkdir -p /tmp/empty
cd /tmp/empty


#-------------------------------------------------------------------------------
# include constants (no logging possible before that!)
#-------------------------------------------------------------------------------

. $_mydir/constants

log $_mysource script started\\n

log including constants ...
log constants file \"$_mydir/constants\" is: `/bin/cat $_mydir/constants | /bin/sed 's/^/\\\\n\\\\t/g'`
log ... constants included\\n


#-------------------------------------------------------------------------------
# define values for servers and deamons
#-------------------------------------------------------------------------------

# ssh server
sshd_configfile="$temp_dir/sshd.conf"
sshd_pidfile="$temp_dir/sshd.pid"

# name server
bind_configfile="$temp_dir/named.conf"
bind_forwardfile="$temp_dir/named.forward"
bind_reversefile="$temp_dir/named.reverse"

# dhcp server
dhcpd_configfile="$temp_dir/dhcpd.conf"
dhcpd_pidfile="$temp_dir/dhcpd.pid"
dhcpd_leasefile="$temp_dir/dhcpd.leases"

# dhcp client
dhclient_pidfile="$temp_dir/dhclient.pid"
dhclient_leasefile="$temp_dir/dhclient.leases"

# hostap daemon
hostapd_configfile="$temp_dir/hostapd.conf"

# lighttpd
lighttpd_configfile="$temp_dir/lighttpd.conf"
lighttpd_root="$base_dir/www"
lighttpd_port="80"


#-------------------------------------------------------------------------------
# include configuration
#-------------------------------------------------------------------------------

log including configuration ...

. $base_dir/$config_file
log configuration file \"$base_dir/$config_file\" is: `/bin/cat $base_dir/$config_file | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... configuration included\\n


#-------------------------------------------------------------------------------
# error management
#-------------------------------------------------------------------------------

log writing boot file ...

errorValue=0
echo $errorValue > $bootfile
/usr/bin/touch $bootingfile

log ... boot file written\\n


#-------------------------------------------------------------------------------
# check network interfaces
#   fatal: no wireless
#   not fatal: no wired
#-------------------------------------------------------------------------------

log checking network interfaces ...

wired=""
wireless=""
minusb=""
for interface in /sys/class/net/*
do
  # wireless
  /bin/udevadm info $interface | grep "DEVTYPE=wlan" > /dev/null
  if [ $? -eq 0 ]
  then
    # Raspberry Pi3 internal wifi ?
    /bin/udevadm info -q path $interface | grep ".mmc" > /dev/null
    if [ $? -eq 0 ]
    then
      wireless=$interface
      # prefer this interface over all usb ones
      minusb=0
      log $interface is wireless pi3 internal
      break
    fi
    
    # USB wifi dongle ?
    /bin/udevadm info -q path $interface | grep ".usb" > /dev/null
    if [ $? -eq 0 ]
    then
      usb=`/bin/udevadm info -q path $interface | cut -d':' -f1 | cut -d'.' -f4`
      # use dongle with lowest USB
      if [ -z "$minusb" ] || [ $usb -lt $minusb ]
      then
        minusb=$usb
        wireless=$interface
        log $interface is wireless usb-id $usb
      fi	
    fi 
  fi
  
  # wired
  /bin/udevadm info -q path $interface | grep ".usb/usb1/1-1/1-1.1" > /dev/null
  if [ $? -eq 0 ]
  then
    wired=$interface
    log $interface is wired
  fi
done

if [ -z "$wireless" ]
then
  abort "no wireless interface found"
fi

log ... checked network interfaces, wireless is $wireless, wired is $wired\\n


#-------------------------------------------------------------------------------
# set up wired interface
#-------------------------------------------------------------------------------

if [ -n "$wired" ]
then
  log setting up wired interface ...

  /bin/ip link set `basename $wired` name $wired_interface
  /sbin/ifconfig $wired_interface up
  carrier=`/bin/cat /sys/class/net/$wired_interface/carrier`

  log ... set up wired interface as $wired_interface, carrier is $carrier\\n
fi


#-------------------------------------------------------------------------------
# set up wireless interface
#-------------------------------------------------------------------------------

log setting up wireless interface ...

/bin/ip link set `basename $wireless` name $wireless_interface
/sbin/iw dev $wireless_interface set power_save off
power_save=`/sbin/iw dev $wireless_interface get power_save`

log ... set up wireless interface as $wireless_interface, $power_save\\n


#-------------------------------------------------------------------------------
# set hostap wifi IP
#   fatal: no IP
#-------------------------------------------------------------------------------

log setting hostap wifi IP ...

/sbin/ifconfig $wireless_interface up $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip netmask $__ip_mask
# check if setting of IP was successful
/sbin/ifconfig $wireless_interface | grep `echo $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip | /bin/sed 's/\./\\\\./g'` > /dev/null
if [ $? -ne 0 ]
then
  abort "couldn't set wifi IP"
fi

log ... hostap wifi IP set to $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip\\n


#-------------------------------------------------------------------------------
# configure sshd server
#-------------------------------------------------------------------------------

log configuring sshd server ...

echo "Port 22" > $sshd_configfile
if [ $develop_mode -eq 0 ]
then
  echo "ListenAddress $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip" >> $sshd_configfile
fi
echo "Protocol 2
PidFile $sshd_pidfile

HostKey /etc/ssh/ssh_host_rsa_key
HostKey /etc/ssh/ssh_host_dsa_key
HostKey /etc/ssh/ssh_host_ecdsa_key
HostKey /etc/ssh/ssh_host_ed25519_key

UsePrivilegeSeparation yes
LoginGraceTime 120
PermitRootLogin yes
PermitEmptyPasswords no
StrictModes yes

SyslogFacility AUTH
LogLevel ERROR

UsePAM yes
RSAAuthentication yes
PubkeyAuthentication yes
RhostsRSAAuthentication no
HostbasedAuthentication no
ChallengeResponseAuthentication no

X11Forwarding no
PrintMotd no
PrintLastLog yes
TCPKeepAlive yes
AcceptEnv LANG LC_*

Subsystem sftp /usr/lib/openssh/sftp-server
" >> $sshd_configfile

log config file \"$sshd_configfile\" is: `/bin/cat $sshd_configfile | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... sshd server configured\\n


#-------------------------------------------------------------------------------
# start sshd server
#   not fatal: no server
#-------------------------------------------------------------------------------

log starting sshd server ...

/usr/sbin/sshd -f $sshd_configfile

# check if sshd server is running
/bin/ps -C sshd > /dev/null
if [ $? -ne 0 ]
then
  error $errorNoSSHD "couldn't start sshd server\\n"
else
  log ... sshd server started\\n
fi


#-------------------------------------------------------------------------------
# configure name server
#-------------------------------------------------------------------------------

log configuring name server ...

echo "options
{
  listen-on { 127.0.0.1; $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip; };
  allow-transfer { none; };
  allow-query { any; };
  allow-recursion { any; };
  // cf. http://www.orsn.net/en/tech/pubdns/
  forwarders
  {
    188.165.175.115;
    37.187.193.30;
    37.187.99.178;
    87.118.126.225;
    109.230.224.42;
    72.80.25.34;
    178.209.50.232;
    84.200.83.54;
    79.133.62.62;
    212.224.71.71;
  };
};

zone \".\" 
{
  type hint;
  file \"/etc/bind/db.orsn\";
};

zone \"localhost\" 
{
  type master;
  file \"/etc/bind/db.local\";
};

zone \"127.in-addr.arpa\" 
{
  type master;
  file \"/etc/bind/db.127\";
};

zone \"0.in-addr.arpa\" 
{
  type master;
  file \"/etc/bind/db.0\";
};

zone \"255.in-addr.arpa\" 
{
  type master;
  file \"/etc/bind/db.255\";
};

zone \"$__dns_domain_name\"
{
  type master;
  file \"$bind_forwardfile\";
};

zone \"$__ip_ip3.$__ip_ip2.$__ip_ip1.in-addr.arpa\"
{
  type master;
  file \"$bind_reversefile\";
};" > $bind_configfile

echo "\$TTL 3600
@  IN SOA $__dns_server_name.$__dns_domain_name. root.localhost. (
  2007010401 ; Serial
        3600 ; Refresh [1h]
         600 ; Retry   [10m]
       86400 ; Expire  [1d]
         600); Negative Cache TTL [1h]

@  IN NS $__dns_server_name.$__dns_domain_name.

$__dns_server_name  IN A $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip" > $bind_forwardfile

echo "@  IN SOA $__dns_server_name.$__dns_domain_name. root.localhost. (
  2007010401 ; Serial
        3600 ; Refresh [1h]
         600 ; Retry   [10m]
       86400 ; Expire  [1d]
         600); Negative Cache TTL [1h]

@  IN NS $__dns_server_name.$__dns_domain_name.
$__ip_ip  IN PTR $__dns_server_name.$__dns_domain_name." > $bind_reversefile

log config file \"$bind_configfile\" is: `/bin/cat $bind_configfile | /bin/sed 's/^/\\\\n\\\\t/g'`
log forward file \"$bind_forwardfile\" is: `/bin/cat $bind_forwardfile | /bin/sed 's/^/\\\\n\\\\t/g'`
log reverse file \"$bind_reversefile\" is: `/bin/cat $bind_reversefile | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... configured name server\\n


#-------------------------------------------------------------------------------
# start named server
#   not fatal: no server (use ip address instead)
#-------------------------------------------------------------------------------

log starting name server ...

/usr/sbin/named -c $bind_configfile 2>&1

# check if named server is running
/bin/ps -C named > /dev/null
if [ $? -ne 0 ]
then
  error $errorNoNamed "couldn't start named server\\n"
else
  log ... name server started\\n
fi


#-------------------------------------------------------------------------------
# configure DHCP server
#-------------------------------------------------------------------------------

log configuring DHCP server ...

echo "option domain-name-servers $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip;
option domain-name \"$__dns_domain_name\";
log-facility user;
subnet $__ip_ip1.$__ip_ip2.$__ip_ip3.0 netmask $__ip_mask
{
  range $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_dhcp1 $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_dhcp2;
  option routers $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip;
}" > $dhcpd_configfile

log config file is: `/bin/cat $dhcpd_configfile | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... DHCP server configured\\n


#-------------------------------------------------------------------------------
# start DHCP server
#   fatal: no server
#-------------------------------------------------------------------------------

log starting DHCP server ...
/bin/touch $dhcpd_leasefile
/usr/sbin/dhcpd -cf $dhcpd_configfile -pf $dhcpd_pidfile -lf $dhcpd_leasefile $wifi_interface 2>&1

# check if DHCP server is running
/bin/ps -C dhcpd > /dev/null
if [ $? -ne 0 ]
then
  abort "couldn't start dhcp server"
fi

log ... DHCP server started\\n


#-------------------------------------------------------------------------------
# configure host AP server
#-------------------------------------------------------------------------------

log configuring host AP server ...

echo "interface=$wireless_interface
driver=nl80211
hw_mode=g
ieee80211n=1
wmm_enabled=1
# 
logger_syslog=-1
logger_syslog_level=2
#
auth_algs=1
wpa=2
macaddr_acl=0
wpa_key_mgmt=WPA-PSK
wpa_pairwise=CCMP
rsn_pairwise=CCMP
#
ssid=$__wlan_ssid
wpa_passphrase=$__wlan_password
channel=$__wlan_channel" > $hostapd_configfile

log config file is: `/bin/cat $hostapd_configfile | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... host AP server configured\\n


#-------------------------------------------------------------------------------
# start host AP server
#   fatal: no server
#-------------------------------------------------------------------------------

log starting host AP server ...

/usr/sbin/hostapd -B $hostapd_configfile 2>&1

# check if host AP server is running
/bin/ps -C hostapd > /dev/null
if [ $? -ne 0 ]
then
  abort "couldn't start hostapd server"
fi

log ... host AP server started\\n


#-------------------------------------------------------------------------------
# configure lighttpd server
#-------------------------------------------------------------------------------

log configuring lighttpd server ...

echo "server.document-root = \"$lighttpd_root\"
server.bind = \"$__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip\"
server.port = $lighttpd_port
index-file.names = ( \"index.php\", \"index.html\" )
server.modules = ( \"mod_fastcgi\" )
fastcgi.server = ( \".php\" => (( \"bin-path\" => \"/usr/bin/php-cgi\", \"socket\" => \"/tmp/php.sock\" )))
mimetype.assign = ( \".html\" => \"text/html\", \".htm\" => \"text/html\", \".png\" => \"image/png\", \".css\" => \"text/css\", \".js\" => \"application/javascript\" )
static-file.exclude-extensions = ( \".php\" )
server.errorlog = \"/var/log/lighttpd_error.log\"" > $lighttpd_configfile

log config file is: `/bin/cat $lighttpd_configfile | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... lighttpd server configured\\n


#-------------------------------------------------------------------------------
# start lighttpd server
#   fatal: no server
#-------------------------------------------------------------------------------

log starting lighttpd server ...

/usr/sbin/lighttpd -f $lighttpd_configfile 2>&1

# check if lighttpd server is running
/bin/ps -C lighttpd > /dev/null
if [ $? -ne 0 ]
then
  abort "couldn't start lighttpd server"
fi

log ... lighttpd server started\\n

#-------------------------------------------------------------------------------
# activate routing
#   not fatal: no routing
#-------------------------------------------------------------------------------

log activating routing ...

/sbin/sysctl -w net.ipv4.ip_forward=1

if [ `/sbin/sysctl -n net.ipv4.ip_forward` -ne 1 ]
then
  error $errorNoRouting "couldn't activate routing\\n"
else
  log ... routing activated\\n
fi


#-------------------------------------------------------------------------------
# configure iptables for server access
# (to avoid having these connections redirected to sslsplit)
#-------------------------------------------------------------------------------

log configuring iptables for server access ...

# do not redirect packets from wireless interface destined for local webserver
/sbin/iptables -v --table nat  --append PREROUTING  --protocol tcp  --dport 80  --destination $(subnet $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip $__ip_mask)/$(maskcount $__ip_mask)  --jump REDIRECT  --to-ports $lighttpd_port

log ... iptables for server access configured\\n


#-------------------------------------------------------------------------------
# configure mysql server
#-------------------------------------------------------------------------------

log configuring mysql server ...

if [ ! -d /var/run/mysqld ]
then
  /bin/mkdir -p /var/run/mysqld/
fi

bind="127.0.0.1"
if [ $develop_mode -eq 1 ]
then
  bind="$__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip"
fi
echo "[mysqld]
user = root
pid-file = $mysqld_pidfile
socket = /var/run/mysqld/mysqld.sock
port = $mysqld_port
basedir = /usr
datadir = $mysqld_datadir
tmpdir = /tmp
bind-address = $bind
key_buffer = 16K
max_allowed_packet = 16M
skip-external-locking
sort_buffer = 64K
net_buffer_length = 2K
query_cache_type = 1
query_cache_limit = 1M
query_cache_size = 16M
" > $mysqld_configfile

log config file \"$mysqld_configfile\" is: `/bin/cat $mysqld_configfile | /bin/sed 's/^/\\\\n\\\\t/g'`
log ... mysql server configured\\n


#-------------------------------------------------------------------------------
# create data directory if necessary
#   not fatal: no directory
#-------------------------------------------------------------------------------

log checking for data directory ...

if [ ! -d $data_dir ]
then
  log no data directory, creating it
  log result of \"/bin/mkdir -vp $data_dir\" is:
  log `/bin/mkdir -vp $data_dir 2>&1`
  if [ ! -d $data_dir ]
  then
    error $errorNoDataDir "cannot create directory $data_dir\\n"
  fi
fi

log ... checked for data directory\\n


#-------------------------------------------------------------------------------
# initialize mysql tables on sd card if necessary
#   not fatal: no tables
#-------------------------------------------------------------------------------

if [ ! -d $mysqld_datadir ]
then
  log initializing mysql base tables on sd card ...
  
  /usr/bin/mysql_install_db --defaults-file=$mysqld_configfile
  if [ ! -d $mysqld_datadir ]
  then
    error $errorNoBaseTables1 "couldn't initialize mysql base tables on sd card\\n"
  else
    log ... mysql base tables on sd card initialized\\n
  fi
fi


#-------------------------------------------------------------------------------
# start mysql server
#   not fatal: no server
#-------------------------------------------------------------------------------

log starting mysql server ...

/usr/sbin/mysqld --defaults-file=$mysqld_configfile 2>&1 &

# check if mysql server is running
sleep 5
/bin/ps -C mysqld > /dev/null
if [ $? -ne 0 ]
then
  error $errorNoMysqld1 "couldn't start mysql server\\n"
else
  log ... mysql server started\\n


#-------------------------------------------------------------------------------
# create and initialize database on sd card if necessary
#   not fatal: no database
#-------------------------------------------------------------------------------

  log checking for database $database_name ...
  
  echo "use $database_name" | /usr/bin/mysql
  if [ $? -eq 0 ]
  then
    log ... database found\\n
  else
    log database not found, creating it on sd card ...
    
    echo "create database $database_name character set utf8" | /usr/bin/mysql
    log ... database $database_name created\\n

    log initializing database $database_name using $database_initfile ...  
    cat $database_initfile | /usr/bin/mysql $database_name
    log ... database $database_name initialized using $database_initfile\\n

    log initializing database $database_name using $ciphers_initfile ...  
    cat $ciphers_initfile | /usr/bin/mysql $database_name
    log ... database $database_name initialized using $ciphers_initfile\\n
    
    log initializing database $database_name using $ciphersuites_cmd ...  
    $ciphersuites_cmd
    log ... database $database_name initialized using $ciphersuites_cmd\\n
    
    echo "use $database_name" | /usr/bin/mysql
    if [ $? -ne 0 ]
    then
      error $errorNoDatabase1 "couldn't create database\\n"
    fi
  fi

fi


#-------------------------------------------------------------------------------
# check for USB memory stick and mount it
#   not fatal: no mount
#-------------------------------------------------------------------------------

log checking for memory stick ...

if [ ! -d "/sys/class/block/sda1" ]
then
  log ... no memory stick found, using sd card instead\\n
else
  log memory stick found

  log stopping mysql server ...
  echo "shutdown" | /usr/bin/mysql
  sleep 5
  /bin/ps -C mysqld > /dev/null
  if [ $? -eq 0 ]
  then
    error $errorMysqld "couldn't stop mysql server\\n"
  else
    log ... mysql server stopped\\n
  
    /bin/mount -o iocharset=utf8 -t vfat /dev/sda1 $data_dir
    if [ $? -ne 0 ]
    then
      error $errorNoMount "couldn't mount USB file system to $data_dir, recurring to sd card\\n"
    else
      log `/bin/df -h | /bin/grep $data_dir`
      log ... memory stick mounted\\n    

#-------------------------------------------------------------------------------
# initialize mysql tables on stick if necessary
#   not fatal: no tables
#-------------------------------------------------------------------------------

      if [ ! -d $mysqld_datadir ]
      then
        log initializing mysql base tables on memory stick ...
        
        /usr/bin/mysql_install_db --defaults-file=$mysqld_configfile
        if [ ! -d $mysqld_datadir ]
        then
          /bin/umount /dev/sda1
          error $errorNoBaseTables2 "couldn't initialize mysql base tables on memory stick \\n"
        else
          log ... mysql base tables on memory stick initialized\\n
        fi
      fi


#-------------------------------------------------------------------------------
# start mysql server
#   not fatal: no server
#-------------------------------------------------------------------------------

      log starting mysql server ...

      /usr/sbin/mysqld --defaults-file=$mysqld_configfile 2>&1 &

      # check if mysql server is running
      sleep 5
      /bin/ps -C mysqld > /dev/null
      if [ $? -ne 0 ]
      then
        error $errorNoMysqld2 "couldn't start mysql server\\n"
        # unmount stick
        /bin/umount /dev/sda1
        # start mysql on sd card
        /usr/sbin/mysqld --defaults-file=$mysqld_configfile 2>&1 &
        sleep 5
        /bin/ps -C mysqld > /dev/null
        if [ $? -ne 0 ]
        then
          error $errorNoMysqld1 "couldn't start mysql server\\n"
        fi
      else
        log ... mysql server started\\n


#-------------------------------------------------------------------------------
# create and initialize database on memory stick if necessary
#   not fatal: no database
#-------------------------------------------------------------------------------

        log checking for database $database_name ...
        
        echo "use $database_name" | /usr/bin/mysql
        if [ $? -eq 0 ]
        then
          log ... database found\\n
        else
          log database not found, creating it on memory stick  ...
          
          echo "create database $database_name character set utf8" | /usr/bin/mysql
          log ... database $database_name created\\n

          log initializing database $database_name using $database_initfile ...  
          cat $database_initfile | /usr/bin/mysql $database_name
          log ... database $database_name initialized using $database_initfile\\n

          log initializing database $database_name using $ciphers_initfile ...  
          cat $ciphers_initfile | /usr/bin/mysql $database_name
          log ... database $database_name initialized using $ciphers_initfile\\n
          
          log initializing database $database_name using $ciphersuites_cmd ...  
          $ciphersuites_cmd
          log ... database $database_name initialized using $ciphersuites_cmd\\n
          
          echo "use $database_name" | /usr/bin/mysql
          if [ $? -ne 0 ]
          then
            error $errorNoDatabase2 "couldn't create database\\n"
            log stopping mysql server ...
            echo "shutdown" | /usr/bin/mysql
            sleep 5
            /bin/ps -C mysqld > /dev/null
            if [ $? -eq 0 ]
            then
              error $errorMysqld "couldn't stop mysql server\\n"
            else
              log ... mysql server stopped\\n
              # unmount stick
              /bin/umount /dev/sda1
              # start mysql on sd card
              /usr/sbin/mysqld --defaults-file=$mysqld_configfile 2>&1 &
              sleep 5
              /bin/ps -C mysqld > /dev/null
              if [ $? -ne 0 ]
              then
                error $errorNoMysqld1 "couldn't start mysql server\\n"
              fi
            fi
          fi
        fi
      fi
    fi
  fi
fi


#-------------------------------------------------------------------------------
# we're all set, signal success
#-------------------------------------------------------------------------------

# set green system LED to configured mode
log setting LED ...
if [ -n "$__led1" ]
then
  msOn=${__led1% *}
  msOff=${__led1#* }
  
  # check for raspberry zeroes (they have revisions starting with "9000")
  /bin/cat /proc/cpuinfo |grep "^Revision.*9000"
  # zeroes do have an inverse LED logic (apparently for cost reasons), we swap on and off values then
  if [ $? -eq 0 ]
  then
    x=$msOn
    msOn=$msOff
    msOff=$x
  fi
  
  if [[ "$msOn" == ?(-)+([0-9]) ]] && [[ "$msOff" == ?(-)+([0-9]) ]]
  then
    if [ $msOn -eq 0 ]
    then
      echo "none" >/sys/class/leds/led0/trigger
      echo 0 >/sys/class/leds/led0/brightness
      log LED is off
    else
      if [ $msOff -eq 0 ]
      then
        echo "none" >/sys/class/leds/led0/trigger
        echo 1 >/sys/class/leds/led0/brightness
        log LED is on
      else
        echo "timer" >/sys/class/leds/led0/trigger
        echo $msOn >/sys/class/leds/led0/delay_on
        echo $msOff >/sys/class/leds/led0/delay_off
        log LED is $msOn ms on, $msOff ms off
      fi
    fi
  else
    log LED not set \(illegal configured value\)
  fi
else
  log LED not set \(value not configured\)
fi
log ... LED set\\n


# if no error occured, switch hdmi video off to save power
if [ $errorValue -eq 0 ]
then
  log no errors, switching hdmi video off to save power\\n
  /opt/vc/bin/tvservice -o
fi

log all set, $_mysource script finished\\n

# remove booting flag file to signal end of booting process
/bin/rm $bootingfile

exit 0
