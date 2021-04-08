# mcdcmReporter

## Introduction

mcdcmReporter is a front-end for [Orthanc](https://www.orthanc-server.com) which allows password-protected distribution of radiology studies and reports within an intranet environment.

As opposed to traditional HIS-RIS-PACS set-ups where Orders are sent to modalities, mcdcmReporter generates "orders" from the studies coming out from modalities. This allows radiology departments without PACS to solve the problem of storage & distribution of images & reports without needing to install a HIS-Order placer first.

mcdcmReporter was presented at [OrthancCon 2019](https://www.orthanc-server.com/static.php?page=conference-schedule).

[Online demo](http://3.0.109.201/isen) is available. Use credentials `user`:`password`. For additional functions (templates, report-writing), please contact me.

## Prerequisites
1. [Orthanc](https://orthanc-server.com)
1. [PostgreSQL](https://www.postgresql.org)
1. Web server + PHP (e.g. Apache+PHP)

Note: An APP stack, such as [Bitnami WAPP](https://bitnami.com/stack/wapp), is the easiest way to get started

## Basic Setup

### Orthanc
1. Copy `addPatient.lua` to the Orthanc folder
1. In `orthanc.json`:
    1. Set `"LuaScripts" : [ "../addPatient.lua" ],`
    1. Set `"StableAge" : 3,` for studies to appear in mcdcmReporter faster
    1. Set `"OverwriteInstances" : true,`
    1. Set `"HttpTimeout" : 3,`
    1. Set `"RemoteAccessAllowed" : true,` to allow access from other clients
    1. Uncomment `"AuthenticationEnabled" : false,` if `"RemoteAccessAllowed"` is set to `true`
1. Edit `addPatient.lua` to point to the correct URL for `addPatient.php` (default is `http://localhost/isen/addPatient.php`)
1. Restart the Orthanc service

### mcdcmReporter
1. Create a new folder (default name is `isen`) in htdocs (or equivalent)
1. Copy this repo to that new folder
1. In `config.php`:
    1. Configure `$orthanc` and `$orthancLocal` (if needed)
    1. Configure the PostgreSQL credentials
        * `$dbname` and `$table` can be left as default

### First Run
1. Best used in Chrome. Go to `http://localhost/isen`
1. Default credentials are `admin`:`password`
1. Check error messages and run `setup.php`

#### To Add Studies into mcdcmReporter Manually
1. Upload DICOM through Orthanc Explorer (e.g. `http://localhost:8042`)
1. Studies will appear in mcdcmReporter after `StableAge`

#### If not using `isen` as folder in htdocs
1. Change line in `addPatient.lua`

## Additional Setup for Production
Note: Some of these steps will break the Basic Setup. Please take the time to study Orthanc before implementing these steps.

### Orthanc
1. Set up Orthanc to use PostgreSQL in `postgresql.json`. Refer [Orthanc Book](https://book.orthanc-server.com/plugins/postgresql.html)
1. Set up Orthanc as a DICOM node in your network. Refer [Step 3 in Beginner's Guide](https://www.orthanc-server.com/resources/2015-02-09-emsy-tutorial/index.html)
    * `"DicomAet" : "<insert aetitle here>",`
    * `"DicomPort" : <insert DICOM port here>,`
1. Set `"RemoteAccessAllowed" : true,`. `addPatient.lua` has filters that blocks all remote methods except GET

### mcdcmReporter
1. Edit PRINT OPTIONS in `config.php` (If printing of reports is required.)
    
## Advanced Steps for Production
### mcdcmReporter
1. Change the default file name of `$userListFile`
1. Change the default `$dbname` and `$table`
1. Set up `allowedIPs.php` for whitelisting (*STRONGLY RECOMMENDED*)

### Multiple Orthanc Services
1. Set up a 2nd Orthanc service on another HTTP port which uses the same PostgreSQL database as the 1st Orthanc service
    1. `$orthanc` points to the 2nd Orthanc service (for viewing purposes)
    1. `$orthancLocal` points to the 1st Orthanc service (pure DICOM listener)
