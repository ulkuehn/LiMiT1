#!/bin/bash

#===============================================================================
#===============================================================================
#
#      PROJECT: LiMiT1
#         FILE: limitify.sh
#          SEE: https://github.com/ulkuehn/LiMiT1
#       AUTHOR: Ulrich Kuehn
#
#        USAGE: limitify.sh [-i|-f|-s]
#
#  DESCRIPTION: -i: the script modifies a fresh raspbian install for use as
#                   a LiMiT1 system which will be available after reboot
#               -f: does so even if a LiMiT1 system is already installed
#               -s: the script assembles the necessary files of an existing
#                   LiMiT1 system into a tar archive for transfer
#
#===============================================================================
#===============================================================================


### base configuration

# name (hostname, base dir)
myname="limit1"
# full name
my_name="LiMiT1"
# major version; full version number is "major.minor"
my_major="1.0"
# minor version counter, may get updated automagically by git commit
my_minor="4"

# password (leave empty for setting interactively)
password="limit1"

# name of configuration file
config_file="configuration"

# name of constants file
constants_file="constants"


### system configuration

# keyboard
keyboard_model="pc105"
keyboard_layout="de"

# console
console_charmap="UTF-8"

# locale
locale_country="de_DE"
locale_coding="UTF-8"

# timezone
timezone="Europe/Berlin"


### default configuration

# menu boxes
__suchbox="1"
__dekodbox="1"
__whoisbox="1"
__usetabs="1"

# skin
__skin=""

# tables
__klick="oncontextmenu"
__zeilen="5 10 25"

# dns
__dns_server_name="lim"
__dns_domain_name="it1"

# wifi
__wlan_ssid="limit1"
__wlan_password="limit1limit"
__wlan_channel="5"

# local wifi network
__ip_ip1="172"
__ip_ip2="16"
__ip_ip3="0"

# internet routing
__internet_aufzeichnung="1"

# ports
__tcp_ports=""
__ssl_ports="443"


### software

# list of packages to install
packages="bzip2 patch make gcc pkg-config usbutils unzip \
libssl-dev libevent-dev libusb-1.0-0-dev \
bind9 dnsutils whois \
wireless-tools wpasupplicant hostapd \
isc-dhcp-server isc-dhcp-client \
lighttpd php5 php5-cgi php5-cli php5-mysql \
usb-modeswitch wvdial tcpdump iptables protobuf-compiler exiv2 \
tcl curl mariadb-server"

# parameters for non-interactive installation of packages
set_selections=("mysql-server-5.5 mysql-server/root_password password root" \
"mysql-server-5.5 mysql-server/root_password_again password root")

# list of preinstalled services to disable
services="ntp ssh bind9 hostapd lighttpd dhcpcd dhcpd isc-dhcp-client isc-dhcp-server mysql"

# revision numbers of pi2 and pi3 models
pi2revisions="a01040 a01041 a21041 a22042"
pi3revisions="a02082 a22082"

# list of files to ship
files2ship="start.sh ciphers.sql ciphersuites.php ciphersuites.txt initialize.sql sslsplit-latest.tar.bz2 pxyconn.c.patch 40-usb_modeswitch.rules.patch www"


### no changes beyond this line!
basedir="/$myname"
tempdir="/$myname/tmp"
datadir="/$myname/data"

logfile="${BASH_SOURCE[0]}".log



#===  FUNCTION  ================================================================
#         NAME: abort
#  DESCRIPTION: abort due to fatal errors
#   PARAMETERS: message text
#       RESULT: echo message
#===============================================================================

abort ()
{
  if [ $# -ne 0 ]
  then
    log Aborting: $*
  fi
  
  exit 0
}

#===  FUNCTION  ================================================================
#         NAME: log
#  DESCRIPTION: print a log entry
#   PARAMETERS: message to log
#       RESULT: message is logged with timestamp
#===============================================================================

log ()
{
  if [ "$1" != "" ]
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

if [ -f $logfile ]
then
  /bin/mv $logfile $logfile.old
fi


#-------------------------------------------------------------------------------
# shipping mode?
#-------------------------------------------------------------------------------

log checking mode ...

usage="usage: use -s for shipping mode, -i for install mode or -f for forced install"

while getopts ":ifsh" o
do
  case "$o" in
  "h" )
    echo $usage
    exit 0
    ;;
  "s" ) 
    log shipping mode ...
    if [ ! -d $basedir ]
    then
      abort "$basedir doesn't exist"
    fi

    log `/bin/tar cjvf $myname.tar.bz2 -C $basedir $files2ship 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`
    log ... shipping done\\n
    exit
    ;;
  "i" )
    if [ -d $basedir ]
    then
      abort "$basedir exists -- use -f to enforce install"
    fi
    ;;
  "f" )
    if [ -d $basedir ]
    then
      /bin/rm --one-file-system -R $basedir
    fi
    ;;
  * )
    abort invalid option -$OPTARG  $usage
    ;;
  esac
done

if [ -z "$o" ]
then
  abort $usage
fi

log non-shipping mode

log ... mode checked\\n


#-------------------------------------------------------------------------------
# are we root/sudo or what?
#-------------------------------------------------------------------------------

log checking user privileges ...

uid=`/usr/bin/id -u`
if [ $uid -gt 0 ]
then
  abort user is not root, please invoke using sudo
fi

log ... user privileges checked\\n


#-------------------------------------------------------------------------------
# check system (model, base directory)
#-------------------------------------------------------------------------------

log checking system state ...

model=0
revision=`/bin/cat /proc/cpuinfo | /bin/grep -i "^Revision" | cut -d':' -f2 | tr -d ' '`
for rev in $pi2revisions
do
  if [ $rev == $revision ]
  then
    model=2
    log "running on a Raspberry Pi2 (revision $revision)"
  fi
done
for rev in $pi3revisions
do
  if [ $rev == $revision ]
  then
    model=3
    log "running on a Raspberry Pi3 (revision $revision)"
  fi
done

if [ $model -lt 2 ]
then
  abort not running on a Raspberry Pi2 or Pi3
fi

log ... system state checked\\n


#-------------------------------------------------------------------------------
# set root password
#-------------------------------------------------------------------------------

log setting root password ...

while [ -z "$password" ]
do
  echo -n "Root-Passwort: "
  read password
done

echo -e "$password\n$password" | /usr/bin/passwd root

log root password set\\n


#-------------------------------------------------------------------------------
# system update goes first
#-------------------------------------------------------------------------------

log updating system ...

log `/usr/bin/apt-get -y update 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`
log `/usr/bin/apt-get -y upgrade 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... system updated\\n


#-------------------------------------------------------------------------------
# change hostname
#-------------------------------------------------------------------------------

log changing hostname to $myname ...

/bin/cat <<LIMIT1 > /etc/hosts
127.0.0.1   localhost
127.0.0.1   $myname    
LIMIT1
echo $myname > /etc/hostname
/bin/hostname $myname

log ... hostname changed\\n


#-------------------------------------------------------------------------------
# change console keyboard and setup
#-------------------------------------------------------------------------------

log changing console setup ...

/bin/cat <<LIMIT1 > /etc/default/keyboard
# KEYBOARD CONFIGURATION FILE
# Consult the keyboard(5) manual page.

XKBMODEL="$keyboard_model"
XKBLAYOUT="$keyboard_layout"
BACKSPACE="guess"
LIMIT1

/bin/cat <<LIMIT1 > /etc/default/console-setup
# CONFIGURATION FILE FOR SETUPCON
# Consult the console-setup(5) manual page.

ACTIVE_CONSOLES="/dev/tty[1-6]"
CHARMAP="$console_charmap"
CODESET="guess"
FONTFACE="Fixed"
FONTSIZE="8x16"
LIMIT1

/bin/setupcon

log ... console setup changed\\n


#-------------------------------------------------------------------------------
# change locale
#-------------------------------------------------------------------------------

log changing locale ...

/bin/rm -rf /usr/lib/locale/locale-archive
/usr/bin/localedef -i en_GB -c -f UTF-8 -A /usr/share/locale/locale.alias en_GB.UTF-8
/usr/bin/localedef -i $locale_country -c -f $locale_coding -A /usr/share/locale/locale.alias de_DE.UTF-8
echo LANG=de_DE.UTF-8 > /etc/default/locale

log ... locale changed\\n


#-------------------------------------------------------------------------------
# change timezone
#-------------------------------------------------------------------------------

log changing timezone ...

echo $timezone > /etc/timezone 
log `/usr/sbin/dpkg-reconfigure -f noninteractive tzdata 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... timezone changed\\n


#-------------------------------------------------------------------------------
# install packages
#-------------------------------------------------------------------------------

log installing packages ...

for i in ${!set_selections[*]}
do
  echo ${set_selections[$i]} | /usr/bin/debconf-set-selections
done

log `/usr/bin/apt-get -y install $packages 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... packages installed\\n


#-------------------------------------------------------------------------------
# disable services
#-------------------------------------------------------------------------------

log disabling services ...

log `/bin/systemctl disable $services 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... services disabled\\n


#-------------------------------------------------------------------------------
# configure networking
#-------------------------------------------------------------------------------

log configuring networking ...

/bin/cat <<LIMIT1 > /etc/network/interfaces
auto lo
iface lo inet loopback
LIMIT1

log ... networking configured\\n


#-------------------------------------------------------------------------------
# configure tmpfs
#-------------------------------------------------------------------------------

log configuring tmpfs ...

/bin/cat <<LIMIT1 > /etc/default/tmpfs
RAMLOCK=no
RAMSHM=no
RAMTMP=yes
RUN_SIZE=50%
LIMIT1

log ... tmpfs configured\\n


#-------------------------------------------------------------------------------
# configure mounts
#-------------------------------------------------------------------------------

log configuring mounts ...

/bin/cat <<LIMIT1 > /etc/fstab
proc            /proc         proc    defaults                                    0 0
/dev/mmcblk0p1  /boot         vfat    ro                                          0 2
/dev/mmcblk0p2  /             ext4    defaults,noatime                            0 1
tmpfs           /tmp          tmpfs   defaults,noatime,nosuid,mode=0777,size=50%  0 0
tmpfs           /var/log      tmpfs   defaults,noatime,nosuid,mode=0777,size=50%  0 0
tmpfs           $basedir/tmp  tmpfs   defaults,noatime,nosuid,mode=0777,size=50%  0 0
LIMIT1

log ... mounts configured\\n


#-------------------------------------------------------------------------------
# get dns root hints
#-------------------------------------------------------------------------------

log getting dns root hints ...

if ! [ -d /etc/bind ]
then
  mkdir /etc/bind
fi
log `/usr/bin/wget http://www.orsn.org/roothint/root-hint.txt -O /etc/bind/db.orsn 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... got dns root hints\\n


#-------------------------------------------------------------------------------
# change name resolver
#-------------------------------------------------------------------------------

log changing name resolver ...

echo nameserver 127.0.0.1 > /etc/resolv.conf

/bin/cat <<LIMIT1 > /etc/resolvconf.conf
resolv_conf_local_only=YES
name_servers=127.0.0.1
LIMIT1

if ! [ -d /etc/dhcp ]
then
  mkdir /etc/dhcp
fi
if ! [ -d /etc/dhcp/dhclient-enter-hooks.d ]
then
  mkdir /etc/dhcp/dhclient-enter-hooks.d
fi
/bin/cat <<LIMIT1 > /etc/dhcp/dhclient-enter-hooks.d/resolv
make_resolv_conf() 
{
  echo nameserver 127.0.0.1 > /etc/resolv.conf
}
LIMIT1

log ... name resolver changed\\n


#-------------------------------------------------------------------------------
# make base directory
#-------------------------------------------------------------------------------

log making base directory ...

/bin/mkdir $basedir

log ... made base directory\\n


#-------------------------------------------------------------------------------
# install software
#-------------------------------------------------------------------------------

log installing software ...

log `/bin/tar xjvf $myname.tar.bz2 -C $basedir 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... software installed\\n


#-------------------------------------------------------------------------------
# patch udev rule file (enable modeswitch for HUAWEI 3G modems)
# see https://github.com/RPi-Distro/repo/issues/47
# might well be distro spedific!
#-------------------------------------------------------------------------------

log patching udev rule file ...

log `/usr/bin/patch /lib/udev/rules.d/40-usb_modeswitch.rules $basedir/40-usb_modeswitch.rules.patch 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`

log ... udev rule file patched\\n


#-------------------------------------------------------------------------------
# patch and make sslsplit
#-------------------------------------------------------------------------------

log making sslsplit ...

log `/bin/tar xjvf $basedir/sslsplit-latest.tar.bz2 -C $basedir 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`

sslsplit=`ls -d1 $basedir/sslsplit* | grep -v sslsplit-latest.tar.bz2`
log `/usr/bin/patch $sslsplit/pxyconn.c $basedir/pxyconn.c.patch 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`
log `/usr/bin/make -C $sslsplit 2>&1 | /bin/sed 's/^/\\\\n\\\\t/g'`
ln -s $sslsplit/sslsplit $basedir/sslsplit

log ... sslsplit made\\n


#-------------------------------------------------------------------------------
# make configuration file
#-------------------------------------------------------------------------------

log making configuration file ...

/bin/cat <<LIMIT1 > $basedir/$config_file
#!/bin/bash

### Ansicht und Verhalten
# Boxen
__suchbox="$__suchbox"
__dekodbox="$__dekodbox"
__whoisbox="$__whoisbox"
__usetabs="$__usetabs"
# Skin
__skin="$__skin"
# Tabellen
__klick="$__klick"
__zeilen="$__zeilen"
# Debug-Infos
__debug="0"

### DNS
# eigener Hostname
__dns_server_name="$__dns_server_name"
# eigener Domainname
__dns_domain_name="$__dns_domain_name"

### WLAN
# eigene SSID
__wlan_ssid="$__wlan_ssid"
# Passwort des eigenen WLAN
__wlan_password="$__wlan_password"
# Kanal des eigenen WLAN
__wlan_channel="$__wlan_channel"

### Netzwerk
# erstes Oktet
__ip_ip1="$__ip_ip1"
# zweites Oktet
__ip_ip2="$__ip_ip2"
# drittes Oktet
__ip_ip3="$__ip_ip3"
# Netzmaske (konstant)
__ip_mask="255.255.255.0"
# eigene Adresse (konstant)
__ip_ip="1"
# Start des DHCP-Bereichs (konstant)
__ip_dhcp1="2"
# Ende des DHCP-Bereichs (konstant)
__ip_dhcp2="254"

### Internet
# Routing
__internet_aufzeichnung="$__internet_aufzeichnung"
# Ports, auf denen unverschlüsselter Verkehr abgehört werden soll
__tcp_ports="$__tcp_ports"
# Ports, auf denen verschlüsselter Verkehr abgehört werden soll
__ssl_ports="$__ssl_ports"
# umts provider (konstant)
__umts="Tchibo Mobil:webmobil1;O2:pinternet.interkom.de;Vodafone:web.vodafone.de"

LIMIT1

log ... configuration file made\\n


#-------------------------------------------------------------------------------
# make constants file
#-------------------------------------------------------------------------------

log making constants file ...

/bin/cat <<LIMIT1 > $basedir/$constants_file
#!/bin/bash

# develop mode?
# set to 0 for regular use
# set to 1 for a more open configuration (e.g. mysqld access over wifi connection, ssh access from everywhere)
develop_mode=0

# name
my_name="$my_name"
# version
my_version="$my_major.$my_minor"
# names of interfaces
wired_interface="${myname}eth"
wireless_interface="${myname}wlan"

# base dir
base_dir="$basedir"
# tmp dir for config files etc
temp_dir="$tempdir"
# data dir for database etc
data_dir="$basedir/data"
# name of configuration file
config_file="$config_file"
# name of constants file
constants_file="$constants_file"

# location of php5 cli bin
php5_bin="/usr/bin/php5"
# name of dbinserter script
dbinserter="dbinserter.php"
# name of dbinserter output file
dbinserter_output="dbinserter.output"
# name of dbinserter pid file
dbinserter_pid="dbinserter.pid"

# location of sslsplit binary (or link to)
sslsplit_bin="$basedir/sslsplit"
# name of dir to store connection logs
connection_dir="connections"
# name of dir to store certificates
certificate_dir="certificates"
# name of file for sslslpit connection log
connection_log="connection.log"
# name of file for sslslpit output
sslsplit_output="sslsplit.output"
# name of sslsplit pid file
sslsplit_pid="sslsplit.pid"
# proxy port for non-SSL traffic
tcpproxy_port=65534
# proxy port for SSL traffic
sslproxy_port=65535

# location of tcpdump binary (or link to)
tcpdump_bin="/usr/sbin/tcpdump"
# name of tcpdump pcap file
tcpdump_pcap="tcpdump.pcap"
# name of file for tcpdump output
tcpdump_output="tcpdump.output"
# name of tcpdump pid file
tcpdump_pid="tcpdump.pid"

# location of log file for start script
logfile="$tempdir/$my_name.log"

# location of CA key file for SSLsplit
key_file="$basedir/cert.key"
# location of CA cert file for SSLsplit
cert_file="$basedir/cert.crt"

# location of config and pid files for sshd server
sshd_configfile="$tempdir/sshd.conf"
sshd_pidfile="$tempdir/sshd.pid"

# location of config and db files for bind name server
bind_configfile="$tempdir/named.conf"
bind_forwardfile="$tempdir/named.forward"
bind_reversefile="$tempdir/named.reverse"

# location of config, lease and pid files for dhcpd
dhcpd_configfile="$tempdir/dhcpd.conf"
dhcpd_pidfile="$tempdir/dhcpd.pid"
dhcpd_leasefile="$tempdir/dhcpd.leases"

# location of lease and pid files for dhclient
dhclient_pidfile="$tempdir/dhclient.pid"
dhclient_leasefile="$tempdir/dhclient.leases"

# location of config file for hostapd
hostapd_configfile="$tempdir/hostapd.conf"

# location of config file for lighttpd
lighttpd_configfile="$tempdir/lighttpd.conf"
# server root for lighttpd
lighttpd_root="$basedir/www"
# port for lighttpd
lighttpd_port="80"
# skin dir (relative to lighttpd_root)
skin_dir="css/skin/"

# location of config file for mysqld
mysqld_configfile="$tempdir/mysqld.conf"
# location of pid file for mysqld
mysqld_pidfile="$tempdir/mysqld.pid"
# DB root
mysqld_datadir="$datadir/mysql"
# database name
database_name="$myname"
# port for mysqld
mysqld_port="3306"
# location of file containing sql commands to initialize database
database_initfile="$basedir/initialize.sql"
# location of file containing sql commands to initialize ciphers
ciphers_initfile="$basedir/ciphers.sql"
# command to incorporate ciphersuite information
ciphersuites_cmd="/usr/bin/php5 $basedir/ciphersuites.php $basedir ciphersuites.txt"

# location of script to open internet connection
online_script="$tempdir/online.sh"
# location of script to close internet connection
offline_script="$tempdir/offline.sh"
# location of flag file indicating having been online at least once after (re)start
online_flag="$tempdir/online_flag"

# location of config file for wvdial
wvdial_configfile="$tempdir/wvdial.conf"
# location of config file for wpa_supplicant
wpa_supplicant_configfile="$tempdir/wpa_supplicant.conf"

# location of run file for session
session_file="$tempdir/session.run"

# location of image file for meta data extraction
image_file="$tempdir/image"

# location of zip file for export and import
export_file="$datadir/archiv.zip"

# name of file containing meta infos in export archive
meta_file="meta.xml"

LIMIT1

log ... constants file made\\n


#-------------------------------------------------------------------------------
# activate start script
#-------------------------------------------------------------------------------

log activating start script ...

/bin/cat <<LIMIT1 > /etc/rc.local
#!/bin/sh -e
/bin/bash $basedir/start.sh $model &
exit 0
LIMIT1

log ... start script activated\\n


#-------------------------------------------------------------------------------
# reboot
#-------------------------------------------------------------------------------

log rebooting ...

/sbin/reboot




