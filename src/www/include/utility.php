<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/utility.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: collection of settings and utility functions needed
//              by several scripts
//
//==============================================================================
//==============================================================================
// unbegrenzte Ausführungszeit einstellen, damit langdauernde Skripte nicht abgebrochen werden
set_time_limit ( 0 );

// Zeichenzahl, ab der Spalteneinträge in der kompakten Tabellenansicht abgeschnitten werden
$foldLength = 20;

// Auslassungsanzeige bei kompakter Tabellenansicht
$tableEllipsis = "&nbsp;<strong><span style=\"white-space: nowrap;\">&middot;&middot;&middot;</span></strong>&nbsp;"; //<i class=\"fa fa-ellipsis-h\"></i>";
// Ersatz-HTML für nicht druckbare Zeichen
$nonPrint = " &middot; ";

// Aufzeichnung(sende)-Buttons
$recordStart = "<a href=\"startstop.php\"><span class=\"text-success\"><i class=\"fa fa-dot-circle-o fa-lg\"></i></span></a>";
$recordStop = "<a href=\"startstop.php\"><span class=\"text-danger\"><i class=\"fa fa-stop fa-lg flash\"></i></span></a>";
$recordEnd = "<a href=\"startstop.php\"><span class=\"text-warning\"><i class=\"fa fa-database fa-lg flash\"></i></span></a>";

// Badges für Gut/Schlecht-Kennzeichnungen etc.
$badSign = "<span class=\"label label-danger\"><i class=\"fa fa-close fa-fx\"></i></span>";
$mehSign = "<span class=\"label label-warning\"><i class=\"fa fa-exclamation fa-fx\"></i></span>";
$goodSign = "<span class=\"label label-success\"><i class=\"fa fa-check fa-fx\"></i></span>";
$questSign = "<span class=\"label label-info\"><i class=\"fa fa-question fa-fx\"></i></span>";

// Vor- und Zurück-Button
$zurueckInaktiv = "<span class=\"btn btn-primary btn-xs\" disabled=\"disabled\"><i class=\"fa fa-arrow-left\"></i></span>";
$vorInaktiv = "<span class=\"btn btn-primary btn-xs\" disabled=\"disabled\"><i class=\"fa fa-arrow-right\"></i></span>";
$zurueckButton = "<span class=\"btn btn-primary btn-xs\"><i class=\"fa fa-arrow-left\"></i></span>";
$vorButton = "<span class=\"btn btn-primary btn-xs\"><i class=\"fa fa-arrow-right\"></i></span>";

// empfangen / versandt
$empfangenIcon = "<div style=\"white-space:nowrap; display:inline;\"><i class=\"fa fa-globe\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa fa-home\"></i></div>";
$versandtIcon = "<div style=\"white-space:nowrap; display:inline;\"><i class=\"fa fa-home\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa fa-globe\"></i></div>";


/* * *****************************************************************************

  Elemente für die Freitextsuche

 * ***************************************************************************** */

// Felder: Singular, Plural, SQL-Query, SQL-Query zum Zählen
$suchOrte = array ( "HTTP_Requests"
    =>
    array ( "HTTP-Request",
        "HTTP-Requests",
        "select *, id as request, concat (methode, ' ', uri, ' ', version) as inhalt from request having inhalt regexp ?",
        "select count(*) from request where binary concat (methode, ' ', uri, ' ', version) regexp ?"
    ),
    "HTTP_Responses"
    =>
    array ( "HTTP-Response",
        "HTTP-Responses",
        "select *, concat (version, ' ', status, ' ', statustext) as inhalt from response having inhalt regexp ?",
        "select count(*) from response where binary concat (methode, ' ', uri, ' ', version) regexp ?"
    ),
    "HTTP_Header"
    =>
    array ( "HTTP-Header",
        "HTTP-Header",
        "select *, concat(feld, ' ', wert) as inhalt from header having inhalt regexp ?",
        "select count(*) from header where binary concat(feld, ' ', wert) regexp ?"
    ),
    "Inhalte"
    =>
    array ( "Inhalt",
        "Inhalte",
        "select * from inhalt where (convert(inhalt using utf8) regexp convert(? using utf8) or convert(inhalt using latin1) regexp convert(? using latin1))",
        "select count(*) from inhalt where (binary convert(inhalt using utf8) regexp convert(? using utf8) or binary convert(inhalt using latin1) regexp convert(? using latin1))"
    )
);

function suchMarkierung ( $ort, $heuhaufen, $nadel, $isReg, $isCase, $pos )
{
  global $tableEllipsis;
  $compactCut = 30; // Anzahl der Zeichen vor und nach der Fundstelle in der kompakten Ansicht
  $fullCut = 200; // Anzahl der Zeichen vor und nach der Fundstelle in der ausführlichen Ansicht

  if ( $isReg )
  {
    $nadel = str_replace ( "(", "(?:", $nadel );
    $nadel = str_replace ( "/", "\\/", $nadel );
  }
  else
  {
    $nadel = preg_quote ( $nadel, "/" );
  }

  $prefix = substr ( $heuhaufen, 0, $pos );
  $heuhaufen = substr ( $heuhaufen, $pos );

  if ( !mb_check_encoding ( $heuhaufen, "UTF-8" ) )
  {
    $heuhaufen = utf8_encode ( $heuhaufen );
  }

  if ( !preg_match ( "/$nadel/Uu" . ($isCase ? "" : "i"), $heuhaufen, $match, PREG_OFFSET_CAPTURE ) )
  {
    return array ( "", false, 0 );
  }

  $prepart = $prefix . substr ( $heuhaufen, 0, $match[ 0 ][ 1 ] );
  $postpart = substr ( $heuhaufen, $match[ 0 ][ 1 ] + strlen ( $match[ 0 ][ 0 ] ) );
  $compactRes = "";
  $fullRes = "";

  if ( strlen ( $prepart ) > $compactCut )
  {
    $compactRes .= $tableEllipsis . binLesbar ( htmlSave ( substr ( $prepart, -$compactCut ) ) );
  }
  else
  {
    $compactRes .= binLesbar ( htmlSave ( $prepart ) );
  }
  if ( strlen ( $prepart ) > $fullCut )
  {
    $fullRes .= $tableEllipsis . binLesbar ( htmlSave ( substr ( $prepart, -$fullCut ) ) );
  }
  else
  {
    $fullRes .= binLesbar ( htmlSave ( $prepart ) );
  }

  $compactRes .= "<span class=\"highlight\">" . binLesbar ( htmlSave ( $match[ 0 ][ 0 ] ) ) . "</span>";
  $fullRes .= "<span class=\"highlight\">" . binLesbar ( htmlSave ( $match[ 0 ][ 0 ] ) ) . "</span>";

  if ( strlen ( $postpart ) > $compactCut )
  {
    $compactRes .= binLesbar ( htmlSave ( substr ( $postpart, 0, $compactCut ) ) ) . $tableEllipsis;
  }
  else
  {
    $compactRes .= binLesbar ( htmlSave ( $postpart ) );
  }
  if ( strlen ( $postpart ) > $fullCut )
  {
    $fullRes .= binLesbar ( htmlSave ( substr ( $postpart, 0, $fullCut ) ) ) . $tableEllipsis;
  }
  else
  {
    $fullRes .= binLesbar ( htmlSave ( $postpart ) );
  }

  return array ( "<span class=\"compactFundstellen$ort\">$compactRes</span><span class=\"fullFundstellen$ort\">$fullRes</span>",
      preg_match ( "/$nadel/Uu" . ($isCase ? "" : "i"), $postpart ),
      $pos + $match[ 0 ][ 1 ] + strlen ( $match[ 0 ][ 0 ] ) );
}

// in String mit beliebigen Zeichen nichtdruckbare Zeichen ersetzen

function binLesbar ( $string, $unicode = false )
{
  return preg_replace_callback ( "/[^[:print:]|^[:space:]]/" . ($unicode ? "u" : ""), function ($m)
  {
    global $nonPrint;
    return $nonPrint;
  }, $string );
}

// Inhalt darstellen
// Anzahl Zeichen, auf die ein zu langer Inhalt gekürzt wird
$inhaltTrunc = 1000;
// Faktor, um den ein Inhalt länger als $inhaltTrunc sein muss, um tatsächlich gekürzt zu werden
$truncScale = 150 / 100;

function highLighter ( $matches, $isurl = false )
{
  global $hlCounter, $hlName, $nonPrint;

  if ( array_key_exists ( 1, $matches ) && $matches[ 1 ] != "" )
  {
    return $nonPrint;
  }

  switch ( $matches[ 0 ] )
  {
    case "<": return "&lt;";
    case ">": return "&gt;";
    case "&": return "&amp;";
  }

  $hlCounter++;
  if ( $isurl )
  {
    return "<a href=\"" . $matches[ 0 ] . "\" target=\"_blank\"><span class=\"$hlName\" style=\"border-bottom:1px solid black;\">" . $matches[ 0 ] . "</span></a>";
  }
  else
  {
    return "<span class=\"$hlName\">" . $matches[ 0 ] . "</span>";
  }
}

function highLighterURL ( $matches )
{
  return highLighter ( $matches, true );
}

function highBtn ( $name, $typ, $title, $text )
{
  echo "<span id=\"b$typ$name\" class=\"btn btn-xs btn-info\" style=\"margin-right:5px\" onclick=\"$name ('$typ$name')\" title=\"$title\">$text</span>";
}

function highCont ( $id, $inhalt, $class = "" )
{
  echo <<<LIMIT1
<div id="$id">
  <pre$class>$inhalt</pre>
</div>
LIMIT1;
}

function zeigeInhalt ( $nr, $inhalt, $eigenschaften, $mime = "" )
{
  global $hlCounter, $hlName;

  $classStyle = "";

  $hlName = "HL$nr";

  $hlPattern = "|[&<>]|([^[:print:]|^[:space:]])";

  $ppMimes = array ( "html" => "html", "xml" => "xml", "css" => "css", "protobuf" => "protobuf", "javascript" => "javascript", "json" => "javascript" );

  $pp = "";
  foreach ( $ppMimes as $mimepart => $style )
  {
    if ( stristr ( $mime, $mimepart ) )
    {
      $pp = $style;
    }
  }

  if ( !mb_check_encoding ( $inhalt, "UTF-8" ) )
  {
    $inhalt = utf8_encode ( $inhalt );
  }

  $inhaltNormal = preg_replace_callback ( "/[^[:print:]|^[:space:]]/u", function ($m)
  {
    global $nonPrint;
    return $nonPrint;
  }, htmlspecialchars ( $inhalt ) );

  $hlCounter = 0;
  $wortPattern = "(?:(?<=\PL)|(?<=\PN)|(?<=^))(?:\pL|[-_]){2,}(?!\pN)(?!\pL)(?![-_])";
  $inhaltWort = preg_replace_callback ( "/$wortPattern$hlPattern/u", "highLighter", $inhalt );
  $woerter = $hlCounter;

  $hlCounter = 0;
  $zahlPattern = "(?<=\PL)(?<=\P{Nd})\p{Nd}{2,}(?=\PL)(?=\P{Nd})";
  $inhaltZahl = preg_replace_callback ( "/$zahlPattern$hlPattern/u", "highLighter", $inhalt );
  $zahlen = $hlCounter;

  $urlPattern = "(?i)(?:[a-z](?:[a-z0-9+-.])*:\/\/(?:[^\s'\"\\\\<>])+)(?-i)";
  $hlCounter = 0;
  $inhaltUrl = preg_replace_callback ( "/$urlPattern$hlPattern/u", "highLighterURL", $inhalt );
  $urls = $hlCounter;

  $emailPattern = "(?i)[A-Z0-9._%+-]{1,64}@(?:[A-Z0-9-]{1,63}\.){1,125}[A-Z]{2,63}(?-i)";
  $hlCounter = 0;
  $inhaltEmail = preg_replace_callback ( "/$emailPattern$hlPattern/u", "highLighter", $inhalt );
  $emails = $hlCounter;

  $eigs = 0;
  $eigPattern = "";
  foreach ( $eigenschaften as $k => $wert )
  {
    $eigPattern .= ($eigPattern == "" ? "" : "|") . preg_quote ( $wert, "/" );
  }
  if ( $eigPattern != "" )
  {
    $hlCounter = 0;
    $inhaltEig = preg_replace_callback ( "/(?i)(?:$eigPattern)(?-i)$hlPattern/u", "highLighter", $inhalt );
    $eigs = $hlCounter;
  }

  if ( $woerter || $zahlen || $urls || $emails || $eigs || $pp != "" )
  {
    echo "<p>";

    highBtn ( $hlName, "normal", "Nichts hervorheben", "<i class=\"fa fa-ban\"></i>" );

    if ( $pp != "" )
    {
      highBtn ( $hlName, "pp", "Syntax hervorheben", "<i class=\"fa fa-paint-brush\"></i>" );
    }
    if ( $woerter )
    {
      highBtn ( $hlName, "wort", "$woerter Wörter hervorheben", "a .. Z" );
    }
    if ( $zahlen )
    {
      highBtn ( $hlName, "zahl", "$zahlen Zahlen hervorheben", "0 .. 9" );
    }
    if ( $urls )
    {
      highBtn ( $hlName, "url", "$urls Webadressen hervorheben", ": //" );
    }
    if ( $emails )
    {
      highBtn ( $hlName, "email", "$emails Mailadressen hervorheben", "@" );
    }
    if ( $eigs )
    {
      highBtn ( $hlName, "eig", "$eigs Geräteeigenschaften hervorheben", "X = abc" );
    }

    echo "</p>";
  }

  highCont ( "normal$hlName", $inhaltNormal );
  if ( $pp != "" )
  {
    highCont ( "pp$hlName", $inhaltNormal, " class=\"syntax $pp\" " );
  }
  if ( $woerter )
  {
    highCont ( "wort$hlName", $inhaltWort );
  }
  if ( $zahlen )
  {
    highCont ( "zahl$hlName", $inhaltZahl );
  }
  if ( $urls )
  {
    highCont ( "url$hlName", $inhaltUrl );
  }
  if ( $emails )
  {
    highCont ( "email$hlName", $inhaltEmail );
  }
  if ( $eigs )
  {
    highCont ( "eig$hlName", $inhaltEig );
  }

  echo <<<LIMIT1
<script src="js/jquery-syntax/jquery.syntax.min.js" type="text/javascript"></script>
<script type="text/javascript">
jQuery ( function($) { $.syntax ( { blockLayout: "plain", tabWidth: 8 } ); } );

function remove$hlName()
{
  while (sheet$hlName.cssRules.length)
  {
    sheet$hlName.deleteRule(0);
  }
}

function $hlName(variant)
{
  remove$hlName();
  sheet$hlName.insertRule (".$hlName { font-weight: bold; color: #ff0000; background-color: #ffff00; }", 0);
  for (i=0; i<variants$hlName.length; i++)
  {
    sheet$hlName.insertRule ("#" + variants{$hlName}[i]  + " { display: " + (variants{$hlName}[i]==variant? "inline":"none") + "; }", 0);
  }
  for (i=0; i<variants$hlName.length; i++)
  {
    var btn = document.getElementById("b"+variants{$hlName}[i]);
    if (btn != null)
    {
      if (variants{$hlName}[i]==variant)
      {
        btn.classList.add ("active");
      }
      else
      {
        btn.classList.remove ("active");
      }
    }
  }
}

var style = document.createElement('style');
style.type = 'text/css';
document.head.appendChild(style);
var sheet$hlName = style.sheet;
var variants$hlName = ['normal$hlName', 'pp$hlName', 'wort$hlName', 'zahl$hlName', 'url$hlName', 'email$hlName', 'eig$hlName'];
$hlName ('normal$hlName');

</script>

LIMIT1;
}

function zeigeTrunc ( $trunc, $len, $nr, $id, $inhalt )
{
  echo "<div id=\"zta$id\">";
  alertMsg ( "<button type=\"button\" class=\"btn btn-link\" onclick=\"full$id();\" style=\"text-align:left\">Es werden nur die ersten $trunc Zeichen angezeigt. Bitte hier klicken, um die gesamten " . humanBytesize ( $len ) . " anzuzeigen." . ($len < 1024 * 500 ? "" : " <br><b>Es handelt sich um eine große Datenmenge. Das Nachladen kann erhebliche Zeit dauern!</b>") . "</button>" );
  echo "</div>";
  echo "<div id=\"ztb$id\" style=\"display: none\">";
  progressMsg ( "Die restlichen " . humanBytesize ( $len - $trunc ) . " werden nachgeladen ..." );
  echo "</div>";

  echo <<<LIMIT1
  <script>
    function full$id()
    {
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function() 
      {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) 
        {
          document.getElementById("$id").innerHTML = xmlhttp.responseText;
          jQuery ( function($) { $.syntax ( { blockLayout: "plain", tabWidth: 8 } ); } );
        }
      }
      
      document.getElementById("zta$id").style.display="none";
      document.getElementById("ztb$id").style.display="block";
      xmlhttp.open("GET","include/zeigeinhalt.php?nr=$nr&id=$inhalt",true);
      xmlhttp.send();
    }
  </script>  
LIMIT1;
}

// Javascript-Code für faltbare Tabellenzeilen erzeugen

function tableFolder ( $tableName, $echo = true )
{
  $script = <<<TF1

<script type="text/javascript">

function remove$tableName()
{
  while (sheet$tableName.cssRules.length)
  {
    sheet$tableName.deleteRule(0);
  }
}

function compact$tableName()
{
  remove$tableName();
  sheet$tableName.insertRule(".compact$tableName { display: inline }",0);
  sheet$tableName.insertRule(".full$tableName { display: none }",0);
  $tableName = 0;
}

function full$tableName()
{
  remove$tableName();
  sheet$tableName.insertRule(".compact$tableName { display: none }",0);
  sheet$tableName.insertRule(".full$tableName { display: inline }",0);
  $tableName = 1;
}

function toggle$tableName()
{
  if ($tableName==1)
  {
    compact$tableName();
  }
  else
  {
    full$tableName();
  }
}

var $tableName = document.createElement('style');
$tableName.type = 'text/css';
document.head.appendChild($tableName);
var sheet$tableName = $tableName.sheet;
var $tableName = 0;
compact$tableName();

</script>

TF1;

  $html = <<<TF2
<span class="compact$tableName"><a class="btn btn-xs btn-success" onclick="full$tableName();" title="ausführliche Ansicht"><i class="fa fa-chevron-down"></i></a></span>
<span class="full$tableName"><a class="btn btn-xs btn-warning" onclick="compact$tableName();" title="kompakte Ansicht"><i class="fa fa-chevron-up"></i></a></span>

TF2;

  if ( $echo )
  {
    echo $script;
    return $html;
  }
  else
  {
    return array ( $script, $html );
  }
}

// Javascript-Code für eine sortierbare Tabelle erzeugen

function tableSorter ( $tableName, $extra )
{
  global $__zeilen;

  $ret = <<<LIMIT1
<script type="text/javascript">

$(document).ready( function() 
  { 
    $("#$tableName").DataTable
    (
      { 
        $extra,
        "dom":  "<'row'<'pull-left'l><'pull-right'f>><'row'<'col-md-12'tr>><'row'<'pull-left'i><'pull-right'p>>",
LIMIT1;

  if ( $__zeilen == "" )
  {
    $ret .= "\"paging\": false,\n";
  }
  else
  {
    $zeilen = explode ( " ", $__zeilen );
    $ret .= "\"pagingType\": \"full_numbers\",\n";
    $ret .= "\"pageLength\": " . $zeilen[ 0 ] . ",\n";
    sort ( $zeilen, SORT_NUMERIC );
    $ret .= "\"lengthMenu\": [ [" . implode ( ",", array_merge ( $zeilen, array ( "-1" ) ) ) . "], [" . implode ( ",", array_merge ( $zeilen, array ( "\"Alle\"" ) ) ) . "] ],\n";
  }

  $ret .= <<<LIMIT1
          "language":
        {
          "emptyTable": "Die Tabelle enthält keine Zeilen",
          "info": "zeige Zeile _START_ - _END_ von _TOTAL_ Zeilen",
          "infoEmpty": "Keine Zeilen",
          "infoFiltered": " &ndash; ausgewählt aus insgesamt _MAX_ Zeilen",
          "lengthMenu": "zeige _MENU_ Zeilen",
          "search": "Suchfilter <i class=\"fa fa-filter\"></i> ",
          "zeroRecords": "Keine passende Zeile gefunden",
          "paginate":
            {
              "first": "<i class=\"fa fa-arrow-left\"></i><i class=\"fa fa-arrow-left\"></i>",
              "previous": "<i class=\"fa fa-arrow-left\"></i>",
              "next": "<i class=\"fa fa-arrow-right\"></i>",
              "last": "<i class=\"fa fa-arrow-right\"></i><i class=\"fa fa-arrow-right\"></i>"
            }
        },        
        
        columnDefs:
        [
          { targets: "_all",
            "render": function ( data, type, full, meta ) 
            {
              if (type=="sort")
              {
                var m = data.match(/<!--.*-->/);
                if (m)
                {
                  return m[0].substr(4,m[0].length-7).trim();
                }
                else
                {
                  return data;
                }
              }
              else
              {
                return data;
              }
            }
          }
        ]        
      } 
    );
  } 
);
</script>

LIMIT1;

  return $ret;
}

// Bytemengen in kB, MB, GB wandeln

function humanBytesize ( $bytes )
{
  if ( $bytes < 1024 )
  {
    return "$bytes Bytes";
  }
  if ( $bytes < 1024 * 1024 )
  {
    return sprintf ( "%.1f", $bytes / 1024 ) . " kB";
  }
  if ( $bytes < 1024 * 1024 * 1024 )
  {
    return sprintf ( "%.1f", $bytes / 1024 / 1024 ) . " MB";
  }
  return sprintf ( "%.1f", $bytes / 1024 / 1024 / 1024 ) . " GB";
}

/**
 * display a title and optionally some help in a modal window
 * 
 * @param string $title the title to display
 * @param string $helpText
 * 
 * @return NULL nothing
 */
function titleAndHelp ( $title, $helpText = "" )
{
  $helpModalID = "helpModal";

  echo <<<LIMIT1
  <div class="row">
    <h2>
      $title
LIMIT1;

  if ( $helpText != "" )
  {
    echo <<<LIMIT1
      <a href="#$helpModalID" data-toggle="modal"><small><span class="text-success"><i class="fa fa-question-circle"></i></span></small></a>
LIMIT1;
  }

  echo <<<LIMIT1
    </h2>
  </div>
LIMIT1;

  if ( $helpText != "" )
  {
    echo <<<LIMIT1
<div class="modal fade" id="$helpModalID" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="alert alert-success" role="alert">
          <div class="msgIcon"><i class="fa fa-question-circle fa-2x"></i></div>
          <div class="msgText"><strong>$title</strong></div>
        </div>
      </div>
      <div class="modal-body">
        $helpText
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">
LIMIT1;
    echo (_ ( "Schließen" ));
    echo <<<LIMIT1
</button>
      </div>
    </div>
  </div>
</div>
LIMIT1;
  }
}

// Filterbox bei mehreren Aufzeichnungen

function aufzeichnungsFilter ( $alleText = "", $proText = "", $eineText = "" )
{
  global $db;

  $select_s = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc" );
  $select_s->execute ();
  $aufzeichnungen = $select_s->fetchAll ();
  if ( count ( $aufzeichnungen ) > 1 )
  {
    if ( isset ( $_COOKIE[ "filter" ] ) )
    {
      $_REQUEST[ "show" ] = $_COOKIE[ "filter" ];
    }
    else if ( !isset ( $_REQUEST[ "show" ] ) )
    {
      $_REQUEST[ "show" ] = "jede";
    }

    echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Darstellungsfilter</h4>
      </div>
      <div class="panel-body">
        <form method="post" id="filterForm">
          <select class="form-control" id="shows" name="show" onChange="document.cookie = 'filter='+document.getElementById('shows').value+'; expires=0'; document.getElementById('filterForm').submit();">
LIMIT1;
    echo "<option value=\"alle\"", ($_REQUEST[ "show" ] == "alle" ? " selected" : ""), ">", ($alleText == "" ? "Alle Aufzeichnungen in einer Tabelle" : $alleText), "</option>";
    echo "<option value=\"jede\"", ($_REQUEST[ "show" ] == "jede" ? " selected" : ""), ">", ($proText == "" ? "Jede Aufzeichnung in einer eigenen Tabelle" : $proText), "</option>";
    foreach ( $aufzeichnungen as $aufzeichnung )
    {
      echo "<option value=\"", $aufzeichnung[ "id" ], "\"", ($_REQUEST[ "show" ] == $aufzeichnung[ "id" ] ? " selected" : ""), ">", ($eineText == "" ? "Nur Aufzeichnung" . ($aufzeichnung[ "name" ] == "" ? "" : " &nbsp; &nbsp;" . htmlSave ( strlen ( $aufzeichnung[ "name" ] ) > 50 ? substr ( $aufzeichnung[ "name" ], 0, 47 ) . "..." : $aufzeichnung[ "name" ]  ) . "&nbsp; &nbsp;") . " vom " . $aufzeichnung[ "_start" ] : $eineText), "</option>";
    }
    echo <<<LIMIT1
          </select>
        </form>
      </div>
    </div>
  </div>
LIMIT1;
  }
  else
  {
    $_REQUEST[ "show" ] = $aufzeichnungen[ 0 ][ "id" ];
  }
}

// RDN-Infos von Zertifikaten aufbereiten

function rdnInfo ( $eigenschaft, $bad, $rdn )
{
  global $tableEllipsis, $badSign;
  $wert = "";
  $erl = array ();
  $org = "";
  $name = "";
  $keys = array ();

  foreach ( explode ( ", ", $rdn ) as $part )
  {
    if ( preg_match ( "/^(.*)=(.*)$/U", $part, $m ) )
    {
      $keys[ $m[ 1 ] ] = 1;
      switch ( $m[ 1 ] )
      {
        case "O":
          $org .= $m[ 2 ] . " ";
          break;
        case "CN":
          $name .= $m[ 2 ] . " ";
          break;
      }
    }
  }
  if ( $org != "" || $name != "" )
  {
    $wert = "<span class=\"compactZertifikat\">" . ($org != "" ? $org : $name) . "</span><span class=\"fullZertifikat\">" . $rdn . "</span>";
  }
  else
  {
    $wert = $rdn;
  }

  ksort ( $keys );
  foreach ( array_keys ( $keys ) as $key )
  {
    switch ( strtoupper ( $key ) )
    {
      case "C":
        array_push ( $erl, "C: Land (Country)" );
        break;
      case "ST":
        array_push ( $erl, "ST: Staat/Provinz (State)" );
        break;
      case "L":
        array_push ( $erl, "L: Ort (Locality)" );
        break;
      case "O":
        array_push ( $erl, "O: Organisation/Firma etc." );
        break;
      case "OU":
        array_push ( $erl, "OU: Abteilung (Organizational Unit)" );
        break;
      case "CN":
        array_push ( $erl, "CN: Name (Common Name)" );
        break;
      default:
        array_push ( $erl, strtoupper ( $key ) . ": ???" );
        break;
    }
  }
  echo "<tr", onevent ( "Zertifikat" ), "><td>$eigenschaft</td><td>$wert</td><td><span class=\"compactZertifikat\">", $bad == "" ? "" : $badSign, "$tableEllipsis</span><span class=\"fullZertifikat\">", $bad == "" ? "" : "$badSign $bad<br>", implode ( ", ", $erl ), "</span></td></tr>";
}

// Strings für die HTML-Ansicht aufbereiten

function htmlSave ( $text )
{
  return preg_replace ( "/\r?\n\r?/", "<br>", htmlspecialchars ( $text ) );
}

// Strings für Javascript-Anweisungen aufbereiten

function jsSave ( $text, $htmlescape = true )
{
  return preg_replace ( "/\r?\n\r?/", "\\n", str_replace ( "'", "\\x27", str_replace ( "\"", "\\x22", $htmlescape ? htmlspecialchars ( $text ) : $text ) ) );
}

// Online-Skript erzeugen

function onlineScript ( $commands, $interface = "" )
{
  global $online_script, $__internet_aufzeichnung;

  $script = fopen ( $online_script, "w" );
  fwrite ( $script, $commands );
  if ( $interface != "" && !$__internet_aufzeichnung )
  {
    fwrite ( $script, "/sbin/iptables --table nat --append POSTROUTING --out-interface $interface -j MASQUERADE\n" );
  }
  fclose ( $script );
}

// Offline-Skript erzeugen
function offlineScript ( $commands, $interface = "" )
{
  global $offline_script;

  $script = fopen ( $offline_script, "w" );
  fwrite ( $script, $commands );

  if ( $interface != "" )
  {
    // iptables --delete ... in jedem Fall, da sich $__internet_aufzeichnung ändern kann
    // falls keine entsprechende Regel vorhanden, ist es unschädlich
    fwrite ( $script, "/sbin/iptables --table nat --delete POSTROUTING --out-interface $interface -j MASQUERADE\n" );
  }
  fclose ( $script );
}

// Internetverbindung trennen wie im Offline-Skript vorgegeben

function offline ()
{
  global $offline_script;

  if ( file_exists ( $offline_script ) )
  {
    exec ( "/bin/bash $offline_script" );
    unlink ( $offline_script );
  }
}

// Internetverbindung herstellen wie im Online-Skript vorgegeben

function online ( $methode )
{
  if ( $methode == "" )
  {
    $methode = "Verbindung";
  }
  else
  {
    $methode .= "-Verbindung";
  }

  echo "<div class=\"row\" id=\"onMsg1\">";
  progressMsg ( "Die $methode wird hergestellt ..." );
  echo "</div>";
  echo "<div class=\"row\" id=\"onMsg2\" style=\"display:none\">";
  successMsg ( "Die $methode wurde erfolgreich hergestellt." );
  echo "</div>";
  echo "<div class=\"row\" id=\"onMsg3\" style=\"display:none\">";
  errorMsg ( "Die $methode konnte nicht hergestellt werden." );
  echo "</div>";

  echo <<<LIMIT1
<script>
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() 
  {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) 
    {
      document.getElementById("onMsg1").style.display='none';
      if (xmlhttp.responseText == "1")
      {
        document.getElementById("onMsg2").style.display='block';
        document.getElementById("topmenuOffline").classList.remove("disabled");
      }
      else
      {
        document.getElementById("onMsg3").style.display='block';
      }          
    }
  }
  xmlhttp.open("GET","online.php",true);
  xmlhttp.send();

</script>

LIMIT1;
}

/**
 * wait for the system to reboot
 * 
 * inserts JS code to do the following:
 * - waits for the system to go down
 * - as soon as the system reappears redirects client to landing page $openURL
 * - while waiting a div is updated with more and more $fa icons to visualize progress
 * 
 * @param string $updateID id of the div to update
 * @param string $fa name of the icon to use for visual update
 * @param string $openURL URL to open after reboot (relative to webroot)
 */
function waitForReboot ( $updateID, $fa, $openURL = "" )
{
  echo <<<LIMIT1
  <script>
    var wasDown = 0;
    function ping()
    {
      $.ajax ({
                url: window.location.protocol + "//" + window.location.hostname + "/index.php",
                timeout: 1000,
                success: function (result)
                         {
                           if (wasDown == 1)
                           {
                             window.location.assign(window.location.protocol+"//"+window.location.hostname+"$openURL");
                           }
                           else
                           {
                             ping(); 
                           }
                         },     
                error: function (result)
                       {
                         wasDown = 1;
                         ping(); 
                       }
              });
    }
    ping();
    setInterval (function () { document.getElementById("$updateID").innerHTML += "<i class=\"fa $fa\"></i> "; }, 2000);
    </script>
LIMIT1;
}

// HTTP-Statuscode-Gruppen kennzeichnen
function statusBadge ( $status )
{
  switch ( substr ( $status, 0, 1 ) )
  {
    case "1":
      return "<div class=\"label label-info\"><i class=\"fa fa-info fa-lg\"></i></div>";
    case "2":
      return "<div class=\"label label-success\"><i class=\"fa fa-check fa-lg\"></i></div>";
    case "3":
      return "<div class=\"label label-warning\"><i class=\"fa fa-share fa-lg\"></i></div>";
    case "4":
    case "5":
      return "<div class=\"label label-danger\"><i class=\"fa fa-close fa-lg\"></i></div>";
    default:
      return "<div class=\"label label-primary\"><i class=\"fa fa-question fa-lg\"></i></div>";
  }
}

// Meldung anzeigen
function infoMsg ( $text, $echo = true )
{
  $erg = <<<LIMIT1
  <div class="alert alert-info alert-sm" role="alert">
    <div class="msgIcon"><i class="fa fa-info fa-2x"></i></div>
    <div class="msgText"><strong>$text</strong></div>
  </div>
LIMIT1;
  if ( $echo )
  {
    echo $erg;
  }
  else
  {
    return $erg;
  }
}

// Ausführungsmeldung anzeigen
function progressMsg ( $text, $echo = true )
{
  $erg = <<<LIMIT1
  <div class="alert alert-info alert-sm" role="alert">
    <div class="msgIcon"><i class="fa fa-hourglass-start fa-2x flash"></i></div>
    <div class="msgText"><strong>$text</strong></div>
  </div>
LIMIT1;
  if ( $echo )
  {
    echo $erg;
  }
  else
  {
    return $erg;
  }
}

// Erfolgsmeldung anzeigen
function successMsg ( $text, $echo = true )
{
  $erg = <<<LIMIT1
  <div class="alert alert-success" role="alert">
    <div class="msgIcon"><i class="fa fa-check fa-2x"></i></div>
    <div class="msgText"><strong>$text</strong></div>
  </div>
LIMIT1;
  if ( $echo )
  {
    echo $erg;
  }
  else
  {
    return $erg;
  }
}

// Hinweisemeldung anzeigen
function alertMsg ( $text, $echo = true )
{
  $erg = <<<LIMIT1
  <div class="alert alert-warning" role="alert">
    <div class="msgIcon"><i class="fa fa-exclamation-triangle fa-2x"></i></div>
    <div class="msgText"><strong>$text</strong></div>
  </div>
LIMIT1;
  if ( $echo )
  {
    echo $erg;
  }
  else
  {
    return $erg;
  }
}

// Fehlermeldung anzeigen
function errorMsg ( $text, $echo = true )
{
  $erg = <<<LIMIT1
  <div class="alert alert-danger" role="alert">
    <div class="msgIcon"><i class="fa fa-bomb fa-2x"></i></div>
    <div class="msgText"><strong>$text</strong></div>
  </div>
LIMIT1;
  if ( $echo )
  {
    echo $erg;
  }
  else
  {
    return $erg;
  }
}

// Sekunden in verstrichene Zeit wandeln
function zeitDauer ( $runsek )
{
  if ( $runsek < 60 )
  {
    $run = "$runsek Sek";
  }
  else
  {
    $runmin = floor ( $runsek / 60 );
    if ( $runmin < 60 )
    {
      $run = "$runmin Min";
    }
    else
    {
      $runhour = floor ( $runmin / 60 );
      $runmin -= $runhour * 60;
      $run = "$runhour:" . ($runmin <= 9 ? "0" : "") . "$runmin Std";
    }
  }

  return $run;
}

// Ansehen-Button (z.B. in Tabellen)
function viewButton ( $url, $title = "Ansehen", $target = "" )
{
  return iconButton ( "eye", $url, $title, "info", $target );
}

// allgemeiner Button
function iconButton ( $icon, $url, $title = "", $class = "", $target = "" )
{
  global $my_name;
  return "<a class=\"btn btn-" . ($class = "" ? "default" : $class) . " btn-xs\" href=\"$url\"" . ($title == "" ? "" : " title=\"$title\"") . " target=\"$my_name$target\"><i class=\"fa fa-$icon\"></i></a>";
}

// Zelleninhalt für gefaltete Zelle ausgeben

function onevent ( $name )
{
  global $__klick;

  return " $__klick=\"toggle$name();return false;\" ";
}

function faltSortZelle ( $inhalt, $name, $left, $sort )
{
  global $foldLength, $tableEllipsis;

  if ( strlen ( $inhalt ) <= $foldLength )
  {
    return "<td>" . htmlSave ( $inhalt ) . "<!--$sort--></td>";
  }
  else
  {
    if ( $left )
    {
      return "<td class=\"break\"" . onevent ( $name ) . "><span class=\"compact$name\">$tableEllipsis " . htmlSave ( mb_substr ( $inhalt, -$foldLength ) ) . "</span><span class=\"full$name\">" . htmlSave ( $inhalt ) . "</span><!--$sort--></td>";
    }
    else
    {
      return "<td class=\"break\"" . onevent ( $name ) . "><span class=\"compact$name\">" . htmlSave ( mb_substr ( $inhalt, 0, $foldLength ) ) . "$tableEllipsis</span><span class=\"full$name\">" . htmlSave ( $inhalt ) . "</span><!--$sort--></td>";
    }
  }
}

function faltZelle ( $inhalt, $name, $left = false )
{
  return faltSortZelle ( $inhalt, $name, $left, $inhalt );
}

// Sekunden in Tage, Stunden umrechnen

function timeSpan ( $t )
{
  $tage = 0;
  if ( $t < 0 )
  {
    $t = 0;
  }
  $tage = floor ( $t / 24 / 3600 );
  $t = $t - 24 * 3600 * floor ( $t / 24 / 3600 );
  return $tage . " Tag" . ($tage == 1 ? "" : "e") . ", " . gmdate ( "H:i:s", $t );
}

// Domainnamen mit Whois-Links versehen

function whoisify ( $name, $check = true )
{
  global $db, $my_name;

  if ( $check )
  {
    $select_s = $db->prepare ( "select distinct domain from whois" );
    $select_s->execute ();
    $domains = $select_s->fetchAll ( PDO::FETCH_COLUMN, 0 );
  }

  if ( ip2long ( $name ) != false )
  {
    $parts = array ( $name );
  }
  else
  {
    $parts = explode ( ".", $name );
  }

  $i = 0;
  $ret = "";
  while ( count ( $parts ) )
  {
    if ( $parts[ 0 ] != "" )
    {
      $ret .= "<div class=\"whohover\" onmouseover=\"whoover(this," . ($check ? (in_array ( implode ( ".", $parts ), $domains ) ? 2 : 1) : 0) . ")\" onmouseout=\"whoout(this," . ($check ? (in_array ( implode ( ".", $parts ), $domains ) ? 2 : 1) : 0) . ")\">";
      $ret .= "<a class=\"whohover\" href=\"whois.php?domain=" . implode ( ".", $parts ) . "\" target=\"{$my_name}whois\">" . $parts[ 0 ];
    }
    array_shift ( $parts );
    if ( count ( $parts ) )
    {
      $ret .= ".";
    }
    $ret .= "</a></div>";
    $i++;
  }

  return "<div class=\"whohover\">$ret</div>";
}

// Infos über einen Host zusammenstellen

function ipHostinfo ( $ip, $table = null )
{
  global $db, $foldLength, $tableEllipsis;

  if ( !$ip || $ip == "" )
  {
    if ( $table && 0 )
    {
      return "<td></td>";
    }
    else
    {
      return false;
    }
  }

  $ermittelt = array ();
  $original = array ();

  $host_s = $db->prepare ( "select * from host where ip=?" );
  $host_s->execute ( array ( $ip ) );
  while ( $host = $host_s->fetch () )
  {
    if ( $host[ "nameermittelt" ] )
    {
      array_push ( $ermittelt, $host[ "name" ] );
    }
    else
    {
      array_push ( $original, $host[ "name" ] );
    }
  }

  usort ( $original, 'hostnamesort' );
  usort ( $ermittelt, 'hostnamesort' );

  $auth = array_pop ( $original );
  if ( !$auth )
  {
    $auth = array_pop ( $ermittelt );
  }
  if ( !$auth )
  {
    if ( $table )
    {
      return "<td class=\"break\"" . onevent ( $table ) . ">" . whoisify ( long2ip ( $ip ) ) . "</td>";
    }
    else
    {
      return array ( long2ip ( $ip ) );
    }
  }
  else
  {
    if ( $table )
    {
      $hostex = explode ( ".", $auth );
      $tld = array_pop ( $hostex );
      $domain = array_pop ( $hostex );
      array_push ( $hostex, $domain . "." . $tld );
      return "<td class=\"break\"" . onevent ( $table ) . ">" . whoisify ( $auth ) . "<span class=\"full$table\"><br>(" . implode ( " * ", array_merge ( array_map ( "whoisify", $original ), array_map ( "whoisify", $ermittelt ), array ( whoisify ( long2ip ( $ip ) ) ) ) ) . ")</span>" . "<!--" . implode ( " ", array_reverse ( $hostex ) ) . " --></td>";
    }
    else
    {
      return array_merge ( array ( $auth ), $original, $ermittelt, array ( long2ip ( $ip ) ) );
    }
  }
}

function nameHostinfo ( $name, $table = null )
{
  global $db;

  $host_s = $db->prepare ( "select ip from host where name=?" );
  $host_s->execute ( array ( $name ) );
  return ipHostinfo ( $host_s->fetchColumn (), $table );
}

function idHostinfo ( $id, $table = null )
{
  global $db;

  $host_s = $db->prepare ( "select ip from host where id=?" );
  $host_s->execute ( array ( $id ) );
  return ipHostinfo ( $host_s->fetchColumn (), $table );
}

// Sortierfunktion für Hostnamen

function hostnamesort ( $h1, $h2 )
{
  $h1 = explode ( ".", $h1 );
  $h2 = explode ( ".", $h2 );
  $tld1 = array_pop ( $h1 );
  $d1 = array_pop ( $h1 );
  $tld2 = array_pop ( $h2 );
  $d2 = array_pop ( $h2 );
  if ( $d1 != $d2 )
  {
    return strcmp ( $d1, $d2 );
  }
  else if ( $tld1 != $tld2 )
  {
    return strcmp ( $tld1, $tld2 );
  }
  else
  {
    return strcmp ( $h1[ 0 ], $h2[ 0 ] );
  }
}

// Begrenzung auf bestimmte Aufzeichnung anzeigen

function aufBegrenzt ( $aufzID )
{
  global $db;

  $select_s = $db->prepare ( "select count(*) from aufzeichnung" );
  $select_s->execute ( array () );
  $aufzeichnungen = $select_s->fetchColumn ();

  if ( $aufzeichnungen > 1 )
  {
    if ( $aufzID == 0 )
    {
      echo "<div class=\"row\">";
      infoMsg ( "Unter Berücksichtigung aller Aufzeichnungen" );
      echo "</div>";
    }
    else
    {
      $select_s = $db->prepare ( "select name,date_format(start,'%e.%c.%Y %H:%i') as _start from aufzeichnung where id=?" );
      $select_s->execute ( array ( $aufzID ) );
      $aufzeichnung = $select_s->fetch ();
      $name = $aufzeichnung[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $aufzeichnung[ "name" ] ) . "</strong>";
      echo "<div class=\"row\">";
      infoMsg ( "Begrenzt auf die Aufzeichnung $name vom {$aufzeichnung[ "_start" ]}" );
      echo "</div>";
    }
  }
}

// prüfen, ob Prozess mit der Nummer in pidFile noch läuft

function processPid ( $pidFile )
{
  if ( !is_readable ( $pidFile ) )
  {
    return false;
  }
  $pf = fopen ( $pidFile, "r" );
  $pid = trim ( fgets ( $pf ) );
  fclose ( $pf );
  system ( "/bin/ps $pid >/dev/null", $rv );
  return $rv == 1 ? false : true;
}

?>
