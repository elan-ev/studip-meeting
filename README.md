Stud.IP Video Conference Plugin
===============================

[![Build Status](https://travis-ci.org/virtUOS/studip-bigbluebutton.svg?branch=master)](https://travis-ci.org/virtUOS/studip-bigbluebutton)

Ein Stud.IP-Plugin um BigBlueButton aus einer Veranstaltung heraus starten zu können.
Teilnehmer/innen des Kurses können der Konferenz beitreten.

Einrichtung
-----------

Ist die Hochschule Mitglied der DFN und soll für das Plugin "Meetings" (Videokonferenzen)
Adobe Connect über die DFN genutzt werden, müsste die Hochschule bei der DFN
per E-Mail (hotline@vc.dfn.de) anfragen, um eine Hochschul-Kennung zu erhalten.
Die DFN benötigt hierfür eine spezielle Funktionsemailadresse und die IP-Adresse
des Stud.IP-Servers der Hochschule.

Konfiguration
-------------

Als Benutzer mit root-Berechtigung müssen in der Konfiguration die folgenden
Optionen konfiguriert werden:

Option            | Erklärung
----------------- | ---------------------------------------------------------
`VC_DRIVER`       | zu verwendender Treiber (muss auf *dfnvc* gesetzt werden)
`DFN_VC_URL`      | API-Endpoint des DFN (wird vom DFN mitgeteilt)
`DFN_VC_LOGIN`    | die Funktionsemailadresse
`DFN_VC_PASSWORD` | Passwort (wird vom DFN mitgeteilt)
