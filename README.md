# sipgate

Modul für IP-Symcon ab Version 4.4

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)

## 1. Funktionsumfang

...

## 2. Voraussetzungen

 - IP-Symcon ab Version 4.4
 - sipgate Basic-Account, ggfs Freischaltung bestimmter Funktionen

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

In dem Konfigurationsdialog die Zugangsdaten des Ac℅ounts eintragen.

## 4. Funktionsreferenz

### zentrale Funktion

`Sipgate_SendSMS(integer $InstanzID, string Telno, string Message)`

## 5. Konfiguration:

### Variablen

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :-----------------------: | :-----:  | :----------: | :----------------------------------------------------------------------------------------------------------: |
| Benutzer                  | string   |              | sipgate-Benutzer |
| Passwort                  | string   |              | Passwort des Benutzers |

### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :------------------------------------------------: |
| Verbindungstest              | Testet den Account-Zugriff |

## 6. Anhang

GUIDs
- Modul: `{A110B4EA-FE52-4351-8922-B2B751179BAD}`
- Instanzen:
  - Sipgate: `{D8C71279-8E04-4466-8996-04B6B6CF2B1D}`
