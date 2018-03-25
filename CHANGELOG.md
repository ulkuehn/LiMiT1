# Changelog

## [Current] - unreleased

---

## [1.2.1] - Live Connection Preview - 25. March 2018

### Codename

Farmsen

### Added

- In recording mode give a live update of latest connections
- Support for Raspberry Zero W (plus headless setup)
- No need for memory stick (database can be stored on SD card)
- Tool to insert and eject memory stick
- Tutorial
- Introduced codenames for releases (using Hamburg borough names)

### Changed

- Major code review
- Extended settings (preset APN, LED behaviour)
- Extended status information
- Improved controls in search tool (fine grained search field selection)
- Error handling on boot (fail only on fatal errors; report non fatal errors)
- No automatic update for major release changes (new manual setup needed)

### Removed

Nothing.


## [1.1.3] - Improvements - 1. Oct 2017

### Codename

Eppendorf

### Added

Nothing.

### Changed

- improved check for having successfully gone online (forcing ntp update)
- no need to specify at least one SSL port in settings any more (if field is left blank, every port is considered non-SSL, so no decryption takes place)
- on shutdown visualize when system can savely be powered off
- some minor bugfixes
- numerous code improvements (comments etc)

### Removed

Nothing.

## [1.1.2] - Bugfixes - 24. Sept 2017

### Codename

Dulsberg

### Added

Nothing.

### Changed

- links to whois service fixed

### Removed

Nothing.

## [1.1.1] - Online Updates - 19 Sept 2017

### Codename

Curslack

### Added

- Functionality for online updates
- Enhanced options for `limitify.sh`

### Changed

Several bugfixes.

### Removed

Nothing.

## [1.0.4] - Support for HUAWEI UMTS modems - 27 Aug 2017

### Codename

Brambek

### Added

Patch file for udev rules (removing bug regarding HUAWEI UMTS modems)

### Changed

Nothing.

### Removed

Nothing.

---
 
## 1.0.1 - Initial release - 27 Aug 2017

### Codename

Altona

### Added

- Shell script and tar file needed to put up a LiMiT1 system
- Source files
- Description

### Changed

Nothing.

### Removed

Nothing.


[Current]: https://github.com/ulkuehn/LiMiT1/compare/v1.2.1...HEAD
[1.2.1]: https://github.com/ulkuehn/LiMiT1/compare/v1.1.3...v1.2.1
[1.1.3]: https://github.com/ulkuehn/LiMiT1/compare/v1.1.2...v1.1.3
[1.1.2]: https://github.com/ulkuehn/LiMiT1/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/ulkuehn/LiMiT1/compare/v1.0.4...v1.1.1
[1.0.4]: https://github.com/ulkuehn/LiMiT1/compare/v1.0.1...v1.0.4