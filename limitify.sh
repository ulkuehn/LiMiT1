#!/bin/bash

#===============================================================================
#===============================================================================
#
#      PROJECT: LiMiT1
#         FILE: limitify.sh
#          SEE: https://github.com/ulkuehn/LiMiT1
#       AUTHOR: Ulrich Kuehn
#
#        USAGE: limitify.sh [options]
#
#  DESCRIPTION: script to install or update a LiMit1 system
#               available options:
#                 -r <password> set root password (default is '$password')
#                 -l <country.coding> set system locale (default is $locale_country.$locale_coding')
#                 -L list available locales and exit
#                 -t <timezone> set system timezone (default is '$timezone')
#                 -T list available timezones and exit
#                 -s <ssid> set default ssid for LiMiT1 wifi (default is '$__wlan_ssid')
#                 -p <password> set default password for LiMiT1 wifi (default is '$__wlan_password')
#                 -c <channel> set default wifi channel (default is $__wlan_channel)
#
#===============================================================================
#===============================================================================

### base configuration

# full name
my_name="LiMiT1"
# major version; full version number is "major.minor"
my_major="1.1"
# minor version counter, may get updated automagically by git commit
my_minor="2"

# root password
password="limit1"

# name of configuration file
config_file="configuration"

# name of constants file
constants_file="constants"


### default system configuration

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

# own wifi
__wlan_ssid="limit1"
__wlan_password="limit1limit"
__wlan_channel="5"

# own wifi network
__ip_ip1="172"
__ip_ip2="16"
__ip_ip3="0"
__ip_mask="255.255.255.0"

# own ip address within network
__ip_ip="1"

# dhcp range
__ip_dhcp1="2"
__ip_dhcp2="254"

# internet routing
__internet_aufzeichnung="1"

# ports
__tcp_ports=""
__ssl_ports="443"

# umts
__umts="Tchibo Mobil:webmobil1;O2:pinternet.interkom.de;Vodafone:web.vodafone.de"


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

# dns root hints URL
hintsurl="http://www.orsn.org/roothint/root-hint.txt"


### no changes beyond this line!
### usage, help

usage="for help use: ${BASH_SOURCE[0]} -h"
help="${BASH_SOURCE[0]}
script to put up a LiMiT1 system on a raspbian install
must be invoked with root privileges (e.g. sudo)
options:
  -r <password> set root password (default is '$password')
  -l <country.coding> set system locale (default is '$locale_country.$locale_coding')
  -L list available locales and exit
  -t <timezone> set system timezone (default is '$timezone')
  -T list available timezones and exit
  -s <ssid> set default ssid for LiMiT1 wifi (default is '$__wlan_ssid')
  -p <password> set default password for LiMiT1 wifi (default is '$__wlan_password')
  -c <channel> set default wifi channel (default is $__wlan_channel)
"

# sanitize my_name for use as installation base
myname=$(echo -n $my_name | tr '[:upper:]' '[:lower:]' | tr '[:space:]' '_')
basedir="/$myname"
# relative to basedir
tempdir="tmp"
datadir="data"

logfile="${BASH_SOURCE[0]}".log



#===  FUNCTION  ================================================================
#         NAME: abort
#  DESCRIPTION: abort due to fatal errors
#   PARAMETERS: message text
#       RESULT: echo message, exit with error code
#===============================================================================

abort ()
{
  if [ $# -ne 0 ]
  then
    echo "*****************************************" | log
    echo "Aborting: $*" | log
    echo "*****************************************\\n" | log
  fi
  
  # exit non-normally
  exit 1
}


#===  FUNCTION  ================================================================
#         NAME: log
#  DESCRIPTION: print a log entry
#   PARAMETERS: number of spaces to indent
#       RESULT: message is logged with timestamp
#===============================================================================

log ()
{
  sep=": "
  if [ -n "$1" ] && expr $1 + 1 &> /dev/null
  then
    for (( c=0; c<$1; c++ ))
    do
     sep="$sep "
    done
  fi
  
  while IFS= read -r line; 
  do 
    echo -e `date +"%Y.%m.%d  %H:%M:%S"`"$sep$line"
    echo -e `date +"%Y.%m.%d %H:%M:%S"`"$sep$line" >> $logfile
  done
}


# utility versions having small and large indent

logo ()
{
  log 2
}

logoo ()
{
  log 4
}


#===============================================================================
#===============================================================================
#===  MAIN SCRIPT  =============================================================
#===============================================================================
#===============================================================================


#-------------------------------------------------------------------------------
# save old log
#-------------------------------------------------------------------------------

if [ -f $logfile ]
then
  /bin/mv $logfile $logfile.old
fi


#-------------------------------------------------------------------------------
# are we root/sudo?
#-------------------------------------------------------------------------------

echo "checking user privileges ..." | log

if [ $EUID -gt 0 ]
then
  abort "user is not root, please invoke using sudo"
else
  echo "okay, running as root" | logo
fi

echo "... user privileges checked\\n" | log


#-------------------------------------------------------------------------------
# check mode
#-------------------------------------------------------------------------------

echo "checking mode ..." | log

if [ -d $basedir ]
then
  echo "$my_name installation exists, doing update" | logo
  update=1
  # use a new dir as (temporary) installation base
  installdir="${basedir}.update"
  # unset configuration params (some might be reset by options)
  password=
  locale_country=
  locale_coding=
  timezone=
else
  echo "no $my_name system found, doing first install" | logo
  update=0
  installdir=$basedir
fi
 
echo "... mode checked\\n" | log


#-------------------------------------------------------------------------------
# check hardware
#-------------------------------------------------------------------------------

echo "checking hardware ..." | log

model=0
revision=`/bin/cat /proc/cpuinfo | /bin/grep -i "^Revision" | cut -d':' -f2 | tr -d ' '`
for rev in $pi2revisions
do
  if [ $rev == $revision ]
  then
    model=2
    echo "running on a Raspberry Pi2 (revision $revision)" | logo
  fi
done
for rev in $pi3revisions
do
  if [ $rev == $revision ]
  then
    model=3
    echo "running on a Raspberry Pi3 (revision $revision)" | logo
  fi
done

if [ $model -lt 2 ]
then
  echo "not running on a Raspberry Pi2 or Pi3 -- no guarantee this might work" | logo
fi

echo "... hardware checked\\n" | log


#-------------------------------------------------------------------------------
# check options
#-------------------------------------------------------------------------------

echo "checking options ..." | log

while getopts ":hr:Ll:Tt:s:p:c:" o
do
  case "$o" in
  
  # help
  h )
    echo -e "$help"
    exit 0
    ;;
  # set root password
  r )
    if [ -z "$OPTARG" ]
    then
      abort "root password must not be empty"
    fi
    password=$OPTARG
    echo "root password is '$password'" | logo
    ;;
  # set ssid
  s )
    if [ -z "$OPTARG" ]
    then
      abort "ssid must not be empty"
    fi
    __wlan_ssid=$OPTARG
    echo "$my_name ssid is '$__wlan_ssid'" | logo
    ;;
  # set wifi password
  p )
    if [ -z "$OPTARG" ]
    then
      abort "wifi password must not be empty"
    fi
    __wlan_password=$OPTARG
    echo "wifi password is '$__wlan_password'" | logo
    ;;
  # set wifi channel
  c )
    if [ -z "$OPTARG" ]
    then
      abort "wifi channel must not be empty"
    fi
    if ! expr $OPTARG + 1 &> /dev/null
    then
      abort "wifi channel must be a number"
    fi
    if [ "$OPTARG" -lt 1 ] || [ "$OPTARG" -gt 14 ]
    then
      abort "wifi channel must be number between 1 and 14"
    fi
    __wlan_channel=$OPTARG
    echo "wifi channel is '$__wlan_channel'" | logo
    ;;
  # list locales
  L )
    echo "available locales:"
    /usr/bin/locale -a
    exit
    ;;
  # set locale
  l )
    if [ -z "$OPTARG" ]
    then
      abort "locale must not be empty"
    fi
    locale_country="${OPTARG%.*}"
    locale_coding="${OPTARG#*.}"
    if [ $locale_country == $locale_coding ]
    then
      abort "locale argument must be given as <country.coding>"
    fi
    locale_coding=$(echo $locale_coding | /usr/bin/tr '[:lower:]' '[:upper:]')
    echo "locale is '$locale_country' '$locale_coding'" | logo
    ;;
  # list timezones
  T )
    echo "available timezones:"
    /usr/bin/timedatectl list-timezones --no-pager
    exit
    ;;
  # set timezone
  t )
    if [ -z "$OPTARG" ]
    then
      abort "timezone must not be empty"
    fi
    mapfile -t zones < <( /usr/bin/timedatectl list-timezones --no-pager )
    found=0
    for zone in "${zones[@]}"
    do
      if [ $zone == $OPTARG ]
      then
        found=1
      fi
    done
    if [ $found == "0" ]
    then
      abort "timezone '$OPTARG' not known (use -T to list known timezones)"
    fi
    timezone=$OPTARG
    echo "timezone is '$timezone'" | logo
    ;;

  \? )
    abort "invalid option \"-$OPTARG\" -- $usage"
    ;;
    
  : )
    abort "option -$OPTARG requires an argument"
    ;;
    
  esac
done

echo "... options checked\\n" | log


#-------------------------------------------------------------------------------
# set root password
#-------------------------------------------------------------------------------

echo "setting root password ..." | log

if [ -z "$password" ]
then
  echo "leaving root password unchanged" | logo
else
  echo "using '$password'" | logo
  result=$(echo -e "$password\n$password" | /usr/bin/passwd root 2>&1)
  if [ $? -ne 0 ]
  then
    echo $result | logo
    abort "root password not successfully changed"
  fi
fi

echo "... root password set\\n" | log


#-------------------------------------------------------------------------------
# system update
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "updating system ..." | log

  /usr/bin/apt-get -y update 2>&1 | logo
  pista=${PIPESTATUS[0]}
  if [ $pista -ne 0 ]
  then
    abort "'/usr/bin/apt-get -y update' terminated with exit code $pista"
  fi
  
  /usr/bin/apt-get -y upgrade 2>&1 | logo
  pista=${PIPESTATUS[0]}
  if [ $pista -ne 0 ]
  then
    abort "'/usr/bin/apt-get -y upgrade' terminated with exit code $pista"
  fi

  echo "... system updated\\n" | log
fi


#-------------------------------------------------------------------------------
# change hostname
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "changing hostname ..." | log

  /bin/cat <<LIMIT1 > /etc/hosts
127.0.0.1   localhost
127.0.0.1   $myname    
LIMIT1
  echo $myname > /etc/hostname
  /bin/hostname $myname
  
  if [ `/bin/hostname` != $myname ]
  then
    abort "hostname not successfully changed (hostname is '"`/bin/hostname`"')"
  else
    echo "hostname now is '"`/bin/hostname`"'" | logo
  fi
  
  echo "... hostname changed\\n" | log
fi


#-------------------------------------------------------------------------------
# change console keyboard and setup
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "changing console setup ..." | log

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

  /bin/setupcon --force --verbose 2>&1 | logo
  pista=${PIPESTATUS[0]}
  if [ $pista -ne 0 ]
  then
    abort "'/bin/setupcon --force --verbose' terminated with exit code $pista"
  fi

  echo "... console setup changed\\n" | log
fi


#-------------------------------------------------------------------------------
# change locale
#-------------------------------------------------------------------------------

if [ -n "$locale_country" ] && [ -n "$locale_coding" ]
then
  echo "changing locale ..." | log

  /bin/rm -rf /usr/lib/locale/locale-archive | logoo
  echo "existing locales removed" | logo
  
  /usr/bin/localedef -i en_GB -c -f UTF-8 -A /usr/share/locale/locale.alias en_GB.UTF-8 2>&1 | logoo
  echo "locale en_GB.UTF-8 created" | logo
  
  /usr/bin/localedef -i $locale_country -c -f $locale_coding -A /usr/share/locale/locale.alias $locale_country.$locale_coding 2>&1 | logoo
  echo "locale $locale_country.$locale_coding created" | logo
  
  echo LANG=$locale_country.$locale_coding > /etc/default/locale
  echo "locale $locale_country.$locale_coding set as default" | logo

  echo "... locale changed\\n" | log
fi


#-------------------------------------------------------------------------------
# change timezone
#-------------------------------------------------------------------------------

if [ -n "$timezone" ]
then
  echo "changing timezone ..." | log

  echo $timezone > /etc/timezone 
  /usr/sbin/dpkg-reconfigure -f noninteractive tzdata 2>&1 | logo
  echo "timezone now is "`/bin/date +%Z` | logo
  
  echo "... timezone changed\\n" | log
fi


#-------------------------------------------------------------------------------
# install raspbian packages
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "installing raspbian packages ..." | log

  for i in ${!set_selections[*]}
  do
    echo ${set_selections[$i]} | /usr/bin/debconf-set-selections | logo
  done

  /usr/bin/apt-get -y install $packages 2>&1 | logo
  pista=${PIPESTATUS[0]}
  if [ $pista -ne 0 ]
  then
    abort "'/usr/bin/apt-get -y install' terminated with exit code $pista"
  fi

  echo "... raspbian packages installed\\n" | log
fi


#-------------------------------------------------------------------------------
# disable services
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "disabling services ..." | log

  for service in $services
  do
    /bin/systemctl disable $service 2>&1 | logoo
    pista=${PIPESTATUS[0]}
    if [ $pista -ne 0 ]
    then
      abort "'/bin/systemctl disable $service' terminated with exit code $pista"
    else
      echo "service $service disabled" | logo
    fi
  done
  
  echo "... services disabled\\n" | log
fi


#-------------------------------------------------------------------------------
# configure networking
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "configuring networking ..." | log

  /bin/cat <<LIMIT1 > /etc/network/interfaces
auto lo
iface lo inet loopback
LIMIT1
  echo "network configuration is:" | logo
  /bin/cat /etc/network/interfaces | logoo
  echo "... networking configured\\n" | log
fi


#-------------------------------------------------------------------------------
# configure tmpfs
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "configuring tmpfs ..." | log

  /bin/cat <<LIMIT1 > /etc/default/tmpfs
RAMLOCK=no
RAMSHM=no
RAMTMP=yes
RUN_SIZE=50%
LIMIT1
  echo "tmpfs configuration is:" | logo
  /bin/cat /etc/default/tmpfs | logoo

  echo "... tmpfs configured\\n" | log
fi


#-------------------------------------------------------------------------------
# configure mounts
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "configuring mounts ..." | log

  /bin/cat <<LIMIT1 > /etc/fstab
proc            /proc         proc    defaults                                    0 0
/dev/mmcblk0p1  /boot         vfat    ro                                          0 2
/dev/mmcblk0p2  /             ext4    defaults,noatime                            0 1
tmpfs           /tmp          tmpfs   defaults,noatime,nosuid,mode=0777,size=50%  0 0
tmpfs           /var/log      tmpfs   defaults,noatime,nosuid,mode=0777,size=50%  0 0
tmpfs           $basedir/tmp  tmpfs   defaults,noatime,nosuid,mode=0777,size=50%  0 0
LIMIT1
  echo "mount configuration is:" | logo
  /bin/cat /etc/fstab | logoo

  echo "... mounts configured\\n" | log
fi


#-------------------------------------------------------------------------------
# get dns root hints
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "getting dns root hints ..." | log

  tmphints="/tmp/hints"
  rm -f $tmphints

  if ! [ -d /etc/bind ]
  then
    mkdir /etc/bind
  fi

  /usr/bin/wget $hintsurl -O $tmphints 2>&1 | logo
  pista=${PIPESTATUS[0]}
  if [ $pista -ne 0 ]
  then
    abort "'/usr/bin/wget $hintsurl -O $tmphints' terminated with exit code $pista"
  else
    if ! [ -s "$tmphints" ]
    then
      abort "file $tmphints is empty"
    else
      mv $tmphints /etc/bind/db.orsn
      echo "dns root hints are:" | logo
      /bin/cat /etc/bind/db.orsn | logoo
    fi
  fi

  echo "... got dns root hints\\n" | log
fi


#-------------------------------------------------------------------------------
# change name resolver
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "changing name resolver ..." | log

  echo nameserver 127.0.0.1 > /etc/resolv.conf

  /bin/cat <<LIMIT1 > /etc/resolvconf.conf
resolv_conf_local_only=YES
name_servers=127.0.0.1
LIMIT1
  echo "mount configuration is:" | logo
  /bin/cat /etc/fstab | logoo

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

  echo "... name resolver changed\\n" | log
fi


#-------------------------------------------------------------------------------
# make installation directory
#-------------------------------------------------------------------------------

echo "making installation directory ..." | log

if [ -d $installdir ]
then
  abort "directory '$installdir' exists"
fi
/bin/mkdir $installdir
if ! [ -d $installdir ]
then
  abort "couldn't create directory '$installdir'"
else
  echo "directory '$installdir' created" | logo
fi

echo "... made base directory\\n" | log


#-------------------------------------------------------------------------------
# install software
#-------------------------------------------------------------------------------

echo "installing software ..." | log

if ! [ -e $myname.tar.bz2 ]
then
  abort "software file '$myname.tar.bz2' not found"
fi

/bin/tar xjvf $myname.tar.bz2 -C $installdir 2>&1 | logo
pista=${PIPESTATUS[0]}
if [ $pista -ne 0 ]
then
  abort "'/bin/tar xjvf $myname.tar.bz2 -C $installdir' terminated with exit code $pista"
fi

echo "... software installed\\n" | log


#-------------------------------------------------------------------------------
# patch udev rule file (enable modeswitch for HUAWEI 3G modems)
# see https://github.com/RPi-Distro/repo/issues/47
# might well be distro spedific!
#-------------------------------------------------------------------------------

echo "patching udev rule file ..." | log

# reverse previous patch first (only on update install)
if [ $update -eq 1 ]
then
  /usr/bin/patch -R /lib/udev/rules.d/40-usb_modeswitch.rules $basedir/40-usb_modeswitch.rules.patch 2>&1 | logoo
  pista=${PIPESTATUS[0]}
  if [ $pista -ne 0 ]
  then
    abort "'/usr/bin/patch -R /lib/udev/rules.d/40-usb_modeswitch.rules $basedir/40-usb_modeswitch.rules.patch' terminated with exit code $pista"
  else
    echo "successfully reversed previous patch" | logo
  fi
fi

/usr/bin/patch /lib/udev/rules.d/40-usb_modeswitch.rules $installdir/40-usb_modeswitch.rules.patch 2>&1 | logoo
pista=${PIPESTATUS[0]}
if [ $pista -ne 0 ]
then
  abort "'/usr/bin/patch /lib/udev/rules.d/40-usb_modeswitch.rules $installdir/40-usb_modeswitch.rules.patch' terminated with exit code $pista"
else
  echo "successfully applied patch file:" | logo
  /bin/cat $installdir/40-usb_modeswitch.rules.patch | logoo
fi

echo "... udev rule file patched\\n" | log


#-------------------------------------------------------------------------------
# patch and make sslsplit
#-------------------------------------------------------------------------------

echo "installing sslsplit ..." | log

echo "unpacking software ..." | logo
/bin/tar xjvf $installdir/sslsplit-latest.tar.bz2 -C $installdir 2>&1 | logoo
pista=${PIPESTATUS[0]}
if [ $pista -ne 0 ]
then
  abort "'bin/tar xjvf $installdir/sslsplit-latest.tar.bz2 -C $installdir' terminated with exit code $pista"
fi
sslsplit=`ls -d1 $installdir/sslsplit* | grep -v sslsplit-latest.tar.bz2`
sslsplitbase=`basename $sslsplit`
echo "installation directory is '$sslsplit'" | logoo
echo "... software unpacked" | logo

echo "patching pxyconn.c ..." | logo
/usr/bin/patch $sslsplit/pxyconn.c $installdir/pxyconn.c.patch 2>&1 | logoo
pista=${PIPESTATUS[0]}
if [ $pista -ne 0 ]
then
  abort "'/usr/bin/patch $sslsplit/pxyconn.c $installdir/pxyconn.c.patch' terminated with exit code $pista"
fi
echo "... pxyconn.c patched" | logo

echo "making sslslpit ..." | logo
/usr/bin/make -C $sslsplit 2>&1 | logoo
pista=${PIPESTATUS[0]}
if [ $pista -ne 0 ]
then
  abort "'/usr/bin/make -C $sslsplit' terminated with exit code $pista"
fi
(cd $installdir; ln -s $sslsplitbase/sslsplit sslsplit)
echo "... sslslpit made" | logo

echo "... sslsplit installed\\n" | log


#-------------------------------------------------------------------------------
# make configuration file
#-------------------------------------------------------------------------------

echo "making configuration file ..." | log

# on update install read config values from exisiting installation
if [ $update -eq 1 ]
then
  if ! [ -e $basedir/$config_file ]
  then
    abort "couldn't find existing configuration file '$basedir/$config_file'"
  else
    . $basedir/$config_file
    echo "existing configuration used" | logo
  fi
fi

/bin/cat <<LIMIT1 > $installdir/$config_file
#!/bin/bash

### look and feel

# utility boxes
__suchbox="$__suchbox"
__dekodbox="$__dekodbox"
__whoisbox="$__whoisbox"
__usetabs="$__usetabs"

# skin
__skin="$__skin"

# tables
__klick="$__klick"
__zeilen="$__zeilen"

# debug
__debug="0"


### DNS

# own host name
__dns_server_name="$__dns_server_name"

# own domain name
__dns_domain_name="$__dns_domain_name"


### WLAN

# own wifi's SSID
__wlan_ssid="$__wlan_ssid"

# own wifi's password
__wlan_password="$__wlan_password"

# own wifi's channel
__wlan_channel="$__wlan_channel"


### network

# first .. third octets
__ip_ip1="$__ip_ip1"
__ip_ip2="$__ip_ip2"
__ip_ip3="$__ip_ip3"

# netmask
__ip_mask="$__ip_mask"

# own address
__ip_ip="$__ip_ip"

# DHCP range
__ip_dhcp1="$__ip_dhcp1"
__ip_dhcp2="$__ip_dhcp2"


### internet

# routing
__internet_aufzeichnung="$__internet_aufzeichnung"

# ports to listen for unencrypted traffic for
__tcp_ports="$__tcp_ports"

# ports to listen for encrypted traffic for
__ssl_ports="$__ssl_ports"

# list of umts providers
__umts="$__umts"

LIMIT1

echo "configuration file '$installdir/$config_file' is:" | logo
/bin/cat $installdir/$config_file | logoo

echo "... configuration file made\\n" | log


#-------------------------------------------------------------------------------
# make constants file
#-------------------------------------------------------------------------------

echo "making constants file ..." | log

/bin/cat <<LIMIT1 > $installdir/$constants_file
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
temp_dir="$basedir/$tempdir"
# data dir for database etc
data_dir="$basedir/$datadir"
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
logfile="$basedir/$tempdir/$my_name.log"

# location of CA key file for SSLsplit
key_file="$basedir/cert.key"
# location of CA cert file for SSLsplit
cert_file="$basedir/cert.crt"

# location of config and pid files for sshd server
sshd_configfile="$basedir/$tempdir/sshd.conf"
sshd_pidfile="$basedir/$tempdir/sshd.pid"

# location of config and db files for bind name server
bind_configfile="$basedir/$tempdir/named.conf"
bind_forwardfile="$basedir/$tempdir/named.forward"
bind_reversefile="$basedir/$tempdir/named.reverse"

# location of config, lease and pid files for dhcpd
dhcpd_configfile="$basedir/$tempdir/dhcpd.conf"
dhcpd_pidfile="$basedir/$tempdir/dhcpd.pid"
dhcpd_leasefile="$basedir/$tempdir/dhcpd.leases"

# location of lease and pid files for dhclient
dhclient_pidfile="$basedir/$tempdir/dhclient.pid"
dhclient_leasefile="$basedir/$tempdir/dhclient.leases"

# location of config file for hostapd
hostapd_configfile="$basedir/$tempdir/hostapd.conf"

# location of config file for lighttpd
lighttpd_configfile="$basedir/$tempdir/lighttpd.conf"
# server root for lighttpd
lighttpd_root="$basedir/www"
# port for lighttpd
lighttpd_port="80"
# skin dir (relative to lighttpd_root)
skin_dir="css/skin/"

# location of config file for mysqld
mysqld_configfile="$basedir/$tempdir/mysqld.conf"
# location of pid file for mysqld
mysqld_pidfile="$basedir/$tempdir/mysqld.pid"
# DB root
mysqld_datadir="$basedir/$datadir/mysql"
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
online_script="$basedir/$tempdir/online.sh"
# location of script to close internet connection
offline_script="$basedir/$tempdir/offline.sh"
# location of flag file indicating having been online at least once after (re)start
online_flag="$basedir/$tempdir/online_flag"

# location of config file for wvdial
wvdial_configfile="$basedir/$tempdir/wvdial.conf"
# location of config file for wpa_supplicant
wpa_supplicant_configfile="$basedir/$tempdir/wpa_supplicant.conf"

# location of run file for session
session_file="$basedir/$tempdir/session.run"

# location of image file for meta data extraction
image_file="$basedir/$tempdir/image"

# location of zip file for export and import
export_file="$basedir/$datadir/archiv.zip"

# name of file containing meta infos in export archive
meta_file="meta.xml"

LIMIT1

echo "constants file '$installdir/$constants_file' is:" | logo
/bin/cat $installdir/$constants_file | logoo

echo "... constants file made\\n" | log


#-------------------------------------------------------------------------------
# copy certificate
#-------------------------------------------------------------------------------

# do this only on update install
if [ $update -eq 1 ]
then
  echo "copying certificate ..." | log
  
  if [ -e $basedir/cert.key ] && [ -e $basedir/cert.crt ]
  then
    /bin/cp -a $basedir/cert.key $installdir/cert.key
    /bin/cp -a $basedir/cert.crt $installdir/cert.crt
    echo "found cert.key, cert.crt" | logo
  else
    echo "no certificate found" | logo
  fi
  echo "... certificate copied\\n" | log
fi


#-------------------------------------------------------------------------------
# activate start script
#-------------------------------------------------------------------------------

# do this only on first install
if [ $update -eq 0 ]
then
  echo "activating start script ..." | log

  /bin/cat <<LIMIT1 > /etc/rc.local
#!/bin/sh -e
/bin/bash $basedir/start.sh $model &
exit 0
LIMIT1
  echo "/etc/rc.local is now:" | logo
  /bin/cat /etc/rc.local | logoo

  echo "... start script activated\\n" | log
fi


#-------------------------------------------------------------------------------
# reboot
#-------------------------------------------------------------------------------

# reboot only on first install
if [ $update -eq 0 ]
then
  echo "rebooting ..." | log
  /sbin/reboot
# on update install switch directories and exit normally
else
  mv $basedir ${basedir}.old
  mv $installdir $basedir
  exit 0
fi




