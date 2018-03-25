# LiMiT1

Software to turn a Raspberry Pi 3, Zero W or 2 into a Wifi based Man-In-The-Middle device.

Includes everything needed to setup a Raspberry for such a task, control it via web interface, collect data from attached devices and analyze them subsequently.

Up to now, almost everything is in **German** language only (see [LIESMICH.md](./LIESMICH.md)).

I18N is on my agenda, albeit not topmost.

## Disclaimer

**Everything in this repository is meant for educational and informational purposes only. Do not use this software to monitor devices you do not own, control or otherwise have permission to inspect data flowing in and out.**

## Repository content

To create a LiMiT1 system only two files are needed: `limitify.sh` and `limit1.tar.bz2`. Everything else is added for reasons of documentation and better understanding.

file | use
--- | ---
`README.md` | this info
`LIESMICH.md` | instructions on how to build and operate a LiMiT1 system (in German)
`limit1.tar.bz2` | tarball needed to install a LiMiT1 system on top of a Raspbian Lite Distro
`limitify.sh` | Shell script to do the install
`src`  | contains all LiMiT1 source files, for documentation purposes (basically `limit1.tar.bz2` untarred)
`images` | contains some pics needed for `LIESMICH.md`
