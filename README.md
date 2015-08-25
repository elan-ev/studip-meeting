Stud.IP Video Conference Plugin
===============================

[![Build Status](https://travis-ci.org/virtUOS/studip-bigbluebutton.svg?branch=master)](https://travis-ci.org/virtUOS/studip-bigbluebutton)

Ein Stud.IP-Plugin um Konferenzen aus einer Veranstaltung heraus starten zu kˆnnen.
Teilnehmer/innen des Kurses kˆnnen der Konferenz beitreten.

Einrichtung
-----------

### BigBlueButton

Entweder Sie setzen sich selbst einen Server auf (http://bigbluebutton.org) oder nehmen eines der Hosting-Angebote in Anspruch:
http://bigbluebutton.org/commercial-support/

### DfnVc (Adobe Connect)

Ist die Hochschule Mitglied der DFN und soll f¸r das Plugin "Meetings" (Videokonferenzen)
Adobe Connect ¸ber die DFN genutzt werden, m¸sste die Hochschule bei der DFN
per E-Mail (hotline@vc.dfn.de) anfragen, um eine Hochschul-Kennung zu erhalten.
Die DFN benˆtigt hierf¸r eine spezielle Funktionsemailadresse und die IP-Adresse
des Stud.IP-Servers der Hochschule.

Konfiguration
-------------

Als Benutzer mit root-Berechtigung muss unter "Admin" -> "System" -> "Meetings konfigurieren"
die Konfiguration der einzelnen Konnektoren vorgenommen werden.

Standardm‰ﬂig gibt es zwei Konnektoren:
* BigBlueButton
* DfnVC (Adobe Connect)
