Stud.IP Video Conference Plugin
===============================

Ein Stud.IP-Plugin um Konferenzen aus einer Veranstaltung heraus starten zu können.
Teilnehmer/innen des Kurses können der Konferenz beitreten.

Einrichtung
-----------

### BigBlueButton

Entweder Sie setzen sich selbst einen Server auf (http://bigbluebutton.org) oder nehmen eines der Hosting-Angebote in Anspruch:
http://bigbluebutton.org/commercial-support/

### DfnVc (Adobe Connect)

Ist die Hochschule Mitglied der DFN und soll für das Plugin "Meetings" (Videokonferenzen)
Adobe Connect über die DFN genutzt werden, müsste die Hochschule bei der DFN
per E-Mail (hotline@vc.dfn.de) anfragen, um eine Hochschul-Kennung zu erhalten.
Die DFN benötigt hierfür eine spezielle Funktionsemailadresse und die IP-Adresse
des Stud.IP-Servers der Hochschule.

Konfiguration
-------------

Als Benutzer mit root-Berechtigung muss unter "Admin" -> "System" -> "Meetings konfigurieren"
die Konfiguration der einzelnen Konnektoren vorgenommen werden.

Standardmäßig gibt es zwei Konnektoren:
* BigBlueButton
* DfnVC (Adobe Connect)

Erstellen einer eigenen Pluginversion
-------------------------------------

Um ein installierbares Zip zu erstellen, reicht es den Quellcode herunterzuladen und `npm run zip` aufzurufen.
