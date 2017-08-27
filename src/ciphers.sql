--------------------------------------------------------------------------------
--------------------------------------------------------------------------------
--
--     PROJECT: LiMiT1
--        FILE: ciphers.sql
--         SEE: https://github.com/ulkuehn/LiMiT1
--      AUTHOR: Ulrich Kuehn
--
--       USAGE: cat ciphers.sql | /usr/bin/mysql <dbname>
--
-- DESCRIPTION: collection of sql commands to fill database tables of cipher
--              information (key exchange, cipher suites)
--
--------------------------------------------------------------------------------
--------------------------------------------------------------------------------

-- Schlüsselaustauschverfahren
-- create table if not exists keyExchange
-- (
  -- id int unsigned primary key auto_increment  comment 'eindeutige id',
  -- shortName tinytext                          comment 'key exchange Kurzname',
  -- longName text                               comment 'key exchange Langtext',
  -- forwardSecrecy boolean                      comment 'key exchange forward secrecy?',
  -- secure boolean                              comment 'key exchange considered secure?'
-- )                                             comment 'Schlüsselaustauschverfahren';


insert into keyExchange set
shortName = "DH_anon",
longName = "Anonymes Diffie-Hellman ohne Signaturen",
forwardSecrecy = 0,
secure = 0
;

insert into keyExchange set
shortName = "DH_anon_EXPORT",
longName = "Anonymes Diffie-Hellman ohne Signaturen",
forwardSecrecy = 0,
secure = 0
;

insert into keyExchange set
shortName = "DH_DSS",
longName = "Diffie-Hellman mit Zertifikaten auf Basis von DSS (Digital Signature Standard)",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "DH_DSS_EXPORT",
longName = "Diffie-Hellman mit Zertifikaten auf Basis von DSS (Digital Signature Standard)",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "DHE_DSS",
longName = "Ephemeral Diffie-Hellman mit Zertifikaten auf Basis von DSS (Digital Signature Standard)",
forwardSecrecy = 1,
secure = 1
;

insert into keyExchange set
shortName = "DHE_DSS_EXPORT",
longName = "Ephemeral Diffie-Hellman mit Zertifikaten auf Basis von DSS (Digital Signature Standard)",
forwardSecrecy = 1,
secure = 1
;

insert into keyExchange set
shortName = "DHE_PSK",
longName = "Ephemeral Diffie-Hellman auf Basis von Pre-Shared Keys",
forwardSecrecy = 1,
secure = 1
;

insert into keyExchange set
shortName = "DHE_RSA",
longName = "Ephemeral Diffie-Hellman mit Signaturen auf Basis von Rivest/Shamir/Adleman",
forwardSecrecy = 1,
secure = 1
;

insert into keyExchange set
shortName = "DHE_RSA_EXPORT",
longName = "Ephemeral Diffie-Hellman mit Signaturen auf Basis von Rivest/Shamir/Adleman",
forwardSecrecy = 1,
secure = 1
;

insert into keyExchange set
shortName = "DH_RSA",
longName = "Diffie-Hellman mit Signaturen auf Basis von Rivest/Shamir/Adleman",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "DH_RSA_EXPORT",
longName = "Diffie-Hellman mit Signaturen auf Basis von Rivest/Shamir/Adleman",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "ECDH_anon",
longName = "Diffie-Hellman auf Basis elliptischer Kurven ohne Signaturen",
forwardSecrecy = 0,
secure = 0
;

insert into keyExchange set
shortName = "ECDH_ECDSA",
longName = "Diffie-Hellman auf Basis elliptischer Kurven mit Zertifikaten auf Basis von ECDSA (Elliptic Curve Digital Signature Algorithm)",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "ECDHE_ECDSA",
longName = "Ephemeral Diffie-Hellman auf Basis elliptischer Kurven mit Zertifikaten auf Basis von ECDSA (Elliptic Curve Digital Signature Algorithm)",
forwardSecrecy = 1,
secure = 1
;

insert into keyExchange set
shortName = "ECDHE_PSK",
longName = "Ephemeral Diffie-Hellman auf Basis auf Basis elliptischer Kurven unter Verwendung von Pre-Shared Keys",
forwardSecrecy = 1,
secure = 1
;

insert into keyExchange set
shortName = "ECDHE_RSA",
longName = "Ephemeral Diffie-Hellman auf Basis auf Basis elliptischer Kurven mit Signaturen auf Basis von Rivest/Shamir/Adleman",
forwardSecrecy = 1,
secure = 1
;

insert into keyExchange set
shortName = "ECDH_RSA",
longName = "Diffie-Hellman auf Basis auf Basis elliptischer Kurven mit Signaturen auf Basis von Rivest/Shamir/Adleman",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "KRB5",
longName = "Kerberos",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "KRB5_EXPORT",
longName = "Kerberos",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "NULL",
longName = "kein Schlüsselaustausch",
forwardSecrecy = 0,
secure = 0
;

insert into keyExchange set
shortName = "PSK",
longName = "Pre Shared Key",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "PSK_DHE",
longName = "Pre Shared Key",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "RSA",
longName = "Rivest/Shamir/Adleman",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "RSA_EXPORT",
longName = "Rivest/Shamir/Adleman",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "RSA_PSK",
longName = "Rivest/Shamir/Adleman unter Verwendung von Pre-Shared Keys",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "SRP_SHA",
longName = "Secure Remote Password (SRP)",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "SRP_SHA_DSS",
longName = "Secure Remote Password (SRP) mit Zertifikaten auf Basis von DSS (Digital Signature Standard)",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "SRP_SHA_RSA",
longName = "Secure Remote Password (SRP) mit Signaturen auf Basis von Rivest/Shamir/Adleman",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "SRP_SHA_RSA",
longName = "Secure Remote Password (SRP) mit Signaturen auf Basis von Rivest/Shamir/Adleman",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "GOSTR341094",
longName = "Gosudarstvennyy Standart (VKO GOST R 34.10-94)",
forwardSecrecy = 0,
secure = 1
;

insert into keyExchange set
shortName = "GOSTR341001",
longName = "Gosudarstvennyy Standart (VKO GOST R 34.10-2001)",
forwardSecrecy = 0,
secure = 1
;



-- Verschlüsselungsverfahren
-- create table if not exists cipher
-- (
  -- id int unsigned primary key auto_increment  comment 'eindeutige id',
  -- shortName tinytext                          comment 'cipher Kurzname',
  -- longName text                               comment 'cipher Langtext',
  -- streamCipher boolean                        comment 'stream cipher?',
  -- secure boolean                              comment 'cipher considered secure?',
  -- bits smallint unsigned                      comment 'cipher bit length'
-- )                                             comment 'Verschlüsselungsverfahren';


insert into cipher set
shortName = "3DES_EDE_CBC",
longName = "Triple DES (Encrypt Decrypt Encrypt) im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 1,
bits = 112
;

insert into cipher set
shortName = "AES_128_CBC",
longName = "Advanced Encryption Standard im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 1,
bits = 128
;

insert into cipher set
shortName = "AES_128_CCM",
longName = "Advanced Encryption Standard im CCM-Modus (Counter with CBC-MAC)",
streamCipher = 0,
secure = 1,
bits = 128
;

insert into cipher set
shortName = "AES_128_CCM_8",
longName = "Advanced Encryption Standard im CCM-Modus (Counter with CBC-MAC) mit 8 Byte Initialisierungsvektor",
streamCipher = 0,
secure = 1,
bits = 128
;

insert into cipher set
shortName = "AES_128_GCM",
longName = "Advanced Encryption Standard im Galois/Counter-Modus",
streamCipher = 0,
secure = 1,
bits = 128
;

insert into cipher set
shortName = "AES_256_CBC",
longName = "Advanced Encryption Standard im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 1,
bits = 256
;

insert into cipher set
shortName = "AES_256_CCM",
longName = "Advanced Encryption Standard im CCM-Modus (Counter with CBC-MAC)",
streamCipher = 0,
secure = 1,
bits = 256
;

insert into cipher set
shortName = "AES_256_CCM_8",
longName = "Advanced Encryption Standard im CCM-Modus (Counter with CBC-MAC) mit 8 Byte Initialisierungsvektor",
streamCipher = 0,
secure = 1,
bits = 256
;

insert into cipher set
shortName = "AES_256_GCM",
longName = "Advanced Encryption Standard im Galois/Counter-Modus",
streamCipher = 0,
secure = 1,
bits = 256
;

insert into cipher set
shortName = "ARIA_128_CBC",
longName = "Aria im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 1,
bits = 128
;

insert into cipher set
shortName = "ARIA_128_GCM",
longName = "Aria im im Galois/Counter-Modus",
streamCipher = 0,
secure = 1,
bits = 128
;

insert into cipher set
shortName = "ARIA_256_CBC",
longName = "Aria im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 1,
bits = 256
;

insert into cipher set
shortName = "ARIA_256_GCM",
longName = "Aria im Galois/Counter-Modus",
streamCipher = 0,
secure = 1,
bits = 256
;

insert into cipher set
shortName = "CAMELLIA_128_CBC",
longName = "Camellia im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 1,
bits = 128
;

insert into cipher set
shortName = "CAMELLIA_128_GCM",
longName = "Camellia im Galois/Counter-Modus",
streamCipher = 0,
secure = 1,
bits = 128
;

insert into cipher set
shortName = "CAMELLIA_256_CBC",
longName = "Camellia im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 1,
bits = 256
;

insert into cipher set
shortName = "CAMELLIA_256_GCM",
longName = "Camellia im Galois/Counter-Modus",
streamCipher = 0,
secure = 1,
bits = 256
;

insert into cipher set
shortName = "DES40_CBC",
longName = "Data Encryption Standard mit reduzierter Schlüssellänge im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 0,
bits = 40
;

insert into cipher set
shortName = "DES_CBC_40",
longName = "Data Encryption Standard mit reduzierter Schlüssellänge im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 0,
bits = 40
;

insert into cipher set
shortName = "DES_CBC",
longName = "Data Encryption Standard im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 0,
bits = 56
;

insert into cipher set
shortName = "IDEA_CBC",
longName = "International Data Encryption Algorithm im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 1,
bits = 128
;

insert into cipher set
shortName = "NULL",
longName = "keine",
streamCipher = 0,
secure = 0,
bits = 0
;

insert into cipher set
shortName = "RC2_CBC_40",
longName = "Rivest Cipher 2 mit reduzierter Schlüssellänge im Cipher-Block-Chaining-Modus",
streamCipher = 1,
secure = 0,
bits = 40
;

insert into cipher set
shortName = "RC4_128",
longName = "Rivest Cipher 4",
streamCipher = 1,
secure = 0,
bits = 128
;

insert into cipher set
shortName = "RC4_40",
longName = "Rivest Cipher 4 mit reduzierter Schlüssellänge",
streamCipher = 1,
secure = 0,
bits = 40
;

insert into cipher set
shortName = "SEED_CBC",
longName = "SEED im Cipher-Block-Chaining-Modus",
streamCipher = 0,
secure = 1,
bits = 128
;




-- Message-authentication-code-Verfahren
-- create table if not exists mac
-- (
  -- id int unsigned primary key auto_increment  comment 'eindeutige id',
  -- shortName tinytext                          comment 'Message authentication code Kurzname',
  -- longName text                               comment 'Message authentication code Langtext',
  -- secure boolean                              comment 'Message authentication code considered secure?',
  -- bits smallint unsigned                      comment 'Länge des Digest'
-- )                                             comment 'Message-authentication-code-Verfahren';

insert into mac set
shortName = "AEAD",
longName = "Authenticated Encryption with Associated Data",
secure = 1,
bits = 0
;

insert into mac set
shortName = "MD5",
longName = "Message-Digest Algorithm 5",
secure = 0,
bits = 128
;

insert into mac set
shortName = "NULL",
longName = "kein",
secure = 0,
bits = 0
;

insert into mac set
shortName = "SHA",
longName = "Secure Hash Algorithmus 1",
secure = 1,
bits = 160
;

insert into mac set
shortName = "SHA256",
longName = "Secure Hash Algorithmus 2",
secure = 1,
bits = 256
;

insert into mac set
shortName = "SHA384",
longName = "Secure Hash Algorithmus 2",
secure = 1,
bits = 384
;
