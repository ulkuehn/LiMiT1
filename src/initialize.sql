--------------------------------------------------------------------------------
--------------------------------------------------------------------------------
--
--     PROJECT: LiMiT1
--        FILE: initialize.sql
--         SEE: https://github.com/ulkuehn/LiMiT1
--      AUTHOR: Ulrich Kuehn
--
--       USAGE: cat initialize.sql | /usr/bin/mysql <dbname>
--
-- DESCRIPTION: collection of sql commands to create all necessary
--              database tables
--
--------------------------------------------------------------------------------
--------------------------------------------------------------------------------


-- Whois-Informationen
create table if not exists whois
(
  id int unsigned primary key auto_increment  comment 'eindeutige id', 
  domain tinytext                             comment 'name der domain',
  whois text                                  comment 'whois-informationen',
  stand datetime                              comment 'zeitpunkt der whois-abfrage',
  okay boolean                                comment 'whois-Abfrage erfolgreich?'
)                                             comment 'Whois-Informationen';

-- Geräte
create table if not exists geraet
(
  id int unsigned primary key auto_increment  comment 'eindeutige id', 
  name text                                   comment 'name des geraets',
  stand datetime                              comment 'zeitpunkt der letzten aktualisierung'
)                                             comment 'Geräte';


-- Geräte-Eigenschaften
create table if not exists eigenschaft
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  geraet int unsigned                         comment 'verweis auf tabelle geraet',
  name text                                   comment 'name der eigenschaft, z.B. IMEI',
  wert text                                   comment 'wert der eigenschaft'
)                                             comment 'Geräte-Eigenschaften';


-- Hosts
create table if not exists host
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  ip int unsigned                             comment 'ip-adresse des hosts als aton-wert', 
  name tinytext                               comment 'dns-name',
  ipermittelt boolean                         comment 'ip-adresse ist aus hostname ermittelt',
  nameermittelt boolean                       comment 'name ist aus ip-adresse ermittelt'
)                                             comment 'Hosts';


-- Dateien
create table if not exists datei
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  aufzeichnung int                            comment 'id der zugehoerigen aufzeichnung',
  name text                                   comment 'Dateiname mit relativem Pfad',
  inhalt longblob                             comment 'Dateiinhalt (komprimiert)'
)                                             comment 'Dateien';


-- Aufzeichnungen
create table if not exists aufzeichnung
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  start datetime                              comment 'zeitpunkt des aufzeichnungsbeginns', 
  ende datetime                               comment 'zeitpunkt des aufzeichnungsendes',
  name tinytext                               comment 'kurze Bezeichnung',
  info text                                   comment 'laengere Beschreibung', 
  geraet int unsigned                         comment 'id des geraets, mit dem die aufzeichnung verknuepft wurde',
  ip int unsigned                             comment 'ip-adresse des geraets, das aufgezeichnet wird als aton-wert'
)                                             comment 'Aufzeichnungen';


-- Verbindungen
create table if not exists verbindung
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  nr int unsigned                             comment 'sequenznummer (bezogen auf die zugehoerige aufzeichnung)',
  aufzeichnung int unsigned                   comment 'id der zugehoerigen aufzeichnung', 
  zeit datetime                               comment 'zeitstempel',
  vonport smallint unsigned                   comment 'portnummer des clients', 
  anport smallint unsigned                    comment 'portnummer des servers', 
  ip int unsigned                             comment 'ip-adresse des hosts als aton-wert', 
  host int unsigned                           comment 'id des hosts, mit dem die verbindung hergestellt wurde',
  laenge int unsigned                         comment 'bytelaenge des uninterpretierten inhalts', 
  typ enum ('https','http','ssl','tcp','udp') comment 'Typ'
)                                             comment 'Verbindungen';

-- https-Verbindung
create table if not exists https
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  aufzeichnung int unsigned                   comment 'id der zugehoerigen aufzeichnung (redundant)', 
  verbindung int unsigned                     comment 'id der zugehoerigen verbindung',
  host int unsigned                           comment 'id des hosts',
  useragent text                              comment 'user-agent-string aus dem inhalt',
  sslversion tinytext                         comment 'SSL- bzw. TLS-Version',
  ciphersuite smallint unsigned               comment 'ID der Cipher Suite, siehe https://www.iana.org',
  effBits smallint unsigned                   comment 'effektiv verwendete Schlüssellänge',
  maxBits smallint unsigned                   comment 'maximal mögliche Schlüssellänge',
  zertifikat int unsigned                     comment 'id des Zertifikats, das diese Verbindung benutzt'
)                                             comment 'HTTPS-Verbindungen';

-- http-Verbindung
create table if not exists http
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  aufzeichnung int unsigned                   comment 'id der zugehoerigen aufzeichnung (redundant)', 
  verbindung int unsigned                     comment 'id der zugehoerigen verbindung',
  host int unsigned                           comment 'id des hosts',
  useragent text                              comment 'user-agent-string aus dem inhalt'
)                                             comment 'HTTP-Verbindungen';

-- ssl-Verbindung
create table if not exists ssltls
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  aufzeichnung int unsigned                   comment 'id der zugehoerigen aufzeichnung (redundant)', 
  verbindung int unsigned                     comment 'id der zugehoerigen verbindung',
  sslversion tinytext                         comment 'SSL- bzw. TLS-Version',
  ciphersuite smallint unsigned               comment 'ID der Cipher Suite, siehe https://www.iana.org',
  effBits smallint unsigned                   comment 'effektiv verwendete Schlüssellänge',
  maxBits smallint unsigned                   comment 'maximal mögliche Schlüssellänge',
  zertifikat int unsigned                     comment 'id des Zertifikats, das diese Verbindung benutzt'
)                                             comment 'SSL-Verbindungen';

-- tcp-Verbindung
-- udp-Verbindung
-- keine besonderen Felder erforderlich


-- HTTP Requests
create table if not exists request
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  verbindung int unsigned                     comment 'id der verbindung, die den request enthaelt',
  aufzeichnung int unsigned                   comment 'id der aufzeichnung, die den request enthaelt (redundant)',
  inhaltroh int unsigned                      comment 'id des uninterpretierten inhalts',
  inhalt int unsigned                         comment 'id des interpretierten inhalts',
  methode tinytext                            comment 'request-methode, z.B. get, post', 
  mime tinytext                               comment 'mime-typ, z.B. image/png', 
  mimeadd tinytext                            comment 'zusaetzliche mime-infos nach einem semikolon', 
  uri text                                    comment 'uri des requests', 
  version tinytext                            comment 'http-version des requests, z.B. http/1.1'
)                                             comment 'Requests';


-- HTTP Responses
create table if not exists response 
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  request int unsigned                        comment 'id des zugehoerigen requests', 
  verbindung int unsigned                     comment 'id der verbindung, die den response enthaelt (redundant)',
  aufzeichnung int unsigned                   comment 'id der aufzeichnung, die den response enthaelt (redundant)',
  inhaltroh int unsigned                      comment 'id des uninterpretierten inhalts',
  inhalt int unsigned                         comment 'id des interpretierten inhalts',
  mime tinytext                               comment 'mime-typ, z.B. image/png', 
  mimeadd tinytext                            comment 'zusaetzliche mime-infos nach einem semikolon', 
  version tinytext                            comment 'http-version, z.B. http/1.1',
  status smallint unsigned                    comment 'status-code', 
  statustext text                             comment 'status-text'
)                                             comment 'Responses';


-- HTTP Header eines Requests oder einer Response
create table if not exists header 
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  request int unsigned                        comment 'id des zugehoerigen requests', 
  verbindung int unsigned                     comment 'id der verbindung, die den header enthaelt (redundant)',
  aufzeichnung int unsigned                   comment 'id der aufzeichnung, die den header enthaelt (redundant)',
  response boolean                            comment 'true: der header gehoert zur response', 
  feld tinytext                               comment 'name des headers ohne doppelpunkt, z.B. Host', 
  wert text                                   comment 'inhalt des headers'
)                                             comment 'Header eines Requests oder einer Response';


-- Inhalt eines Requests oder einer Response
create table if not exists inhalt
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  typ enum ('request','requestroh','response','responseroh','tcp','udpsend','udprcv') comment 'typ',
  referenz int unsigned                       comment 'id des zugehoerigen elements (request, verbindung)',
  verbindung int unsigned                     comment 'id der verbindung, die den inhalt enthaelt (redundant)',
  aufzeichnung int unsigned                   comment 'id der aufzeichnung, die den inhalt enthaelt (redundant)',
  inhalt mediumblob                           comment 'Bytefolge'
)                                             comment 'Inhalt eines Requests oder einer Response';


-- Metadaten von Inhalten (Bilder, HTML etc.)
create table if not exists metadaten
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  request int unsigned                        comment 'id des zugehoerigen requests', 
  verbindung int unsigned                     comment 'id der verbindung, die den inhalt enthaelt (redundant)',
  aufzeichnung int unsigned                   comment 'id der aufzeichnung, die den inhalt enthaelt (redundant)',
  response boolean                            comment 'true: das metadatum gehoert zur response', 
  mime tinytext                               comment 'mime-typ, z.B. image/png', 
  feld text                                   comment 'beschreibung des metadatums (z.B. title)',
  wert text                                   comment 'wert des metadatums'
)                                             comment 'Metadaten von per HTTP uebertragenen Inhalten';


-- HTTP Cookies
create table if not exists cookie
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  site tinytext                               comment 'site (domain oder host), zu der das cookie gehört',
  name text                                   comment 'name des cookies'
)                                             comment 'Cookies';


-- Cookie-Empfang
create table if not exists setcookie 
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  request int unsigned                        comment 'id des zugehoerigen requests bzw. response', 
  cookie int unsigned                         comment 'id des zugehoerigen cookies', 
  verbindung int unsigned                     comment 'id der verbindung, die das setcookie enthaelt (redundant)',
  aufzeichnung int unsigned                   comment 'id der aufzeichnung, die das setcookie enthaelt (redundant)',
  wert text                                   comment 'wert des cookies',
  domain text                                 comment 'domain-angabe',
  path text                                   comment 'pfad-angabe', 
  secure boolean                              comment 'secure-flag', 
  httponly boolean                            comment 'httponly-flag', 
  comment text                                comment 'kommentar-feld', 
  expires datetime                            comment 'expires-angabe', 
  valid bigint                                comment 'berechnete speicherfrist des cookies'
)                                             comment 'Cookie-Empfang';


-- Cookie-Versand
create table if not exists sendcookie
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  request int unsigned                        comment 'id des zugehoerigen requests', 
  cookie int unsigned                         comment 'id des zugehoerigen cookies',
  verbindung int unsigned                     comment 'id der verbindung, die das sendcookie enthaelt (redundant)',
  aufzeichnung int unsigned                   comment 'id der aufzeichnung, die das sendcookie enthaelt (redundant)',
  wert text                                   comment 'wert des cookies'
)                                             comment 'Cookie-Versand';


-- Zertifikat
create table if not exists zertifikat
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  fingerprint tinytext                        comment 'SHA256-Fingerprint des Zertifikats',
  serial tinytext                             comment 'Seriennummer des Zertifikats',
  issuer tinytext                             comment 'Issuer-String',
  subject tinytext                            comment 'Subject-String',
  notbefore datetime                          comment 'Mindestgültigkeit (GMT)',
  notafter datetime                           comment 'Höchstgültigkeit (GMT)',
  names text                                  comment 'Liste von DNS-Namen des Zertifikats'
)                                             comment 'Zertifikat';

-- Cipher-Suites
create table if not exists cipherSuite
(
  id int unsigned primary key                 comment 'eindeutige Nummer nach https://www.iana.org/assignments/tls-parameters/tls-parameters.xhtml#tls-parameters-4',
  name tinytext                               comment 'offizieller Name',
  rfc smallint unsigned                       comment 'Nummer des definierenden RFC',
  dtls boolean                                comment 'Datagram Transport Layer Security geeignet?',
  keyExchange int unsigned                    comment 'id des Schlüsselaustauschverfahrens',
  cipher int unsigned                         comment 'id des Verschlüsselungsverfahrens',
  mac int unsigned                            comment 'id des Message-authentication-code-Verfahrens'
)                                             comment 'Cipher-Suites';

-- Schlüsselaustauschverfahren
create table if not exists keyExchange
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  shortName tinytext                          comment 'key exchange Kurzname',
  longName text                               comment 'key exchange Langtext',
  forwardSecrecy boolean                      comment 'key exchange forward secrecy?',
  secure boolean                              comment 'key exchange considered secure?'
)                                             comment 'Schlüsselaustauschverfahren';

-- Verschlüsselungsverfahren
create table if not exists cipher
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  shortName tinytext                          comment 'cipher Kurzname',
  longName text                               comment 'cipher Langtext',
  streamCipher boolean                        comment 'stream cipher?',
  secure boolean                              comment 'cipher considered secure?',
  bits smallint unsigned                      comment 'Schlüssellänge'
)                                             comment 'Verschlüsselungsverfahren';

-- Message-authentication-code-Verfahren
create table if not exists mac
(
  id int unsigned primary key auto_increment  comment 'eindeutige id',
  shortName tinytext                          comment 'Message authentication code Kurzname',
  longName text                               comment 'Message authentication code Langtext',
  secure boolean                              comment 'Message authentication code considered secure?',
  bits smallint unsigned                      comment 'Länge des Digest'
)                                             comment 'Message-authentication-code-Verfahren';


