<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: certgen.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: generate a new certificate on a LiMiT1 system needed for
//              interception of ssl traffic
//
//==============================================================================
//==============================================================================

require_once ("include/constants.php");
require_once ("include/configuration.php");

exec ("/usr/bin/openssl req -x509 -nodes -newkey rsa:2048 -keyout $key_file -out $cert_file -days {$_REQUEST["tage"]} -subj \"{$_REQUEST["subj"]}\"");

echo "<p>/usr/bin/openssl req -x509 -nodes -newkey rsa:2048 -keyout $key_file -out $cert_file -days {$_REQUEST["tage"]} -subj \"{$_REQUEST["subj"]}\"</p>";

?>
