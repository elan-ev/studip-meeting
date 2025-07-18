Stud.IP Video Conference Plugin
===============================

Ein Stud.IP-Plugin um Konferenzen aus einer Veranstaltung heraus starten zu können.
Teilnehmende des Kurses können der Konferenz beitreten.

Wichtige Information für Meetings Plugin v 2.83 - Stud.IP 6.x
------------------------------------------------
From version 2.83 the plugin is only available for Stud.IP 6.x.

Wichtige Information für Meetings Plugin v 2.82[.1] - Stud.IP 5.x
------------------------------------------------
The plugin sub-version from v2.82.1 splitted for Stud.IP 5.x.

Wichtige Information für Meetings Plugin ab 2.82
------------------------------------------------
Kompatibilität für BigBlueButton 2.7.8 ist ab Version 2.82 enthalten.

Wichtige Information für Meetings Plugin ab 2.81
------------------------------------------------
Kompatibilität für PHP 8 ist ab Version 2.81 enthalten.

Wichtige Information für Meetings Plugin ab 2.60
------------------------------------------------

Ab der Version 2.60 wird PHP 7.0 oder höher für die Verwendung des Plugins **vorausgesetzt**. Stud.IP Installationen, die PHP 5.6 verwenden, können leider nicht mehr unterstützt werden.

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
