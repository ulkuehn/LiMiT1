<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: startstop.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to start and stop a data recording
//
//==============================================================================
//==============================================================================


require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");
require ("include/database.php");


$_aufzeichnen = "Aufzeichnung starten";
$_beenden = "Aufzeichnung beenden";
$_vorzeitig = "Datenübernahme vorzeitig beenden";


function killPids ($dir, $all=false)
{
  global $dbinserter_pid;
  
  foreach (scandir("$dir") as $file)
  {
    if (substr($file,-4)==".pid" && ($all || $file != $dbinserter_pid))
    {
      $pf = fopen ("$dir/$file", "r");
      $pid = trim (fgets ($pf));
      fclose ($pf);
      posix_kill ($pid, SIGINT);
      system ("/bin/ps $pid >/dev/null", $rv);
      if ($rv==0)
      {
        posix_kill ($pid, SIGKILL);
      }
    }
  }
  // this is a bit of a hack we need because sslsplit spawns
  // lower privileged processes we don't know the pid of
  system ("/usr/bin/killall sslsplit");
}


function eigerg ($wert, $geraet, $name)
{
  global $db;

  if ($wert != "")
  {
    $select_s = $db->prepare ("select * from eigenschaft where geraet=? and name=?");
    $select_s->execute (array($geraet,"$name (automatisch ermittelt)"));
    if (($eigenschaft = $select_s->fetch()) == false)
    {
      $insert_s = $db->prepare ("insert into eigenschaft set geraet=?, name=?, wert=?");
      $insert_s->execute (array($geraet,"$name (automatisch ermittelt)",$wert));
    }
    else
    {
      $update_s = $db->prepare ("update eigenschaft set wert=? where geraet=? and name=?");
      $update_s->execute (array($wert,$geraet,"$name (automatisch ermittelt)"));
    }
  }
}


// keine laufende Aufzeichnung

if (!file_exists ($session_file))
{
  titelHilfe ("Datenverkehr aufzeichnen", <<<LIMIT1
<p>Mit dieser Funktion kann die Aufzeichnung des Datenverkehrs für das bzw. eines der am WLAN "$__wlan_ssid" angeschlossenen Geräte gestartet werden.
Während die Datenaufzeichnung läuft, wird der Datenverkehr zwischen dem entsprechenden Gerät und dem Internet mitgeschnitten und wird anschließend für die spätere Auswertung in einer Datenbank abgespeichert.</p>
<p>Sind mehrere Geräte mit dem WLAN "$__wlan_ssid" verbunden, kann das Gerät, dessen Daten aufgezciehnet werden sollen aus der Liste "Aufzuzeichnendes Gerät" ausgewählt werden. Die Auswahlliste "Verwaltetes Gerät" bezieht sich auf die in der Geräteverwaltung eingerichteten Geräte.</p>
LIMIT1
  );

  echo "<div class=\"row\">";

  // Aufzeichnung starten
  if (isset($_POST["aufzeichnen"]))
  {
    $exists = 0;
    if ($_POST["name"] != "")
    {
      $select_s = $db->prepare ("select count(*) from aufzeichnung where name=?");
      $select_s->execute (array($_POST["name"]));
      $exists = $select_s->fetchColumn();
    }
    if ($exists)
    {
      errorMsg ("Eine Aufzeichnung mit dem Namen \"".$_POST["name"]."\" existiert bereits.");
    }
    else
    {
      $err = false;

      foreach (preg_split("/\\r\\n|\\r|\\n/",$_POST["connected"]) as $ipmacname)
      {
        list ($ip,$mac,$name) = explode (" ",$ipmacname,3);
        if ($ip == $_POST["ip"])
        {
          if (!$_POST["geraet"])
          {
            // neues Gerät anlegen
            $insert_s = $db->prepare("insert into geraet set stand=now(), name=?");
            $insert_s->execute(array("Für Aufzeichnung ".($_POST["name"]==""? "":"\"".$_POST["name"]."\" ")."vom ".strftime("%d.%m.%Y, %H:%M")." automatisch hinzugefügt"));
            $_POST["geraet"] = $db->lastInsertId();
          }
          // ggf. Eigenschaften ergänzen
          eigerg ($mac,$_POST["geraet"],"MAC-Adresse");
          eigerg ($name,$_POST["geraet"],"Name");
        }
      }

      // neue Aufzeichnung in DB anlegen
      $insert_s = $db->prepare("insert into aufzeichnung set start=now(), name=?, info=?, geraet=?, ip=inet_aton(?)");
      $insert_s->execute(array($_POST["name"], $_POST["info"], $_POST["geraet"], $_POST["ip"]));
      $aufzeichnungID = $db->lastInsertId();
      
      // create session directory
      $workdir = "$data_dir/$aufzeichnungID";
      $tempdir = "$temp_dir/$aufzeichnungID";
      
      if (is_dir($workdir))
      {
        exec ("/bin/rm -R $workdir");
      }
      mkdir ($workdir);
      mkdir ("$workdir/$connection_dir");
      mkdir ("$workdir/$certificate_dir");
            
      if (is_dir($tempdir))
      {
        exec ("/bin/rm -R $tempdir");
      }
      mkdir ($tempdir);
      
      // start dbinserter for parallel insertion of data into database
      if (!$err)
      {
        $cmd = "$php5_bin ".$_SERVER["DOCUMENT_ROOT"]."/$dbinserter $aufzeichnungID ".$_POST["ip"];
        exec (sprintf ("%s >%s 2>&1 & echo $! > %s", $cmd, "$tempdir/$dbinserter_output", "$tempdir/$dbinserter_pid"));
        if (!processPid("$tempdir/$dbinserter_pid"))
        {
          errorMsg ("$dbinserter konnte nicht gestartet werden");
          $err = true;
        }
      }
      
      // start sslsplit
      if (!$err)
      {
        $cmd = "$sslsplit_bin -P -k $key_file -c $cert_file -W \"$workdir/$certificate_dir\" -F \"$workdir/$connection_dir/%T-%s-%d.log\" -l $workdir/$connection_log ssl $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip $sslproxy_port tcp $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip $tcpproxy_port";
        exec (sprintf ("%s >%s 2>&1 & echo $! > %s", $cmd, "$tempdir/$sslsplit_output", "$tempdir/$sslsplit_pid"));
        if (!processPid("$tempdir/$sslsplit_pid"))
        {
          errorMsg ("$sslsplit_bin konnte nicht gestartet werden");
          $err = true;
        }
      }
      
      // start tcpdump to catch udp
      if (!$err)
      {
        $cmd = "$tcpdump_bin -i $wireless_interface -U -w $workdir/$tcpdump_pcap udp and host ".$_POST["ip"]." and not host ".$_SERVER['SERVER_ADDR'];
        exec (sprintf ("%s >%s 2>&1 & echo $! > %s", $cmd, "$tempdir/$tcpdump_output", "$tempdir/$tcpdump_pid"));
        if (!processPid("$tempdir/$tcpdump_pid"))
        {
          errorMsg ("$tcpdump_bin konnte nicht gestartet werden");
          $err = true;
        }
      }

      // in develop mode start more tcpdumps
      if (!$err && $develop_mode)
      {
        // start local tcpdump    
        $cmd = "$tcpdump_bin -i $wireless_interface -w $workdir/l_$tcpdump_pcap -C 100 host ".$_POST["ip"]." and not host ".$_SERVER['SERVER_ADDR'];
        exec (sprintf ("%s >%s 2>&1 & echo $! > %s", $cmd, "$tempdir/l_$tcpdump_output", "$tempdir/l_$tcpdump_pid"));
      
        if (is_readable($offline_script))
        {
          $cfile = fopen ($offline_script, "r");
          $line = fgets ($cfile); 
          $line = fgets ($cfile); 
          fclose ($cfile);
          $interface = trim (substr ($line, 1));
          // start remote tcpdump
          exec ("/sbin/ifconfig $interface| grep 'inet ' | cut -d: -f2 |cut -d' ' -f1", $ip);
          $cmd = "$tcpdump_bin -i $interface -w $workdir/r_$tcpdump_pcap -C 100 host ".$ip[0];
          exec (sprintf ("%s >%s 2>&1 & echo $! > %s", $cmd, "$tempdir/r_$tcpdump_output", "$tempdir/r_$tcpdump_pid"));
        }
      }
      
      if ($err) // something went wrong
      {
        killPids ($tempdir, true);
        
        if (is_dir($workdir))
        {
          exec ("/bin/rm -rf $workdir");
        }
        
        $delete_s = $db->prepare("delete from aufzeichnung where id=?");
        $delete_s->execute (array($aufzeichnungID));
      }
      else // everything is okay
      {
        // if necessary, activate masquerading
        if ($__internet_aufzeichnung)
        {
          system ("/sbin/iptables --table nat --append POSTROUTING --source ".$_POST["ip"]." --out-interface $interface -j MASQUERADE");
        }
        
        // redirect to sslsplit
        foreach (explode(" ",$__ssl_ports) as $port)
        {
          system ("/sbin/iptables --table nat  --source ".$_POST["ip"]." --append PREROUTING  --protocol tcp  --dport $port  --jump REDIRECT  --to-ports $sslproxy_port");
        }
        if ($__tcp_ports == "")
        {
          $__tcp_ports = "1:65535";
        }
        foreach (explode(" ",$__tcp_ports) as $port)
        {
          system ("/sbin/iptables --table nat  --source ".$_POST["ip"]." --append PREROUTING  --protocol tcp  --dport $port  --jump REDIRECT  --to-ports $tcpproxy_port");
        }
        
        // create session file: 1st line session name, 2nd line ip address of monitored device, 3rd line running=1
        $sfile = fopen ($session_file, "w");
        fwrite ($sfile, $aufzeichnungID."\n");
        fwrite ($sfile, $_POST["ip"]."\n");
        fwrite ($sfile, "1\n");
        fclose ($sfile);
        
        // flash ACT LED
        $led = fopen ("/sys/class/leds/led0/trigger","w");
        fprintf ($led, "timer");
        fclose ($led);
        $led = fopen ("/sys/class/leds/led0/delay_on","w");
        fprintf ($led, "100");
        fclose ($led);
        $led = fopen ("/sys/class/leds/led0/delay_off","w");
        fprintf ($led, "900");
        fclose ($led);
        
        successMsg ("Die Aufzeichnung wurde gestartet.");
        
        echo "<script>document.getElementById('recordButton').innerHTML='$recordStop';</script>";
      }
    }  
  }

  // Aufzeichnungs-Formular anzeigen
  else
  {
    echo "<form class=\"form-horizontal\" method=\"post\">";

    if (!file_exists ($cert_file) || !file_exists($key_file))
    {
      errorMsg ("Es ist kein $my_name-Zertifikat vorhanden.");
    }
    
    else if (file_exists ($offline_script))
    {        
      if ($__internet_aufzeichnung)
      {
        infoMsg ("Das Gerät, dessen Daten aufgezeichnet werden, erhält dadurch Zugang zum Internet.");
      }

      echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Eigenschaften der Aufzeichnung</h4>
      </div>
      <div class="panel-body">
LIMIT1;

      echo <<<LIMIT1
        <div class="form-group">
          <label for="ip" class="col-md-3 control-label">Name der Aufzeichnung</label>
          <div class="col-md-9">
            <input type="text" class="form-control" name="name" id="name">
            <p class="help-block">Zur leichteren Identifizierung der Aufzeichnung bei der Auswertung. Optional. Kann später geändert bzw. ergänzt werden.</p>
          </div>
        </div>
LIMIT1;
      
      $connected = array();
      $conlist = "";
      exec ("/usr/sbin/arp -i $wireless_interface", $arp);
      foreach ($arp as $aline)
      {
        if ( preg_match ("/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}).*([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/", $aline, $m) )
        {
          exec ("/bin/ping -w 1 -c 1 -q ".$m[1], $p, $r);
          // ping ok
          if (!$r)
          {
            $hn = array();
            $name = "???";
            $cname = "";
            exec ("/bin/grep \"DHCP[A-Z]* on ".$m[1]."\" /var/log/user.log", $hn);
            foreach ($hn as $hline)
            {
              if (preg_match ("/\((.+)\)/", $hline, $hm))
              {
                $name = "\"".trim($hm[1])."\"";
                $cname = " ".trim($hm[1]);
                break;
              }
            }
            $connected[$m[1]] = "$name &mdash; ".($_SERVER["REMOTE_ADDR"]==$m[1]? "":"nicht ") . "dieses Gerät (IP-Adresse ".$m[1].", MAC-Adresse ".$m[2].", pingbar)";
            $conlist .= ($conlist==""? "":"\n").$m[1]." ".$m[2].$cname;
          }
          // no ping
          else
          {
            $connected[$m[1]] = ($_SERVER["REMOTE_ADDR"]==$m[1]? "":"nicht ") . "dieses Gerät (IP-Adresse ".$m[1].", MAC-Adresse ".$m[2].", nicht pingbar)";
            $conlist .= ($conlist==""? "":"\n").$m[1]." ".$m[2];
          }
        }
      }
      if (count($connected) > 1)
      {
        echo <<<LIMIT1
        <div class="form-group">
          <label for="ip" class="col-md-3 control-label">Aufzuzeichnendes Gerät</label>
          <div class="col-md-9">
            <select class="form-control" name="ip" id="ip">
LIMIT1;
        ksort ($connected);
        foreach ($connected as $ip=>$info)
        {
          echo "<option value=\"$ip\"",$_SERVER["REMOTE_ADDR"]==$ip?" selected":"",">$info</option>";
        }
        echo <<<LIMIT1
            </select>
            <p class="help-block">Es sind mehrere Geräte mit $my_name verbunden. Bitte das Gerät auswählen, dessen Daten aufgezeichnet werden sollen.</p>
          </div>
        </div>
LIMIT1;
      }
      else
      {
        echo "<input type=\"hidden\" name=\"ip\" value=\"",$_SERVER["REMOTE_ADDR"],"\">";
      }
      echo "<input type=\"hidden\" name=\"connected\" value=\"$conlist\">";
      
      $select_s = $db->prepare("select * from geraet order by name");
      $select_s->execute();
      if ($geraet = $select_s->fetch())
      {
        echo <<<LIMIT1
        <div class="form-group">
          <label for="ip" class="col-md-3 control-label">Verwaltetes Gerät</label>
          <div class="col-md-9">
            <select class="form-control" name="geraet" id="geraet">
LIMIT1;
        echo "<option value=\"0\"></option>";
        do
        {
          echo "<option value=\"",$geraet["id"],"\">",htmlspecialchars($geraet["name"]),"</option>";
        }
        while ($geraet = $select_s->fetch());        
        echo <<<LIMIT1
            </select>
            <p class="help-block">Bitte das passende Gerät zuordnen, das in der Geräteverwaltung erfasst ist. Optional. Kann später geändert bzw. ergänzt werden.</p>
          </div>
        </div>
LIMIT1;
      }

      echo <<<LIMIT1
        <div class="form-group">
          <label for="info" class="col-md-3 control-label">Erläuterungen</label>
          <div class="col-md-9">
            <textarea class="form-control" style="resize:vertical" name="info" rows="3"></textarea>
            <p class="help-block">Hilfreiche Hinweise. Diese können später geändert bzw. ergänzt werden.</p>
          </div>
        </div>
        <input type="submit" class="btn btn-success" name="aufzeichnen" value="$_aufzeichnen">
      </div>
    </div>
LIMIT1;
    }
    else
    {
      errorMsg ("$my_name ist offline.");
    }

    echo "</form>";
  }
  
  echo "</div>";
}


// laufende Aufzeichnung

else
{
  $sfile = fopen ($session_file, "r");
  $aufzeichnungID = trim (fgets ($sfile));
  $ip = trim (fgets ($sfile));
  $running = trim (fgets ($sfile));
  fclose ($sfile);

  if ($running)
  {
    // Aufzeichnung beenden
    if (array_key_exists("beenden",$_POST))
    {      
      // unredirect to sslsplit
      foreach (explode(" ",$__ssl_ports) as $port)
      {
        system ("/sbin/iptables --table nat  --source $ip  --delete PREROUTING  --protocol tcp  --dport $port  --jump REDIRECT  --to-ports $sslproxy_port");
      }
      if ($__tcp_ports == "")
      {
        $__tcp_ports = "1:65535";
      }
      foreach (explode(" ",$__tcp_ports) as $port)
      {
        system ("/sbin/iptables --table nat  --source $ip  --delete PREROUTING  --protocol tcp  --dport $port  --jump REDIRECT  --to-ports $tcpproxy_port");
      }

      // if necessary, deactivate masquerading
      if ($__internet_aufzeichnung)
      {
        $cfile = fopen ($offline_script, "r");
        $line = fgets ($cfile); 
        $line = fgets ($cfile); 
        fclose ($cfile);
        $interface = trim (substr ($line, 1));
        system ("/sbin/iptables --table nat --delete POSTROUTING --source $ip --out-interface $interface -j MASQUERADE");
      }

      killPids ("$temp_dir/$aufzeichnungID");

      // Aufzeichnung in DB updaten
      $update_s = $db->prepare("update aufzeichnung set ende=now() where id=?");
      $update_s->execute(array($aufzeichnungID));

      // permanently switch off ACT LED
      $led = fopen ("/sys/class/leds/led0/trigger","w");
      fprintf ($led, "none");
      fclose ($led);
      $led = fopen ("/sys/class/leds/led0/brightness","w");
      fprintf ($led, "0");
      fclose ($led);
      
      // recreate session file: 1st line session name, 2nd line ip address of monitored device, 3rd line running=0
      $sfile = fopen ($session_file, "w");
      fwrite ($sfile, $aufzeichnungID."\n");
      fwrite ($sfile, $ip."\n");
      fwrite ($sfile, "0\n");
      fclose ($sfile);
      $running = 0;
      
      echo "<script>document.getElementById('recordButton').innerHTML='$recordEnd';</script>";
    }    

    else if (isset($_POST["vorzeitig"]))
    {
      $sfile = fopen ($session_file, "r");
      $aufzeichnungID = trim (fgets ($sfile));
      fclose ($sfile);

      if (is_readable("$temp_dir/$aufzeichnungID/$dbinserter_pid"))
      {
        $pf = fopen ("$temp_dir/$aufzeichnungID/$dbinserter_pid", "r");
        $pid = trim (fgets ($pf));
        fclose ($pf);

        system ("/bin/ps $pid >/dev/null", $rv);
        if ($rv==0)
        {
          posix_kill ($pid, SIGINT);
          while (file_exists($session_file))
          {
            sleep (1);
          }
          system ("/bin/ps $pid >/dev/null", $rv);
          if ($rv==0)
          {
            posix_kill ($pid, SIGKILL);
          }
        }
      }
      
      echo "<div class=\"row\">";
      successMsg ("Die Datenbankübernahme der <a href=\"aufzeichnung.php?aufzeichnung=$aufzeichnungID\">Aufzeichnung</a> wurde vorzeitig beendet.");
      echo "</div>"; 
    }
    
    // Aufzeichnungsende-Formular anzeigen
    else
    {
      titelHilfe ("Aufzeichnung stoppen", <<<LIMIT1
Hiermit wird die laufende Aufzeichnung gestoppt und die restlichen Daten werden in die Datenbank übernommen.
LIMIT1
      );

      echo <<<LIMIT1
  <div class="row">
    <form class="form-horizontal" method="post">
LIMIT1;

      if ($__internet_aufzeichnung)
      {
        $sfile = fopen ($session_file, "r");
        $aufzeichnungID = trim (fgets ($sfile));
        $ip = trim (fgets ($sfile));
        fclose ($sfile);
        infoMsg ("Das Gerät mit der IP-Adresse $ip wird dadurch wieder vom Internet getrennt.");
      }

      echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading">
          <h4 class="panel-title">Aufzeichnung</h4>
        </div>
        <div class="panel-body">
          <input type="submit" class="btn btn-primary" name="beenden" value="$_beenden">
        </div>
      </div>
    </form>
  </div>
LIMIT1;
    }  
  }
  
  if (!$running)
  {
    titelHilfe ("Aufzeichnung in Datenbank übernehmen", <<<LIMIT1
LIMIT1
    );

    $sm = jsSave (successMsg ("Die <a href=\"aufzeichnung.php?aufzeichnung=$aufzeichnungID\">Aufzeichnung</a> wurde in die Datenbank übernommen.", false), false);
    $vz = jsSave ("<a href=\"#cancelModal\" class=\"btn btn-warning\" data-toggle=\"modal\">$_vorzeitig</a>", false); 

    echo <<<LIMIT1
  <script>
  function progress()
  {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() 
    {
      if (xmlhttp.readyState==4 && xmlhttp.status==200) 
      {
        if (xmlhttp.responseText != "")
        {
          document.getElementById("progress").innerHTML = xmlhttp.responseText + "$vz";
        }
        else
        {
          document.getElementById("progress").innerHTML = "$sm";
        }
      }
    }
    
    xmlhttp.open("GET","include/progress.php?id=$aufzeichnungID",true);
    xmlhttp.send();
  }
  progress();
  var myVar = setInterval (function () { progress() }, 1000);
  </script>

  <form method="post">
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <div class="alert alert-warning" role="alert">
              <div class="msgIcon">
                <span class="fa-stack fa-lg">
                  <i class="fa fa-database fa-stack-1x"></i>
                  <i class="fa fa-ban fa-stack-2x"></i>
                </span>
              </div>
              <div class="msgText">
                <strong>$_vorzeitig</strong>
              </div>
            </div>
          </div>
          <div class="modal-body">
            <p>Soll die Übername in die Datenbank tatächlich beendet werden?</p>
          </div>
          <div class="modal-footer">
            <input class="btn btn-warning" type="submit" value="$_vorzeitig" name="vorzeitig">
            <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="row" id="progress"></div>
 
LIMIT1;
  }
}
  
require ("include/htmlend.php");

?>
