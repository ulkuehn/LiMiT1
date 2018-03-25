# LiMiT1

Software, um einen Raspberry Pi 3, Zero W oder 2 für eine WLAN-basierte Datenaufzeichnung und -analyse zu nutzen.

## Überblick
Bei LiMiT1 handelt sich um ein System, das dabei hilft, den Internet-Datenverkehr eines WLAN-fähigen Geräts (Smartphone, Tablet, Laptop, Smart-TV, IoT-Device, …) aufzuzeichnen und zu analysieren. Bei Geräten, auf denen ein eigenes Zertifikat installiert werden kann, ist dabei auch der Einblick in SSL-transportverschlüsselte Inhalte möglich.

Folgende Eigenschaften zeichnet LiMiT1 aus:

- Es sind keine oder nur geringfügige Anpassungen der Geräte erforderlich, deren Daten aufgezeichnet werden sollen.
- Insbesondere muss keine Software aufgespielt werden.
- Daher kann LiMiT1 zur Überprüfung der Datenflüsse nahezu jedes (WLAN-fähigen) Geräts eingesetzt werden.
- Das System bietet eine übersichtliche Web-Oberfläche, die auf eine einfache Nutzung und flexible Datenauswertung ausgerichtet ist.
- LiMiT1 ist daher auch von technisch weniger versierten Nutzern zu verwenden.
- Durch die Unterstützung des Internetzugangs über WLAN und UMTS und die Möglichkeit des Betriebs per Akku ist ein mobiler Einsatz möglich.

### LiMiT1?

"LiMiT" ist eine Abkürzung für "Licht in der Mitte des Tunnels". Der Begriff "Tunnel" steht dabei für die Verbindung eines Geräts mit dem Internet und die dabei übertragenen Daten, die normalerweise nicht (jedenfalls nicht einfach) zugänglich sind. Als sog. Man-In-The-Middle-Device kann LiMiT1 dabei helfen, Licht in diese ansonsten dunklen Datenflüsse zu bringen. Da es sich um die erste Version handelt, ergibt sich insgesamt der Name `LiMiT1`.

### Bestandteile

LiMiT1 besteht aus folgenden Teilen:

- Raspberry Pi 3 (bevorzugt)
- oder Raspberry Pi Zero W (bevorzugt für mobilen Einsatz)
- oder Raspberry Pi 2 mit hostap-fähigem WLAN-Adapter
- Stromversorgung
- SD-Karte mit konfigurierter LiMiT1-Software
- USB-Speicher-Stick (optional)

Zur Herstellung einer Internetverbindung ist zusätzlich mindestens eines der folgenden erforderlich:

- Netzwerkkabel mit Anschluss an ein LAN (nicht für Pi Zero W)
- WLAN-Adapter
- UMTS-Stick

Zur Steuerung ist ein Laptop oder Smartphone erforderlich, zudem ggf. das Gerät, dessen Daten aufgezeichnet werden sollen. Sofern es über einen geeigneten Web-Browser verfügt, kann die Steuerung problemlos auch direkt durch das Gerät erfolgen, dessen Daten aufgezeichnet werden sollen.

### Konzept

LiMiT1 funktioniert wie jeder gewöhnliche Internet-WLAN-Router, indem er den über das aufgespannte WLAN angeschlossenen Geräten den Zugang ins Internet vermittelt. Zusätzlich zu dieser reinen Vermittlung finden weitere Prozesse statt:

- Die Daten, die zwischen dem angeschlossenen Gerät und dem Internet ausgetauscht werden, werden in einer Datenbank gespeichert.
- Soweit möglich, werden SSL-verschlüsselte Verbindungen umgeschlüsselt, so dass der Inhalt für die spätere Auswertung im Klartext vorliegt.

Zusätzlich zu dieser Routing-Funktion bietet LiMiT1 ein vielfältiges Set von Auswertungswerkzeugen, mit denen aufgezeichnete Daten betrachtet, durchsucht und analysiert werden können.

### Nutzungshinweis

**Diese Software wurde ausschließlich für den Zweck erstellt, um Einblick in Datenströme nehmen zu können, auf die zulässigerweise zugegriffen werden darf. Eine Verwendung für andere, insbesondere strafrechtlich verbotene Handlungen ist nicht bezweckt und wird ausgeschlossen.**

## Herstellung eines LiMiT1-Systems

Dieser Schritt besteht im Wesentlichen aus der Konfiguration einer SD-Karte. Hierfür werden benötigt:

- eine FAT32 formatierte Micro-SD-Karte mit mindestens 4 GB Kapazität,
- ein Raspberry Pi,
- ein USB-Netzteil,
- eine USB-Tastatur,
- ein HDMI-fähiger Bildschirm,
- Internetanschluss per Ethernet,
- ein Raspian-Lite-Image (s.u.),
- Dateien [limitify.sh](./limitify.sh) und [limit1.tar.bz2](./limit1.tar.bz2) aus diesem Repository

### Raspian-Lite-Image dowloaden

Das Betriebssystem-Image kann unter <http://downloads.raspberrypi.org/raspbian_lite/images/raspbian_lite-2017-07-05/2017-07-05-raspbian-jessie-lite.zip> heruntergeladen werden (ca. 300 MB). Bitte nur diese aktuell unterstützte Fassung verwenden! Nach dem Entzippen der Datei steht das Raspberry-Lite-Image als .img-Datei zur Verfügung (ca. 1.7 GB).

### SD-Karte vorbereiten

Mit einem SD-Kartenimager (für Windows z.B. [Win32 Disk Imager](https://sourceforge.net/projects/win32diskimager/)) das Raspberry-Lite-Image auf die SD-Karte schreiben. Unter Windows sinkt die scheinbare Größe der SD-Karte danach auf wenige MB, da der Rest der Karte vom Linux-System benutzt wird.

Anschließend die Dateien [limitify.sh](./limitify.sh) und [limit1.tar.bz2](./limit1.tar.bz2) aus diesem Repository auf die SD-Karte kopieren. Hierfür ist noch genügend Platz auf der FAT-formatierten und unter Windows sichtbaren Partition `boot`.

### Gerät starten

##### Mit WLAN (Headless)

Verfügt der Raspberry über WLAN, kann das System "headless", d.h. ohne Tastatur, Bildschirm und Ethernet eingerichtet werden. Für Modelle ohne (eingebauten) Ethernet-Anschluss (Zero W) ist dies die einzige Möglichkeit.

In der unter Windows sichtbaren Partition `boot` der SD-Karte muss dann noch zusätzlich eine leere Datei mit dem Namen `ssh` (oder `ssh.txt`) angelegt werden. Zudem ist eine Datei mit dem Namen `wpa_supplicant.conf` und folgendem Inhalt erforderlich:

```
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
country=DE

network={ 
ssid="<SSID>" 
psk="<PWD>"
key_mgmt=WPA-PSK 
}
```

Statt `<SSID>` muss die SSID des WLAN eingetragen werden, das benutzt werden soll, statt `<PWD>` ist das zugehörige WLAN-Passwort im Klartext zu verwenden.

Wird die so vorbereitete SD-Karte eingesetzt und das Gerät anschließend mit Hilfe des USB-Netzteils in Betrieb genommen, bucht es sich nach einem kurzen Setup in das angegebene WLAN ein. Über einen SSH-Client ist dann ein Remote-Zugriff von einem anderen Gerät in selben Netzwerk aus möglich. Um die IP-Adresse zu ermitteln, die das Gerät erhalten hat, kann auf dem Router nachgesehen oder ein Netzwerkscanner eingesetzt werden.

##### Ohne WLAN
Den Raspberry Pi mit Tastatur, Bildschirm und Ethernet verbinden und die vorbereitete SD-Karte einsetzen. Das Gerät anschließend mit Hilfe des USB-Netzteils in Betrieb nehmen. Nach kurzer Zeit ist das Raspbian-System initialisiert und es steht ein Login-Prompt zur Verfügung.

### Software konfigurieren

Als Nutzer `pi` mit Passwort `raspberry` anmelden. Bitte beachten: Da zunächst die englische Tastatur eingestellt ist, sind `[ y ]` und `[ z ]` vertauscht; zudem ist das Zeichen `[ / ]` unter der Taste `[ - ]` zu finden, `[ * ]` unter `[ ( ]`.

Anschließend ausführen

`$ cd /boot`

`$ sudo bash ./limitify.sh`

Dabei können zusätzlich folgende Optionen verwendet werden:

Option| Parameter | Bedeutung
---|---|---
-r | password | set root password
-l | country.coding | set system locale
-L | | list available locales and exit
-t | timezone | set system timezone
-T | | list available timezones and exit
-s | ssid | set default ssid for LiMiT1 wifi
-p | password | set default password for LiMiT1 wifi
-c | channel | set default wifi channel

Soll z.B. der WLAN-Kanal auf 10 und die Zeitzone auf Madrid gesetzt werden, müssen folgende Optionen ergänzt werden:

`$ sudo bash ./limitify.sh -c 10 -t "Europe/Madrid"`

Das Skript führt eine Vielzahl von Operationen - insbesondere ein System-Update - durch, so dass der Gesamtvorgang (je nach Hardware, Internetgeschwindigkeit und Aktualisierungsbedarf) mehrere (bis zu 30) Minuten dauern kann. Dabei scheint das System immer wieder lange Zeit inaktiv zu sein. Dies ist jedoch nicht der Fall, wie der Blick auf die grüne SYstem-LED zeigt. Sie flackert jeweils, um die entsprechenden Schreibzugriffe auf die SD-Karte zu signalisieren. 

Nach Abschluss des Vorgangs rebootet das System. Es ist ein zusätzlicher Nutzer `root` mit Passwort `limit1` eingerichtet, der für den Systemzugriff verwendet werden kann (hierfür steht über das WLAN-Interface auch ein SSH-Server zur Verfügung). Es ist nun auch das deutsche Tastaturlayout wie gewohnt verwendbar. 

LiMiT1 ist jetzt prinzipiell einsatzbereit, für den erfolgreichen Betrieb sind aber unbedingt die Inbetriebnahme-Hinweise (s.u.) zu beachten. Sofern nicht bereits alle erforderlichen Hardware-Bestandteile eingebaut sind, sollte das System zunächst mittels `# halt` (bzw. `$ sudo halt`) heruntergefahren werden.


## Inbetriebnahme von LiMiT1

LiMiT1 wird durch Anschließen an die Stromversorgung gestartet. Ein erfolgreicher Start ist nur möglich, wenn eine SD-Karte mit der LiMiT1-Software im SD-Kartenslot 
eingesteckt ist. Tastatur und Bildschirm sind nicht erforderlich.

Wird ein Raspberry Pi 2 verwendet, muss zusätzlich ein für den HostAP-Betrieb geeigneter USB-WLAN-Dongle eingesteckt worden sein. Ein erfolgreich getesteter WLAN-Stick ist Ralink RT5370 (USB-ID 148f:5370). Mehr Details unter <http://elinux.org/RPI-Wireless-Hotspot> und <http://elinux.org/RPi_USB_Wi-Fi_Adapters>.  

Während der Startphase blinkt die grüne LED von LiMiT1 im sog. "Heartbeat"-Modus (jeweils zwei kurze Pulse hintereinander). Dies ändert sich in einen einzelnen Puls alle vier Sekunden, sobald LiMiT1 einsatzbereit ist; dies dauert einige Sekunden. Befindet sich noch keine LiMiT1-Datenbank auf dem Speicherstick, kann der Start deutlich länger dauern.

Als Stromquelle ist neben einem Netzteil mit Mikro-USB-Anschluss auch ein entsprechender Akku ("Powerbank") geeignet. Die Stromquelle sollte mehr als 1 A (1000 mA) bereitstellen, damit LiMiT1 stabil funktioniert.

## Verbindung zu LiMiT1

Nach dem Start stellt das System ein WLAN zur Verfügung, über das sich andere Geräte  mit LiMiT1 verbinden können. Der Zugriff erfolgt über eine Web-Schnittstelle, d.h. den Internet-Browser.

Die SSID, das Passwort und die IP-Adresse bzw. Domainnamen, unter dem LiMiT1 erreichbar ist, können in den Einstellungen geändert werden. Direkt nach der Installation gelten folgende Standardwerte:

Parameter | Standardwert
--- | ---
SSID | limit1
WLAN-Passwort |	limit1limit
lokales Netzwerk | 172.16.0.0 .. 172.16.0.255
eigene IP-Adresse | 172.16.0.1
Domainname | lim.it1

## Erstinbetriebnahme

### Tutorial

In das System ist ein ausführliches Tutorial integriert, auf das bei jedem Start hingewiesen wird. Insbesondere beim ersten Start ist es empfehlenswert, dieses Tutorial durchzulesen. Diese Anleitung geht nur auf wenige wichtige Aspekte ein.

### Einstellungen

Zunächst sollte das Gerät individuell angepasst werden. Hierzu dient das Menü Werkzeuge/Einstellungen. Es sollte zumindest das WLAN-Passwort geändert werden. Beim ersten Start von LiMiT1 sind folgende Werte eingestellt:

Parameter | Standardwert | Änderung?
--- | --- | ---
SSID | limit1 | bei Bedarf
WLAN-Passwort |	limit1limit | dringend empfohlen
lokales Netzwerk | 172.16.0.0 .. 172.16.0.255 | bei Bedarf
eigene IP-Adresse | 172.16.0.1 | bei Bedarf
Domainname | lim.it1 | bei Bedarf
http-Ports | alle bis auf 443 | abhängig von den verwendeten Ports der untersuchten Geräte
ssl-Ports | 443 (https) | abhängig von den verwendeten Ports der untersuchten Geräte

Zudem können verschiedene Ansichtsparameter verändert werden. Bei manchen Änderungen ist ein Neustart erforderlich, bevor sie wirksam werden.

### Zertifikat

LiMiT1 wird aus Sicherheitsgründen ohne Standard-Zertifikat ausgeliefert. Vor der ersten Aufzeichnung muss daher ein individuelles Zertifikat erzeugt werden und dieses auf sämtlichen Geräten installiert werden, deren verschlüsselter Datenverkehr aufgezeichnet werden soll.



