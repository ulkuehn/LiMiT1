<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file tutorial.php
 * 
 * educate the user
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * display a collapsable section 
 * 
 * @param string $id html id of the section
 * @param string $title title of the section
 */
function tutorialSection ( $id,
                           $title,
                           $open = false )
{
  global $__;

  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-parent=\"#", $__[ "tutorial" ] [ "ids" ] [ "panelGroup" ], "\" data-target=\"#$id\"><h4 class=\"panel-title\">$title</h4></div><div id=\"$id\" class=\"panel-collapse collapse", $open ? " in" : "", "\" role=\"tabpanel\"><div class=\"panel-body\">";
}


/**
 * html-ize some markdown formatted text
 * 
 * @param string $md md text
 */
function markDown ( $md )
{
  require_once ("include/Parsedown.php");
  $parsedown = new Parsedown();

  /*
   * insert bootstrap table class for prettyness
   */
  echo preg_replace ( "/<table>/",
                      "<table class=\"table table-condensed table-hover\">",
                      $parsedown->text ( $md ) );
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

titleAndHelp ( _ ( "Tutorial" ) );

echo "<div class=\"row\"><div class=\"panel-group\" id=\"", $__[ "tutorial" ] [ "ids" ] [ "panelGroup" ], "\" role=\"tablist\">";

/*
 * basics
 */
tutorialSection ( "basics",
                  _ ( "Grundlagen" ),
                      true );
markDown ( _ ( <<<LIMIT1
Bei $my_name handelt es sich um einen WLAN-Router, der -- wie von anderen Routern gewohnt -- beliebigen Geräten den Zugang zum Internet vermittelt. $my_name stellt dafür das WLAN mit der SSID $__wlan_ssid zur Verfügung.
  
Zusätzlich zu "normalen" Routern speichert $my_name die von einem angeschlossenen Gerät ins Internet übertragenen bzw. von dort erhaltenen Daten. Anschließend stellt $my_name verschiedene Analyse- und Auswertungsmöglichkeiten für diese Daten zur Verfügung. Mit $my_name ist es dadurch möglich, WLAN-fähigen Geräten (Smartphone, Tablet, Laptop, Smart-TV, IoT-Device, …) "unter die Haube zu schauen" und mehr Licht in die ansonsten schwer durchschaubaren Datenflüsse zu nehmen, die diese Geräte im Alltag produzieren. Der Name "LiMiT" ist insofern Programm und steht für "Licht in der Mitte des Tunnels".
  
Folgende Eigenschaften zeichnet $my_name aus:

- Es sind keine oder nur geringfügige Anpassungen der Geräte erforderlich, deren Daten aufgezeichnet werden sollen, insbesondere muss keine Software aufgespielt werden.
- Bei Geräten, auf denen ein eigenes SSL-Zertifikat installiert werden kann, können auch die meisten SSL-transportverschlüsselten Inhalte im Klartext aufgezeichnet werden.
- Das System bietet eine übersichtliche Nutzer-Oberfläche, die auf eine einfache Nutzung und flexible Datenauswertung ausgerichtet ist.
- $my_name kann auch von technisch weniger versierten Nutzern verwendet werden, die sich nicht täglich mit der Analyse von Datenströmen beschäftigen.
- Durch die Unterstützung des Internetzugangs über WLAN und UMTS und die Möglichkeit des Akku-Betriebs ist ein mobiler Einsatz problemlos möglich.
  
**$my_name wurde ausschließlich für den Zweck erstellt, um Einblick in Datenströme nehmen zu können, auf die zulässigerweise zugegriffen werden darf. Eine Verwendung für andere, insbesondere strafrechtlich verbotene Handlungen ist nicht bezweckt und wird ausgeschlossen.**
LIMIT1
 ) );
echo "</div></div></div>";

/*
 * setup
 */
tutorialSection ( "setup",
                  _ ( "Einrichtung" ) );
markDown ( _ ( <<<LIMIT1
#### <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuIcon" ]}"></i> {$__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuName" ]}
Zunächst sollten einige Standardwerte von $my_name überprüft und ggf. angepasst werden. Dies erfolgt im Menü [{$__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuName" ]}](settings.php). __Zumindest das WLAN-Passwort sollte auf einen eigenen Wert gesetzt werden.__
  
Beim ersten Start von $my_name sind folgende Werte eingestellt:

Parameter | Standardwert | Änderung?
--- | --- | ---
WLAN-Passwort |	limit1limit | aus Sicherheitsgründen wichtig!
SSID | limit1 | nur erforderlich, wenn mehrere $my_name gleichzeitig in Betrieb sind
WLAN-Kanal | 5 | bei Bedarf, z.B. wenn der Kanal in der Umgebung bereits in Benutzung ist
Name, unter der das {$my_name}-Gerät erreichbar ist | lim.it1 | nur erforderlich, wenn mehrere $my_name von einem Gerät aus erreicht werden sollen
IP-Adressbereich des $my_name WLAN-Netzwerks | 172.16.0.0 .. 172.16.0.255 | nur erforderlich, wenn mehrere $my_name von einem Gerät aus erreicht werden sollen
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "toolsCertificateMenuIcon" ]}"></i> {$__[ "include/topMenu" ] [ "values" ] [ "toolsCertificateMenuName" ]}

$my_name wird aus Sicherheitsgründen ohne Standard-Zertifikat ausgeliefert. Dadurch wird der Gefahr vorgebeugt, dass einheitliche Zertifikate auf Endgeräten installiert werden, so dass theoretisch fremder Netzwerkverkehr entschlüsselt werden könnte.
  
Vor der ersten Aufzeichnung muss daher im Menüpunkt [{$__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsCertificateMenuName" ]}](manageCertificate.php) ein individuelles Zertifikat erzeugt werden. Dieses Zertifikat muss auf allen Geräten installiert werden, deren verschlüsselter Datenverkehr im Klartext aufgezeichnet werden soll. Dies ist bei gängigen Smartphones meist möglich, gelingt allerdings nicht bei allen Gerätetypen (z.B. IoT-Geräte). Ist das Zertifikat auf einem Gerät nicht vorhanden, kann dessen Datenverkehr zwar mitgeschnitten werden, bleibt aber verschlüsselt und sein Inhalt daher nicht auswertbar. Dennoch kann der Mitschnitt wertvolle Hinweise liefern, z.B. mit welchen Servern das Gerät Kontakt aufnimmt, wann es Daten austauscht und in welchem Umfang.
LIMIT1
 ) );
echo "</div></div></div>";

/*
 * general info
 */
tutorialSection ( "general",
                  _ ( "Allgemeine Nutzungshinweise" ) );
markDown ( _ ( <<<LIMIT1
#### $my_name-Status

Über das Info-Symbol <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "statusMenuIcon" ]}"></i> in der Menüleiste erhält man jederzeit einen schnellen Überblick über den aktuellen Systemzustand.  
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Hilfe

Auf den meisten Seiten ist über das Fragezeichen-Icon <i class="fa {$__[ "include/utility" ] [ "values" ][ "helpSign" ]}"></i> eine kompakte Hilfe zu der entsprechenden Funktion verfügbar.
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Utility-Boxen

In der Menüleiste rechts befinden sich bis zu drei Eingabefelder für die [<i class="fa {$__[ "search" ][ "values" ][ "icon" ]}"></i> Freitextsuche](search.php), die [<i class="fa {$__[ "decode" ][ "values" ][ "icon" ]}"></i> Dekodierung](decode.php) und die [<i class="fa {$__[ "whois" ][ "values" ][ "icon" ]}"></i> Whois-Abfrage](whois.php). Welche dieser Boxen in der Menüleiste angezeict wird, kann unter [{$__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuName" ]}](settings.php) festgelegt werden. Die entsprechenden Funktionen sind auch über das Menü {$__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ]} erreichbar. Genauere Hinweise in diesem Tutorial unter "{$__[ "include/topMenu" ][ "values" ][ "toolsSearchMenuName" ]}", "{$__[ "include/topMenu" ][ "values" ][ "toolsDecodeMenuName" ]}" bzw. "{$__[ "include/topMenu" ][ "values" ][ "toolsWhoisMenuName" ]}".
  
Bei den meisten Browsern ist es möglich, eine markierte Textstelle per Drag-and-Drop in die Box zu ziehen, ohne dass Copy-Paste nötig ist. Mit Auslösen der entsprechenden Funktion wird der eingegebene Wert in der Box wieder gelöscht, so dass der nächste Wert eingegeben werden kann, ohne den vorherigen Inhalt manuell löschen zu müssen.

Unter [{$__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuName" ]}](settings.php) kann festgelegt werden, ob für diese Funktionen jeweils ein gesonderter Browser-Tabs verwendet wird.
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Entfaltbare Bereiche

Um die teilweise umfangreichen Ergebnisse einer Auswertung auch auf kleineren Bildschirmen noch überschaubar anzuzeigen, sind diese in der Regel in faltbare Abschnitte unterteilt. Von dieser Möglichkeit wird auch in diesem Tutorial Gebrauch gemacht. Solche Bereiche können durch Klick auf den Titel aus- und eingeklappt werden, um den Inhalt anzuzeigen bzw. zu verbergen. Wo dies sinnvoll ist, können mehrere Bereiche gleichzeitig aufgeklappt werden, ansonsten schließt das Öffnen eines Bereichs den zuvor geöffneten.

Bei Auswertungen sind die Titel von Abschnitten ohne Inhalt (z.B. ein Abschnitt zu Cookies, wenn keine Cookies gefunden wurden) blasser dargestellt, so dass das unnötige Aufklappen leerer Abschnitte vermieden werden kann.
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Tabellen

Um zusätzliche Flexibilität bei der Auswertung zu erreichen, werden viele Inhalte in Tabellenform dargestellt. Die Tabellen verfügen über folgende Möglichkeiten:

- __Seitenweise Darstellung__: Besteht eine Tabelle aus vielen Zeilen, werden sie seitenweise dargestellt. Über entsprechende Navigationsbuttons rechts unten kann schnell durch die Seiten geblättert werden. Zudem kann die Anzahl von Zeilen pro Seite angepasst werden (links oben). Unter [{$__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuName" ]}](settings.php) können die möglichen Zeilenzahlen festgelegt werden.
- __Suchfilter <i class="fa {$__[ "include/tableUtility" ][ "values" ][ "filterIcon" ]}"></i>__: Damit kann die Anzeige auf diejenigen Zeilen begrenzt werden, die einen entsprechenden Inhalt (in einer der Spalten) haben.
- __Faltung__: Lange Spalteneinträge werden standardmäßig gekürzt bzw. abgeschnitten dargestellt ({$__[ "include/tableUtility" ] [ "values" ] [ "foldedEllipses" ]}), um die Darstellung kompakt zu halten. Über die Buttons <span class="btn btn-default btn-xs"><i class="fa {$__[ "include/tableUtility" ][ "values" ][ "foldedIcon" ]}"></i></span> und <span class="btn btn-default btn-xs"><i class="fa {$__[ "include/tableUtility" ][ "values" ][ "unfoldedIcon" ]}"></i></span> kann zwischen der kompakten und der vollständigen Darstellung gewechselt werden. Dies ist auch direkt in gekürzten Spalten durch einfachen Mausklick, Rechtsklick oder Doppelklick möglich (je nach Einstellung).
- __Sortierung__: Die meisten Spalten können durch Klick auf die entsprechenden Icons im Spaltenkopf (<i class="fa fa-sort-amount-asc"></i> und <i class="fa fa-sort-amount-desc"></i>) sortiert werden. Eine Mehrfachsortierung ist unter Verwendung der Shift-Taste möglich.
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Whois-Informationen

Aus Tabelleneinträgen, in denen Domainnamen angezeigt werden, ist die [<i class="fa {$__[ "whois" ][ "values" ][ "icon" ]}"></i> Whois-Abfrage](whois.php) direkt erreichbar. Für den Teil eines Domainnamens, auf den der Mauszeiger zeigt, kann durch Klick direkt die entsprechende Whois-Abfrage gestartet werden. Dabei werden Namensteil unterschiedlich dargestellt, je nachdem ob in der Datenbank bereits Whois-Informationen vorhanden sind:

Ansicht | Bedeutung
--- | ---
www.example.com | Ansicht ohne Mausberührung
www.example.<span class="{$__[ "include/openHTML" ][ "values" ] [ "unknown" ]}">com</span> | Maus zeigt auf die TLD ".com"; hierfür sind keine Whois-Daten in der Datenbank vorhanden
www.<span class="{$__[ "include/openHTML" ][ "values" ] [ "known" ]}">example.com</span> | Maus zeigt auf die Domain "example.com"; hierfür sind Whois-Daten vorhanden, die durch Klick abgerufen werden können
<span class="{$__[ "include/openHTML" ][ "values" ] [ "unknown" ]}">www.example.com</span> | Maus zeigt auf den Hostnamen "www.example.com"; hierfür sind keine Whois-Daten in der Datenbank vorhanden, können bei vorhandener Internetverbindung aber abgerufen werden
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Ausschalten

Im {$__[ "include/topMenu" ] [ "values" ] [ "powerMenuName" ]}-Menü <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "powerMenuIcon" ]}"></i> kann $my_name neu gestartet oder heruntergefahren werden. Das Gerät sollte erst dann von der Stromversorgung getrennt werden, wenn der entsprechende Hinweis angezeigt wird.
LIMIT1
 ) );

echo "</div></div></div>";

/*
 * internet
 */
tutorialSection ( "internet",
                  _ ( "Mit dem Internet verbinden" ) );
markDown ( _ ( <<<LIMIT1
Über das Menü "{$__[ "include/topMenu" ] [ "values" ] [ "internetMenuName" ]}" kann der Internetzugang von $my_name gesteuert werden. Nach dem Einschalten besteht zunächst keine Internetverbindung. Um bereits vorhandene Aufzeichnungen auszuwerten, ist dies auch nicht erforderlich. Der Internetzugang wird jedoch für folgende Funktionen benötigt:

- Starten einer Aufzeichnung
- Online-Abfrage von Whois-Daten
- Importieren einer archivierten Aufzeichnung
- Prüfen und Herunterladen von Updates

Ob aktuell eine Internetverbindung besteht, kann jederzeit über die Status-Anzeige <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "statusMenuIcon" ]}"></i> geprüft werden.

Ist eine Internetverbindung hergestellt worden, bedeutet dies nicht unbedingt, dass die mit $my_name verbundenen Geräte ebenfalls einen Internetzugang haben. Es können zwei Varianten unter [{$__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuName" ]}](settings.php) festgelegt werden:

- Internetverbindung unabhängig von einer Aufzeichnung herstellen: Angeschlossene Geräte können jederzeit ins Internet, wenn $my_name mit dem Internet verbunden ist. Die übermittelten Daten eines Geräts werden erst dann mitgeschnitten, wenn eine Aufzeichnung für dieses Gerät gestartet wird.
- Internetverbindung nur während einer Aufzeichnung herstellen: Ein angeschlossenes Gerät wird individuell nur dann mit dem Internet verbunden, wenn gleichzeitig eine Aufzeichnung seiner Datenströme erfolgt. Auf diese Weise kann besser sichergestellt werden, dass keine Daten unaufgezeichnet bleiben. Allerdings kann es sein, dass die angeschlossenen Geräte den fehlenden Internetzugang als Fehler melden.

Sollte der erste Verbindungsaufbau nicht gelingen, kann es insbesondere bei Surfsticks sinnvoll sein, einen zweiten Versuch zu unternehmen.
LIMIT1
 ) );
/*
 * show this only on platforms with LAN adapter
 */
if ( ethernetCable () )
{
  markDown ( _ ( <<<LIMIT1
#### <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "internetLANMenuIcon" ]}"></i> {$__[ "include/topMenu" ] [ "values" ] [ "internetLANMenuName" ]}

Die Internetverbindung über den LAN-Anschluss herstellen. Es wird die automatische Konfiguration per DHCP und eine manuelle Einrichtung unterstützt.
  
Der Menüpunkt ist nur aktiviert, wenn ein LAN-Kabel eingesteckt ist.
LIMIT1
  ) );
}
markDown ( _ ( <<<LIMIT1
#### <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "internetWLANMenuIcon" ]}"></i> {$__[ "include/topMenu" ] [ "values" ] [ "internetWLANMenuName" ]}

Die Internetverbindung über einen USB-WLAN-Adapter herstellen. Es wird die Auswahl eines der empfangbaren WLAN in der Umgebung und die manuelle SSID-Eingabe unterstützt.
  
Der Menüpunkt ist nur aktiviert, wenn ein WLAN-Adapter eingesteckt ist. Der Adapter muss die nl80211 API unterstützen. 
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "internetUMTSMenuIcon" ]}"></i> {$__[ "include/topMenu" ] [ "values" ] [ "internetUMTSMenuName" ]}

Die Internetverbindung über einen UMTS-Adapter (Surfstick) herstellen. 

Der Menüpunkt ist nur aktiviert, wenn ein solcher Adapter eingesteckt ist. Unterstützt werden aktuell mindestens die Modelle ZTE MF 823 (USB-ID 19d2:1405) und Huawei E3531 (USB-ID 12d1:15ca).
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "internetOfflineMenuIcon" ]}"></i> {$__[ "include/topMenu" ] [ "values" ] [ "internetOfflineMenuName" ]}

Die Internetverbindung trennen. Der Menüpunkt ist nur aktiviert, wenn eine Internetverbindung besteht.
LIMIT1
 ) );
echo "</div></div></div>";

/*
 * do a recording
 */
tutorialSection ( "recording",
                  _ ( "Eine Aufzeichnung durchführen" ) );
markDown ( _ ( <<<LIMIT1
#### Aufzeichnung starten

Eine Aufzeichnung kann über den Aufnahmeknopf {$__[ "include/topMenu" ][ "values" ][ "recordStart" ]} gestartet werden. Um eine Aufnahme durchführen zu können, muss $my_name online und ein $my_name-Zertifikat erzeugt worden sein (siehe [{$__[ "include/topMenu" ][ "values" ][ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsCertificateMenuName" ]}](manageCertificate.php)). Soll SSL/TLS-verschlüsselter Verkehr im Klartext aufgezeichnet werden, muss das Zertifikat auf dem entsprechenden Gerät installiert worden sein, bevor die Aufzeichnung beginnt.

Sind mehrere Geräte mit $my_name verbunden, kann dasjenige ausgewählt werden, dessen Daten aufgezeichnet werden sollen. Standardmäßig wird das Gerät verwendet, von dem aus auch die Steuerung erfolgt. Sollen die Daten von Geräten, die nicht über Browser und Bildschirm verfügen aufgezeichnet werden (z.B. ein Internetradio), muss immer ein anderes mit $my_name verbundenes Gerät zur Steuerung eingesetzt werden.
  
Eine Aufzeichnung kann mit einem Namen und hilfreichen Erläuterungen versehen werden. Sind über das Menü [{$__[ "include/topMenu" ][ "values" ][ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsManageDevicesMenuName" ]}](manageDevices.php) Geräte definiert worden, kann der Aufzeichnung auch bereits eines der definierten Geräte zugeordnet werden. All diese Einstellungen können auch nachträglich vorgenommen oder geändert werden.

#### Laufende Aufzeichnung

Während eine Aufzeichnung läuft, werden die jeweils zuletzt aufgezeichneten Verbindungen angezeigt. Dadurch lässt sich erkennen, ob Daten fließen und zu welchen Servern das aufgezeichnete Gerät Kontakt aufnimmt.
  
Die Aufzeichnung der Daten erfolgt in zwei Phasen. Zunächst werden die Rohdaten, wie sie ins bzw. aus dem Internet übermittelt werden, aufgezeichnet (ggf. entschlüsselt, wenn dies möglich ist). Anschließend werden diese Rohdaten analysiert und interessante Elemente wie Cookies, Bilder, Verweise etc. extrahiert. Dieser Schritt erfolgt bereits während die Aufzeichnung läuft, benötigt jedoch mehr Zeit und hinkt der Aufzeichnung der Rohdaten daher immer ein Stück hinterher. In der Statusanzeige <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "statusMenuIcon" ]}"></i> wird angezeigt, für wieviele Verbindungen Rohdaten aufgezeichnet und wieviele davon bereits analysiert wurden.

Während eine Aufzeichnung läuft, kann mit $my_name normal gearbeitet werden, um z.B. bereits vorhandene Aufzeichnungen auszuwerten. 

#### Aufzeichnung beenden

Das Stop-Symbol {$__[ "include/topMenu" ][ "values" ][ "recordStop" ]} beendet eine laufende Aufzeichnung. Sind zu diesem Zeitpunkt noch nicht alle Verbindungsdaten verarbeitet, wird dies durch das Symbol {$__[ "include/topMenu" ][ "values" ][ "recordEnd" ]} angezeigt. Eine Auswertung der aufgezeichneten Daten sollte erst erfolgen, wenn dieser Schritt abgeschlossen ist und das Aufnahme-Symbol {$__[ "include/topMenu" ][ "values" ][ "recordStart" ]} wieder angezeigt wird.
LIMIT1
 ) );
echo "</div></div></div>";

/*
 * evaluate a recording
 */
tutorialSection ( "evaluate",
                  _ ( "Aufzeichnungen auswerten" ) );
markDown ( _ ( <<<LIMIT1
Um die während einer Aufzeichnung übertragenen Daten zu analysieren, stehen im Menü __{$__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuName" ]} <i class="fa {$__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuIcon" ]}"></i>__ verschiedene Funktionen zur Verfügung. Sie ermöglichen es einerseits, einzelne Bestandteile (Verbindungen, Requests) gezielt zu betrachten. Zudem können die Daten themenbezogen ausgewertet werden, z.B. um speziell Cookies zu betrachten.

#### Aufzeichnungen durchsuchen und bearbeiten

Unter [{$__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuRecordingsMenuName" ]}]("evaluateRecordings.php") werden die vorhandenen Aufzeichnungen aufgelistet. Jede Aufzeichnung besteht aus mehreren Verbindungen, und jede Verbindung besteht -- sofern es sich um HTTP(S)-Verbindungen handelt -- aus einem oder mehreren Requests. Diese Detailstufen sind hier ebenso erreichbar wie die folgenden Operationen:

- __Aufzeichnung bearbeiten:__ Der Name, die Erläuterungen und das mit der Aufzeichnung verbundene Gerät können nachträglich geändert werden
- __Aufzeichnung exportieren:__ Um eine Aufzeichnung dauerhaft zu sichern oder auf ein anderes $my_name-Gerät übertragen zu können, ist ein Export als Zip-Archiv möglich
- __Aufzeichnung löschen:__ Die Aufzeichnung wird aus der $my_name-Datenbank gelöscht. Dieser Vorgang kann nicht wieder rückgängig gemacht werden (es sei denn, die Aufzeichnung wurde zuvor exportiert und wird dann unter {$__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsImportMenuName" ]} wieder importiert).

LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Aufzeichnungen thematisch auswerten

Bei den verschiedenen thematischen Auswertungen ist es jeweils möglich, den Umfang der Auswertung festzulegen. Er kann auf eine bestimmte einzelne Aufzeichnung begrenzt werden oder alle Aufzeichnungen umfassen, entweder zu einer einzigen Aufzeichnung kombiniert oder als Auswerungsliste aller Aufzeichnungen. Der hier festgelegte Wert wird gespeichert, so dass er nicht bei jedem Auswertungsthema erneut angegeben werden muss.
  
Es stehen folgende Themenbereiche zur Verfügung:

- __{$__[ "include/topMenu" ] [ "values" ] [ "evaluatePropertiesMenuName" ]}:__ Diese Funktion ermöglicht den schnellen Überblick, welche der für ein mit einer Aufzeichnung verbundenes Gerät definierten Eigenschaften in den aufgezeichneten Daten enthalten ist. Sie stellt dabei einen Schnellzugriff auf die Suchfunktion dar, wobei jedoch bereits im Vorweg erkennbar ist, ob die Suche Treffer findet.
- __{$__[ "include/topMenu" ] [ "values" ] [ "evaluateContentsMenuName" ]}:__ Mit dieser Funktion können die Inhalte, die von http- oder https-Verbindungen übertragen werden einzeln betrachtet und analysiert werden. Bei diesen Inhalten kann es sich um Bilder, Javascript-Programme, Textdateien, PDF-Dokumente etc. handeln.
- __{$__[ "include/topMenu" ] [ "values" ] [ "evaluateImagesMenuName" ] }:__ Die in http- oder https-Verbindungen übertragenen Bilder können mit dieser Funktion rasch identifiziert und betrachtet werden. Die Funktion stellt insofern eine Spezialisierung der Auswertung [Inhalte](#inhalte) dar, bietet zudem aber noch eine Vorschau auf die Bilder.
- __{$__[ "include/topMenu" ] [ "values" ] [ "evaluateMetadataMenuName" ]}:__ In verschiedenen Inhaltstypen sind Metadaten wie Kommentare oder Titel enthalten. Solche Daten werden von dieser Funktion angezeigt.
- __{$__[ "include/topMenu" ] [ "values" ] [ "evaluateHeadersMenuName" ]}:__ In den Header-Zeilen von http- oder https-Requests bzw. -Responses lassen sich häufig interessante Daten finden. Mit dieser Funktion können solche Daten identifiziert werden.
- __{$__[ "include/topMenu" ] [ "values" ] [ "evaluateCookiesMenuName" ]}:__ Die in http- oder https-Verbindungen übertragenen Cookies werden mit dieser Funktion ausgewertet. Es sind sowohl empfangene als auch versandte Cookies zugänglich.
- __{$__[ "include/topMenu" ] [ "values" ] [ "evaluateLinksMenuName" ]}:__ Es gibt verschiedene Tehniken, mit denen ein Server auf einen anderen verweist, z.B. mittels des Referer-Headers. Diese Funktion stellt sämtliche solcher Verweise übersichtlich zusammen.
- __{$__[ "include/topMenu" ] [ "values" ] [ "evaluateSSLMenuName" ]}:__ Eine SSL-Verschlüsselung kann in vielerlei Hinsicht parametrisiert sein, z.B. in Hinblick auf die Schlüssellänge oder den Verschlüsselungsalgorithmus. Die unterschiedlichen SSL-Varianten der aufgezeichneten Verbindungen (https oder ssl) können mit dieser Funktion analysiert werden.
- __{$__[ "include/topMenu" ] [ "values" ] [ "evaluateCertificatesMenuName" ]}:__ Zu jeder SSL-Verschlüsselung gehört serverseitig ein Zertifikat, mit dem der Server sich identifiziert und seine Vertrauenswürdigkeit im Zusammenhang mit einem der Root-Zertifikate des Browsers belegt. Welche Zertifikate in den aufgezeichneten Daten enthalten sind, lässt diese Funktion erkennen.
LIMIT1
 ) );
echo "</div></div></div>";

/*
 * tools
 */
tutorialSection ( "tools",
                  $__[ "include/topMenu" ][ "values" ][ "toolsMenuName" ] );
markDown ( _ ( <<<LIMIT1
In diesem Menü sind Funktionen versammelt, die nicht direkt der Aufzeichnung oder Auswertung von Daten dienen, das Funktionieren von $my_name aber sinnvoll unterstützen.

#### {$__[ "include/topMenu" ][ "values" ][ "toolsSearchMenuName" ]}

Mit dieser Funktion, die auch über eine Utility-Box erreichbar ist, können die aufgezeichneten Daten flexibel durchsucht werden. Genauere Hinweise unter "{$__[ "include/topMenu" ][ "values" ][ "toolsSearchMenuName" ]}".

#### {$__[ "include/topMenu" ][ "values" ][ "toolsDecodeMenuName" ]}

Häufig sind kodierte Inhalte in den aufgezeichneten Daten enthalten. Diese Funktion, die auch über eine Utility-Box erreichbar ist, ermöglicht es, den Klartext solcher Inhalte anzuzeigen. Genauere Hinweise unter "{$__[ "include/topMenu" ][ "values" ][ "toolsDecodeMenuName" ]}".

#### {$__[ "include/topMenu" ][ "values" ][ "toolsWhoisMenuName" ]}

Diese Funktion, die auch über eine Utility-Box erreichbar ist, dient zur Hintergrundrecherche von aufgezeichneten Domainnamen. Genauere Hinweise unter "{$__[ "include/topMenu" ][ "values" ][ "toolsWhoisMenuName" ]}".

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsManageDevicesMenuName" ]}

Um bestimmte Geräte- oder Nutzerdaten wie MAC- oder E-Mail-Adresse usw. in den übertragenen Daten einfacher auffinbar zu machen, können solche Eigenschaften über dieses Menü gerätebezogen verwaltet werden.

Geräte können zunächst unabhängig von den Aufzeichnungen angelegt und mit den gewünschten Eigenschaften definiert werden. Bereits definierte Geräte können dann einer neuen oder bestehenden Aufzeichnung zugeordnet werden. Über die entsprechende Auswertungsfunktion [{$__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "evaluatePropertiesMenuName" ]}](evaluateProperties.php) können anschließend die Eigenschaften des zugeordneten Geräts gezielt gesucht werden, ohne dass deren Werte neu eingetippt werden müssen.

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsCertificateMenuName" ]}

Damit LiMiT1 auch SSL-verschlüsselte Inhalte im Klartext aufzeichnen kann, wird ein Zertifikat benötigt, das dem Gerät, dessen Daten aufgezeichnet werden, bekannt ist. LiMiT1 baut dann zwei getrennte SSL-Verbindungen auf – eine zu dem untersuchten Gerät und eine andere zu dem Internet-Server, den das Gerät anspricht.

Mit dieser Funktion kann ein solches Zertifikat erzeugt werden und über auf das gewünschte Gerät heruntergeladen werden. Auf jedem Gerät, dessen SSL-verschlüsselte Daten im Klartext aufgezeichnet werden sollen,  muss das Zertifikat installiert worden sein, bevor eine Aufzeichnung erfolgt, mit der SSL-verschlüsselter Verkehr im Klartext aufgezeichnet werden soll.

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsImportMenuName" ]}

Die in der $my_name-Datenbank vorhandenen Aufzeichnungen können im Menü [{$__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuRecordingsMenuName" ]}]("evaluateRecordings.php") einzeln archiviert und exportiert werden, z.B. um sie für einen späteren Zeitpunkt aufzuheben. Mit diesem Menüpunkt kann eine so exportierte Aufzeichnung wieder zurückgespielt werden. Dies ist auch zwischen verschiedenen $my_name-Systemen möglich, so dass eine Aufzeichnung unter verschiedenen Geräten ausgetauscht werden kann.

Zum Zeitpunkt des Imports sollte eine Internetverbindung bestehen, damit Domainnamen korrekt aufgelöst werden können.

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsPurgeMenuName" ]}

Wenn sämtliche Aufzeichnungen nicht mehr benötigt werden, kann die Datenbank mit dieser Funktion komplett geleert werden. Es können verschiedene Teile der Datenbank ausgewählt werden, die geleert werden sollen.

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsMountMemoryStickMenuName" ]} / {$__[ "include/topMenu" ] [ "values" ] [ "toolsUnmountMemoryStickMenuName" ]}

Aufzeichungen und andere Daten können auf der SD-Karte des Systems oder auf einem USB-Stick gespeichert werden. Mit diesen Funktionen kann ein USB-Stick eingebunden bzw. entfernt werden. Aktuell werden nur FAT32-formatierte Sticks unterstützt.
  
Solange ein Stick eingebunden ist, stehen die Aufzeichnungen, die auf der SD-Karte gespeichert wurden, nicht zur Verfügung.

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuName" ]}

Mit dieser Funktion kann $my_name individuell angepasst werden.

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsStatusMenuName" ] }

Hier sind detailliertere Systeminformationen abrufbar.

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsUpdateMenuName" ] }

Wenn $my_name online ist, kann hiermit überprüft werden, ob eine neue Software-Version verfügbar ist. Neue Versionen können direkt installiert werden.

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsTutorialMenuName" ]}
Dieses Tutorial anzeigen.

#### {$__[ "include/topMenu" ] [ "values" ] [ "toolsAboutMenuName" ]} $my_name

Mit diesem Menüpunkt werden Lizenzinformationen angezeigt.
LIMIT1
 ) );
echo "</div></div></div>";


/*
 * search
 */
tutorialSection ( "search",
                  _ ( "Suche" ) );
markDown ( _ ( <<<LIMIT1
Mit Hilfe dieser Funktion können die aufgezeichneten Daten einfach und flexibel durchsucht werden. Im einzelnen bestehen folgende Möglichkeiten:
  
- Begrenzung auf eine einzelne Aufzeichnung oder Berücksichtigung aller Aufzeichnungen
- optionale Unterscheidung zwischen Groß- und Kleinschreibung
- optionale Verwendung regulärer Ausdrücke als Suchkriterium. Zeichen, die nicht als Musterzeichen interpretiert werden sollen, müssen dabei entwertet werden (z.B. `\.` um nach einem Punkt zu suchen statt nach einem beliebigen Zeichen)
- Festlegung der Suchfelder (z.B. nur HTTP-Response-Header)
 
Für jedes Suchergebnis wird die Quelle und der textliche Kontext angezeigt, in dem das Ergebnis steht. Wird der gesuchte Text mehrmals in einer Quelle gefunden, kann zwischen den verschiedenen Fundstellen hin- und hergesprungen werden.
LIMIT1
 ) );
echo "</div></div></div>";

/*
 * decode
 */
tutorialSection ( "decode",
                  _ ( "Dekodieren" ) );
markDown ( _ ( <<<LIMIT1
In den aufgezeichneten Daten sind häufig kodierte Werte enthalten, die in dieser Form nicht ausgewertet werden können, obwohl der dekodierte Text vielleicht durchaus interessante Informationen liefern kann. $my_name unterstützt die Dekodierung, ohne dass angegeben werden muss, welche Kodierungstechnik zum Einsatz kommt. Dies erfolgt, indem verschiedene Kodierungen durchprobiert werden. Dadurch werden zwar viele Fehltreffer erzeugt, aber in der Regel ist es sehr schnell möglich, die richtige Dekodierung zu erkennen. Dies wird zusätzlich dadurch unterstützt, dass die Teile des Texts, die erfolgreich dekodiert werden konnten, optisch hervorgehoben werden.

Die möglichen Kodierungen werden auch in Kombination miteinander ausprobiert, da nicht auszuschließen ist, dass Werte doppelt oder sogar mehrfach kodiert sind.
LIMIT1
 ) );
echo "</div></div></div>";

/*
 * whois
 */
tutorialSection ( "whois",
                  _ ( "Whois" ) );
markDown ( _ ( <<<LIMIT1
$my_name unterstützt die Abfrage und Verwaltung von Whois-Daten. Ist das System online, können diese Daten direkt aus dem Internet abgerufen werden. Die Daten werden dabei in der $my_name-Datenbank gespeichert, so dass ein Zugriff auch offline möglich bleibt. Ältere Abfrageergebnisse bleiben jeweils erhalten und ermöglichen so einen Vergleich der Ergebnisse früherer Abrufe.

#### Domainabfrage
  
Um die Whois-Daten einer Domain zu erhalten, wird das Formular ohne Musterzeichen-Option verwendet. Sind bereits aus früheren Abfragen dieser Domain Whois-Daten gespeichert, werden diese angezeigt. Eine erneute Online-Abfrage ist jederzeit möglich, wenn $my_name online ist. Sind noch keine Whois-Daten zu dieser Domain gespeichert, wird direkt eine Online-Abfrage durchgeführt (bzw. ein Fehler angezeigt, wenn $my_name nicht online ist).

Jede Whois-Information kann als Text, also im Original, oder als Tabelle angezeigt werden. Die Tabellenansicht ist kompakter, da Kommentare weggelassen werden und jedes Schlüssel-Wert-Paar nur einmal aufgelistet wird.
  
#### Domainsuche
  
Um die in der $my_name-Datenbank enthaltenen Whois-Daten abzufragen, wird das Formular mit Musterzeichen-Option verwendet. Es erfolgt dann nur eine Suche in den gespeicherten Whois-Daten ohne einen Online-Abruf. Diese Abfrage dient dazu, einen Überblick über die bereits gespeicherten Whois-Daten zu bekommen.
  
Eine Abfrage mit dem Wert `beispiel` etwa würde (sofern diese Daten vorhanden sind) die Whois-Daten der Domain `beispiel.de`, `beispiel.at`, `zum.beispiel.de`, `beispiele.org` anzeigen.
  
Die Abfrage von `\.de$` liefert alle gespeicherten de-Domains:
- Da `.` selbst ein Musterzeichen ist, muss `\.` verwendet werden, wenn das wörtliche Punkt-Zeichen gemeint ist.
- Damit tatsächlich nur de-Domains und nicht z.B. solche mit TLD `.design` gefunden werden, muss die Abfrage mit `$` abgeschlossen werden (matcht mit dem Textende).
LIMIT1
 ) );
echo "</div></div></div>";

/*
 * technical background infos
 */
tutorialSection ( "background",
                  _ ( "Technische Hintergrundinformationen" ) );
markDown ( _ ( <<<LIMIT1
#### WLAN-Router
$my_name verhält sich aus Sicht eines untersuchten Geräts wie jeder andere WLAN-Router: Es wird ein Funknetz zur Verfügung gestellt, über das eine Verbindung ins Internet hergestellt werden kann. Dies passiert bei nahezu jedem heimischen DSL-Zugang, in Hotels, bei öffentlichen WLAN.

<table class="table-condensed" style="margin:5px; border:solid 1px"><tbody><tr style="text-align:center">
<td><i class="fa fa-laptop fa-2x"></i></td>
<td><i class="fa fa-long-arrow-left"></i> <i class="fa fa-wifi"></i> <i class="fa fa-long-arrow-right"></i></td>
<td><span class="fa-stack fa-lg"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-arrows-alt fa-stack-1x"></i></span></td>
<td><i class="fa fa-long-arrow-left"></i> z.B. DSL <i class="fa fa-long-arrow-right"></i></td>
<td><i class="fa fa-cloud fa-2x"></i></td>
</tr><tr style="text-align:center">
<td>Endgerät</td>
<td>WLAN</td>
<td>Router</td>
<td>Verbindung</td>
<td>Internet</td>
</tr></table><br>

Wie bei jedem solchen Internetzugang fließen sämtliche Daten, die zwischen einem angeschlossenen Gerät und einem Server im Internet ausgetauscht werden, durch den Router. Dieser ist daher prinzipiell in der Lage, den gesamten Datenverkehr wahrzunehmen, aufzuzeichnen und auch zu kontrollieren. Davon wird z.B. aus Sicherheitsgründen häufig Gebrauch gemacht, indem in den meisten Routern eine Firewall den Datenverkehr auf das gewünschte Maß begrenzt.
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Verschlüsselung
Typischerweise sind bei einem solchen Szenario verschiedene Verschlüsselungen im Einsatz, die es vor allem Außenstehenden unmöglich machen sollen, Einblick in die übertragenen Daten zu nehmen:
  
<table class="table-condensed" style="margin:5px; border:solid 1px"><tbody>
<tr style="text-align:center">
<td></td>
<td><i class="fa fa-long-arrow-left text-danger"></i></td>
<td class="text-danger">WPA2</td>
<td><i class="fa fa-long-arrow-right text-danger"></i></td>
<td></td>
<td></td>
<td></td>
<td></td>
</tr>
<tr style="text-align:center">
<td></td>
<td><i class="fa fa-long-arrow-left text-danger"></i></td>
<td class="text-danger" colspan=4>SSL / TLS</td>
<td><i class="fa fa-long-arrow-right text-danger"></i></td>
<td></td>
</tr>
<tr style="text-align:center">
<td colspan=2><i class="fa fa-laptop fa-2x"></i></td>
<td><i class="fa fa-long-arrow-left"></i> <i class="fa fa-wifi"></i> <i class="fa fa-long-arrow-right"></i></td>
<td colspan=2><span class="fa-stack fa-lg"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-arrows-alt fa-stack-1x"></i></span></td>
<td><i class="fa fa-long-arrow-left"></i> z.B. DSL <i class="fa fa-long-arrow-right"></i></td>
<td colspan=2><i class="fa fa-cloud fa-2x"></i></td>
</tr><tr style="text-align:center">
<td colspan=2>Endgerät</td>
<td>WLAN</td>
<td colspan=2>Router</td>
<td>Verbindung</td>
<td colspan=2>Internet</td>
</tr></table><br>

Die mit __WPA2__ gekennzeichnete Verbindungsverschlüsselung im WLAN verhindert, dass der per Funk übertragene Datenverkehr abgehört werden kann. Sie wirkt nur zwischen Endgerät und Router und wirkt sich nicht auf die nachfolgende Verbindung zum Internet aus. Auch im Router selbst liegen die Daten ohne WPA2-Verschlüsselung vor.
  
Die mit __SSL/TLS__ gekennzeichnete Verbindungsverschlüsselung verhindert, dass der zwischen dem Endgerät und einem Server im Internet ausgetauschte Datenverkehr von Lauschern im WLAN, auf der Stecke zum Server oder auf dem Router selbst abgehört werden kann. Nur Endgerät und Server sind so in der Lage, die Klardaten wahrzunehmen.
  
Zusätzlich ist noch eine __Ende-zu-Ende-Verschlüsselung__ zwischen Endgerät und Server im Internet denkbar. Dabei werden Daten auf dem Endgerät zunächst verschlüsselt und erst dann übertragen. Auch hier sind nur Endgerät und Server in der Lage, die Klardaten wahrzunehmen. Anders als bei SSL/TLS kann diese Verschlüsselung am Router nicht aufgelöst werden.
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Man in the Middle
Die oben erwähnte SSL-Verschlüsselung schützt die gesamte Verbindung zwischen Endgerät und Server. Für den in der Mitte liegenden Router sind die so verschlüsselten Daten nicht im Klartext lesbar. Allerdings kann der Router unter bestimmten Bedingungen erreichen, dass die Daten "umgeschlüsselt" werden und dadurch zwischendurch im Klartext vorliegen. Für diese Art von "Angriff" hat sich der Begriff _Man in the Middle_ etabliert.

Der Router sorgt dabei dafür, dass statt einer zwei verschiedene SSL-Verschlüsselungen aufgebaut werden, eine zwischen dem Endgerät und dem Router (SSL#1) und eine weitere zwischen dem Router und dem Server (SSL#2):
<table class="table-condensed" style="margin:5px; border:solid 1px"><tbody>
<tr style="text-align:center">
<td></td>
<td><i class="fa fa-long-arrow-left text-danger"></i></td>
<td class="text-danger">WPA2</td>
<td><i class="fa fa-long-arrow-right text-danger"></i></td>
<td></td>
<td></td>
<td></td>
<td></td>
</tr>
<tr style="text-align:center">
<td></td>
<td><i class="fa fa-long-arrow-left text-danger"></i></td>
<td class="text-danger">SSL#1</td>
<td><i class="fa fa-long-arrow-right text-danger"></i></td>
<td><i class="fa fa-long-arrow-left text-danger"></i></td>
<td class="text-danger">SSL#2</td>
<td><i class="fa fa-long-arrow-right text-danger"></i></td>
<td></td>
</tr>
<tr style="text-align:center">
<td colspan=2><i class="fa fa-laptop fa-2x"></i></td>
<td><i class="fa fa-long-arrow-left"></i> <i class="fa fa-wifi"></i> <i class="fa fa-long-arrow-right"></i></td>
<td colspan=2><span class="fa-stack fa-lg"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-arrows-alt fa-stack-1x"></i></span></td>
<td><i class="fa fa-long-arrow-left"></i> z.B. DSL <i class="fa fa-long-arrow-right"></i></td>
<td colspan=2><i class="fa fa-cloud fa-2x"></i></td>
</tr><tr style="text-align:center">
<td colspan=2>Endgerät</td>
<td>WLAN</td>
<td colspan=2>Router</td>
<td>Verbindung</td>
<td colspan=2>Internet</td>
</tr></table><br>
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
Um dies zu erreichen, sind folgende Voraussetzungen erforderlich:
- Der Router muss in den Aufbau der SSL-Verbindung zwischen Endgerät und Server eingreifen und dem Endgerät vortäuschen, er sei selbst der Server (dafür ist auf dem Router entsprechende Software erforderlich; bei $my_name ist dies _sslsplit_)
- Auf dem Endgerät muss ein Zertifikat installiert sein, das der Router erzeugt und selbst signiert hat, so dass das Endgerät dem Router vertraut (ein solches Zertifikat kann unter Menüpunkt [{$__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ]}>{$__[ "include/topMenu" ] [ "values" ] [ "toolsCertificateMenuName" ]}](manageCertificate.php) erzeugt und verwaltet werden)
- Der Webserver darf kein Zertifikat verwenden, das durch sog. Certificate Pinning geschützt ist (durch diese Maßnahme wird verhindert, dass eine fremde Instanz, also der Router, ein Zertifikat für den Server ausstellt)
LIMIT1
 ) );
markDown ( _ ( <<<LIMIT1
#### Implementierung bei $my_name
Das $my_name-System ist so konzipiert, dass die vielen Einstellungen auf Systemebene, die für eine Man-in-the-Middle-Konfiguration erforderlich sind, automatisch erfolgen. Der Nutzer kann sich dadurch auf die Datenauswertung konzentrieren. Vor einer Aufzeichnung muss lediglich ein entsprechendes Zertifikat erzeugt und auf dem zu untersuchenden Gerät installiert werden. Zudem muss ggf. eingestellt werden, auf welchen Ports SSL-verschlüsselter Verkehr abgewickelt wird, sofern dies von den üblichen Standards abweicht (z.B. Port 443 für HTTPS, Port 993 für IMAPS) oder eigene Ports verwendet werden.
LIMIT1
 ) );
echo "</div></div></div>";

echo "</div>";

include ("include/closeHTML.php");
