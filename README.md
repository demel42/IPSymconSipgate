# IPSymconSipgate

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Dieses Modul benutzt die Sipgate-API V2 und stellt Funktionen für die folgende Punkte zur Verfügung:
 - Kontostand
 - Anrufhistorie abrufen
 - SMS verschicken<br>
Hierzu muss im sipgate die Funktion _SMS versenden_ aktivert werden. Die Funktion an sich ist (zur Zeit) kostenlos, es wird pro SMS bezahlt.<br>
Besonderheit: wird die SMS an ein Festnetzanschluss geschickt, ruft Sipgate diese Nummer an und liest den Text vor.
 - Status der Umleitung abrufen und einstellen

## 2. Voraussetzungen

 - IP-Symcon ab Version 6.0
 - Sipgate Basic-Account, ggfs Freischaltung bestimmter Funktionen

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ öffnen.

Den **Modulstore** öffnen und im Suchfeld nun `Sipgate-Client` eingeben, das Modul auswählen und auf _Installieren_ auswählen.
Alternativ kann das Modul auch über **ModulControl** (im Objektbaum innerhalb _Kern Instanzen_ die Instanz _Modules_) installiert werden,
als URL muss `https://github.com/demel42/IPSymconSipgate` angegeben werden.

### b. Einrichtung in IPS

In IP-Symcon nun unterhalb des Wurzelverzeichnisses die Funktion _Instanz hinzufügen_ auswählen, als Gerät _Sipgate Basic_ auswählen.

In dem Konfigurationsdialog siehe **Anmeldung bei Sipgate""

## 4. Funktionsreferenz

### zentrale Funktion

`boolean Sipgate_SendSMS(integer $InstanzID, string Telno, string Message)`<br>
Sendet eine SMS an die angegebene nUmmer. Die Länge der SMS wird ggfs auf 160 Zeichen verkürzt.
Der Rückgabewert ist __*true*__, wenn die SMS an Sipgate abgesendet werden konnte, eine Information, ob die SMS den Empfänger erreicht hat, gibt es (leider) nicht.

`string Sipgate_GetHistory(integer $InstanzID)`<br>
liefert einen String mit einer kodierte JSON-Struktur zurück mit den Daten der Anruf-Historie.<br>
Beispiel siehe Funktion _TestHistory()_ in _modul.php_.

`string Sipgate_GetCallList(integer $InstanzID)`<br>
liefert einen String mit einer kodierte JSON-Struktur zurück mit der Gesprächsliste.<br>
Beispiel siehe Funktion _ShowCallList()_ in _modul.php_.

`string Sipgate_GetForwardings(integer $InstanzID, string $deviceId)`<br>
liefert einen String mit einer kodierte JSON-Struktur mit den aktuellen Umleitungen des angegebenen Telefons.<br>
Beispiel siehe Funktion _ShowForwardings()_ in _modul.php_.

`boolean Sipgate_SetForwarding(string $destination, int $timeout, bool $active, string $deviceId)`<br>
setzt/löscht die Umleitung des angegebenen Telefons.
Beispiel siehe Funktion _TestForwarding()_ in _modul.php_.

## 5. Konfiguration:

### Variablen

| Eigenschaft            | Typ     | Standardwert | Beschreibung |
| :--------------------- | :------ | :----------- | :----------- |
| Aktualisiere Daten ... | integer | 24           | Aktualisierungsintervall, Angabe in Stunden |

### Schaltflächen

| Bezeichnung             | Beschreibung |
| :---------------------- | :----------- |
| Aktualisieren Daten     | führt eine sofortige Aktualisierung durch |
| Zugangsdaten überprüfen | Testet die Zugangsdaten und gibt Accout-Details aus |

### Variablenprofile

Es werden folgende Variablenprofile angelegt:

* Integer<br>
Sipgate.Currency

## 6. Anhang

GUIDs
- Modul: `{A110B4EA-FE52-4351-8922-B2B751179BAD}`
- Instanzen:
  - Sipgate: `{D8C71279-8E04-4466-8996-04B6B6CF2B1D}`

API-Dokumentation: https://api.sipgate.com/v2/doc bzw. https://developer.sipgate.io/rest-api/api-reference/

## 7. Versions-Historie

- 2.3.2 @ 29.04.2022 12:31
  - Überlagerung von Translate und Aufteilung von locale.json in 3 translation.json (Modul, libs und CommonStubs)

- 2.3.1 @ 26.04.2022 12:20
  - Korrektur: self::$IS_DEACTIVATED wieder IS_INACTIVE
  - IPS-Version ist nun minimal 6.0

- 2.3 @ 25.04.2022 15:36
  - Übersetzung vervollständigt
  - Implememtierung einer Update-Logik
  - diverse interne Änderungen

- 2.2.1 @ 13.04.2022 15:31
  - potentieller Namenskonflikt behoben (trait CommonStubs)
  - Aktualisierung von submodule CommonStubs

- 2.2 @ 25.03.2022 18:31
  - automatische Abfrage des Kontostands aktviert
  - libs/common.php -> submodule CommonStubs
  - Anzeige der Referenzen der Instanz

- 2.1 @ 10.02.2022 17:45
  - Aktion "SMS senden" hinzugefügt

- 2.0 @ 01.02.2022 10:58
  - Umstellung auf Anmeldung per OAuth

- 1.5 @ 18.12.2020 14:57
  - PHP_CS_FIXER_IGNORE_ENV=1 in github/workflows/style.yml eingefügt
  - LICENSE.md hinzugefügt

- 1.4 @ 30.12.2019 10:56
  - Anpassungen an IPS 5.3
    - Formular-Elemente: 'label' in 'caption' geändert

- 1.3 @ 10.10.2019 17:27
  - Anpassungen an IPS 5.2
    - IPS_SetVariableProfileValues(), IPS_SetVariableProfileDigits() nur bei INTEGER, FLOAT
    - Dokumentation-URL in module.json
  - Umstellung auf strict_types=1
  - Umstellung von StyleCI auf php-cs-fixer 

- 1.2 @ 28.04.2019 14:24
  - Dokumentation überarbeitet

- 1.1 @ 29.03.2019 16:19
  - SetValue() abgesichert

- 1.0 @ 20.03.2019 14:56
  - Anpassungen IPS 5, Abspaltung von Branch _ips_4.4_
