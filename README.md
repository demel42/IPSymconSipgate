# IPSymconSipgate

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-4.4+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-0.9-blue.svg)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![StyleCI](https://github.styleci.io/repos/135898954/shield?branch=master)](https://github.styleci.io/repos/135898954)

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

 - Kontostand

 - Anrufhistorie abrufen
 
 - SMS verschicken<br>
 Hierzu muss im sipgate die Funktion _SMS versenden_ aktivert werden. Die Funktion an sich ist (zur Zeit) kostenlos, es wird pro SMS bezahlt.<br>
Besonderheit: wird die SMS an ein Festnetzanschluss geschickt, ruft sipgate diese Nummer an und liest den Text vor.

 - Status der Umleitung abrufen und einstellen

 - Status von DND abrufen und einstellen

 - Status der Benachrichtungen abrufen und einstellen

 - Anruf einleiten

## 2. Voraussetzungen

 - IP-Symcon ab Version 4.4
 - Sipgate Basic-Account, ggfs Freischaltung bestimmter Funktionen

## 3. Installation

### a. Laden des Moduls

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconSipgate.git`

und mit _OK_ bestätigen.

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### b. Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und Hersteller _Sipgate_ und als Gerät _Sipgate_ auswählen.

In dem Konfigurationsdialog die Zugangsdaten des Accounts eintragen.

## 4. Funktionsreferenz

### zentrale Funktion

`boolean Sipgate_SendSMS(integer $InstanzID, string Telno, string Message)`
<br>
Sendet eine SMS an die angegebene nUmmer. Die Länge der SMS wird ggfs auf 160 Zeichen verkürzt.
Der Rückgabewert ist __*true*__, wenn die SMS an Sipgate abgesendet werden konnte, eine Information, ob die SMS den Empfänger erreicht hat, gibt es (leider) nicht.

`boolean Sipgate_GetHistory(integer $InstanzID)`
<br>
liefert eine JSON-Struktur zurück mit den Daten der Anruf-Historie, Beispiel siehe Funktion _TestHistory()_ in _modul.php_.

## 5. Konfiguration:

### Variablen

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :-----------------------: | :-----:  | :----------: | :-------------------------------------------: |
| Benutzer                  | string   |              | sipgate-Benutzer |
| Passwort                  | string   |              | Passwort des Benutzers |

### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :-------------------------------------------------: |
| Zugangsdaten überprüfen      | Testet die Zugangsdaten und gibt Accout-Details aus |
| SMS testen                   | SMS-Funktion testen |
| Anruf-Historie abrufen       | Anruf-Historie abrufen und ausgeben |

## 6. Anhang

GUIDs
- Modul: `{A110B4EA-FE52-4351-8922-B2B751179BAD}`
- Instanzen:
  - Sipgate: `{D8C71279-8E04-4466-8996-04B6B6CF2B1D}`

API-Dokumentation: https://api.sipgate.com/v2/doc bzw. https://developer.sipgate.io/rest-api/api-reference/

- 1.0 @ 03.06.2018 14:01<br>
  Vorab-Version
