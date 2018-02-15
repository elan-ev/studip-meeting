<?php

/**
 * Updates the meeting plugin's help tours.
 *
 * @author Gerd Hoffmann <gerd.hoffmann@uni-oldenburg.de>
 */

class UpdateHelpTours extends Migration {

    /**
     * {@inheritdoc}
     */
    public function description() {
        return "Optimizes the meeting plugin's help tours in didactics and diction.";
    }

    /**
     * {@inheritdoc}
     */
    public function up() {

        //clear tour data

        $delete = "DELETE FROM help_tours WHERE
            tour_id = '4c41c9760a3248313236af202275109a' OR
            tour_id = '4c41c9760a3248313236af202275109b' OR
            tour_id = '4c41c9760a3248313236af202275109c' OR
            tour_id = '4c41c9760a3248313236af202275109d'
            ";

        DBManager::get()->exec($delete);

        //clear steps

        $delete = "DELETE FROM help_tour_steps WHERE
            tour_id = '4c41c9760a3248313236af202275109a' OR
            tour_id = '4c41c9760a3248313236af202275109b' OR
            tour_id = '4c41c9760a3248313236af202275109c' OR
            tour_id = '4c41c9760a3248313236af202275109d'
            ";

        DBManager::get()->exec($delete);


        // add optimized tour data
        $insert = "INSERT IGNORE INTO `help_tours` (`tour_id`, `name`, `description`, `type`, `roles`, `version`, `language`, `studip_version`, `installation_id`, `mkdate`) VALUES
            ('4c41c9760a3248313236af202275109a', 'Meetings anlegen', 'Die Tour erklärt das Anlegen neuer Meetings.', 'tour', 'dozent,admin,root', 1, 'de', '3.1', '', 1406709759),
            ('4c41c9760a3248313236af202275109b', 'Meetings nutzen', 'Die Tour erklärt die Nutzung und Verwaltung von Meetings in einer Veranstaltung.', 'tour', 'dozent,admin,root', 1, 'de', '3.1', '', 1406709759),
            ('4c41c9760a3248313236af202275109c', 'Meetings anpassen', 'Die Tour erklärt die Anpassung der Benutzungsoberfläche des Meeting-Plugins.', 'tour', 'dozent,admin,root', 1, 'de', '3.1', '', 1406709759),
            ('4c41c9760a3248313236af202275109d', 'Gesamtansicht', 'Die Tour erklärt die Nutzung und Verwaltung von Meetings im Profil eines Nutzers.', 'tour', 'dozent,admin,root', 1, 'de', '3.1', '', 1406709759);
            ";

        DBManager::get()->exec($insert);

        // add optimized steps
        $insert = "INSERT IGNORE INTO `help_tour_steps` (`tour_id`, `step`, `title`, `tip`, `orientation`, `interactive`, `css_selector`, `route`, `mkdate`) VALUES
            ('4c41c9760a3248313236af202275109a', 1, 'Meetings anlegen', 'Die Tour erklärt das Anlegen neuer Meetings in einer Veranstaltung.\r\n\r\nUm zum nächsten Schritt zu gelangen, klicken Sie bitte rechts unten auf \"Weiter\".', 'T', 0, '', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109a', 2, 'Erstellen oder verlinken', 'Zum Anlegen neuer Meetings gibt es zwei Möglichkeiten:\r\n-Meeting erstellen\r\n- Meeting verlinken', 'B', 0, '', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109a', 3, 'Meeting erstellen', 'Das Textfeld dient der Benennung des Meetings. Durch einen Klick auf \"Meeting erstellen\" wird ein neues Meeting erzeugt.', 'B', 0, '#layout_content DIV:eq(0)  TABLE:eq(1)  TFOOT:eq(0)  TR:eq(0)  TD:eq(0)  P:eq(0)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109a', 4, 'Meeting verlinken', 'Durch Auswahl eines Meetings und eines Klicks auf \"Meeting verlinken\" wird ein neues Meeting erzeugt, das auf ein vorhandenes Meeting verweist.', 'B', 0, 'SELECT[name=meeting_id]', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 1, 'Meetings nutzen', 'Die Tour erklärt die Nutzung und Verwaltung von Meetings in einer Veranstaltung.\r\n\r\nUm zum nächsten Schritt zu gelangen, klicken Sie bitte rechts unten auf \"Weiter\".', 'T', 0, '', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 2, 'Meetings', 'Zeigt eine Liste aller in dieser Veranstaltung angelegten Meetings an.', 'BL', 0, '#layout_content DIV:eq(0)  FORM:eq(0)  TABLE:eq(0)  CAPTION:eq(0)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 3, 'Meeting', 'Zeigt den Namen eines Meetings an. Mit einem Klick auf den Namen lässt sich das Meeting betreten.', 'BL', 0, '#layout_content DIV:eq(0)  FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(1)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 4, 'Aufzeichnung', 'Wenn mit Hilfe der Aktion \"Meeting bearbeiten\" die Internetadresse eines aufgezeichneten Meetings eingetragen wurde, erscheint an dieser Stelle ein Kamera-Icon. Ein Klick auf das Icon führt zur Aufzeichnung des Meetings.', 'BL', 0, '#layout_content DIV:eq(0)  FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(2)', 'plugins.php/meetingplugin', 1406709759),
             ('4c41c9760a3248313236af202275109b', 5, 'Freigeben', 'Die Teilnehmenden einer Veranstaltung können nur die Meetings sehen, die freigegeben sind.', 'BL', 0, '#layout_content DIV:eq(0)  FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(5)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 6, 'Aktion', 'Für angelegte Meetings stehen vier Aktionen bereit:\r\n\r\n- Informationen anzeigen\r\n- Meeting bearbeiten\r\n- Rechte der Teilnehmenden\r\n- Meeting löschen', 'BR', 0, '#layout_content DIV:eq(0)  FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 7, 'Informationen anzeigen', 'Zeigt eine Übersicht der Eigenschaften eines Meetings an. Ein erneuter Klick auf das Icon nimmt die Anzeige wieder zurück.', 'BR', 0, '#layout_content DIV:eq(0)  FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 8, 'Meeting bearbeiten', 'Erlaubt das Umbenennen eines Meetings sowie das Eintragen der Internetadresse zur Aufzeichnung eines Meetings.', 'BR', 0, '#layout_content DIV:eq(0)  FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 9, 'Rechte der Teilnehmenden', 'Durch das Setzen des Hakens erhalten alle Teilnehmenden VeranstalterInnen-Rechte innerhalb des Meetingraums. Sie können dann diesen virtuellen Raum mit denselben Rechten wie die Lehrenden der Veranstaltung gestalten.', 'BR', 0, '#layout_content DIV:eq(0)  FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 10, 'Meeting löschen', 'Löscht ein einzelnes Meeting oder den Link zu einem Meeting, falls das Meeting mit einer anderen Veranstaltung verlinkt ist.', 'BR', 0, '#layout_content DIV:eq(0)  FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 11, 'Informationen anzeigen', 'Zeigt eine Übersicht der Eigenschaften bei allen Meetings an.', 'BL', 0, '#layout-sidebar SECTION:eq(0)  DIV:eq(5)  DIV:eq(1)  UL:eq(0)  LI:eq(0)  A:eq(0)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109b', 12, 'Anpassen', 'Hier kann der Reitertext verändert und ein Einleitugstext ergänzt werden.', 'BL', 0, '#layout-sidebar SECTION:eq(0)  DIV:eq(8)  DIV:eq(1)  UL:eq(0)  LI:eq(0)  A:eq(0)', 'plugins.php/meetingplugin', 1406709759),
            ('4c41c9760a3248313236af202275109c', 1, 'Meetings anpassen', 'Die Tour zeigt, wie der Reitername verändert und ein Einleitungstext ergänzt werden kann.\r\n\r\nUm zum nächsten Schritt zu gelangen, klicken Sie bitte rechts unten auf \"Weiter\".', 'T', 0, '', 'plugins.php/meetingplugin/index/config', 1406709759),
            ('4c41c9760a3248313236af202275109c', 2, 'Reitername', 'Das Textfeld dient zur Benennung des Reiters. Durch einen Klick auf \"Speichern\" unten wird der Reitername übernommen.', 'BL', 0, '#vc_config_title', 'plugins.php/meetingplugin/index/config', 1406709759),
            ('4c41c9760a3248313236af202275109c', 3, 'Einleitungstext', 'Der Editor dient zur Eingabe eines Einleitungtextes. Durch einen Klick auf \"Speichern\" wird der Text übernommen.\r\n\r\nDer Einleitungstext erscheint in der Hauptansicht über der Liste der Meetings.', 'TL', 0, '#layout_content FORM:eq(0)  FIELDSET:eq(0)  FIELDSET:eq(1)  LABEL:eq(0)', 'plugins.php/meetingplugin/index/config', 1406709759),
            ('4c41c9760a3248313236af202275109c', 4, 'Meetings', 'Führt in die Hauptansicht von Meetings zurück.', 'BL', 0, '#layout-sidebar SECTION:eq(0)  DIV:eq(2)  DIV:eq(1)  UL:eq(0)  LI:eq(0)  A:eq(0)', 'plugins.php/meetingplugin/index/config', 1406709759),
            ('4c41c9760a3248313236af202275109d', 1, 'Gesamtansicht', 'Die Tour erklärt die Nutzung und Verwaltung Ihrer Meetings in der Gesamtansicht.\r\n\r\nUm zum nächsten Schritt zu gelangen, klicken Sie bitte rechts unten auf \"Weiter\".', 'T', 0, '', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 2, 'Anzeige', 'Wählen Sie die Sortierung Ihrer Meetings nach Namen oder Semester der Meetings.', 'BL', 0, '#layout-sidebar SECTION:eq(0)  DIV:eq(1)  DIV:eq(1)  UL:eq(0)  LI:eq(0)  A:eq(0)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 3, 'Meine Meetings', 'Zeigt eine Liste aller von Ihnen erstellten Meetings nach Namen oder Semestern sortiert.', 'BL', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  CAPTION:eq(0)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 4, 'Meeting', 'Zeigt den Namen eines Meetings an. Mit einem Klick auf den Namen lässt sich das Meeting betreten.', 'BL', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(1)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 5, 'Aufzeichnung', 'Wenn mit Hilfe der Aktion \"Meeting bearbeiten\" die Internetadresse eines aufgezeichneten Meetings eingetragen wurde, erscheint an dieser Stelle ein Kamera-Icon. Ein Klick auf das Icon führt zur Aufzeichnung des Meetings.', 'BL', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(2)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 6, 'Veranstaltung', 'Zeigt die Veranstaltung an, zu der das Meeting gehört.', 'BL', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(3)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 7, 'Freigeben', 'Die Teilnehmenden einer Veranstaltung können nur die Meetings sehen, die freigegeben sind.', 'BL', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(5)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 8, 'Aktion', 'Für angelegte Meetings stehen vier Aktionen bereit:\r\n\r\n- Informationen anzeigen\r\n- Meeting bearbeiten\r\n- Rechte der Teilnehmenden\r\n- Meeting löschen', 'BR', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 9, 'Informationen anzeigen', 'Zeigt eine Übersicht der Eigenschaften eines Meetings an. Ein erneuter Klick auf das Icon nimmt die Anzeige wieder zurück.', 'BR', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 10, 'Meeting bearbeiten', 'Erlaubt das Umbenennen eines Meetings sowie das Eintragen der Internetadresse zur Aufzeichnung eines Meetings.', 'BR', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 11, 'Rechte der Teilnehmenden', 'Durch das Setzen des Hakens erhalten alle Teilnehmenden VeranstalterInnen-Rechte innerhalb des Meetingraums. Sie können dann diesen virtuellen Raum mit denselben Rechten wie die Lehrenden der Veranstaltung gestalten.', 'BR', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin/index/my', 1406709759),
            ('4c41c9760a3248313236af202275109d', 12, 'Meeting löschen', 'Löscht ein einzelnes Meeting oder den Link zu einem Meeting, falls das Meeting mit einer anderen Veranstaltung verlinkt ist.', 'BR', 0, '#layout_content FORM:eq(0)  TABLE:eq(0)  THEAD:eq(0)  TR:eq(0)  TH:eq(6)', 'plugins.php/meetingplugin/index/my', 1406709759);
            ";

        DBManager::get()->exec($insert);
    }

    /**
     * {@inheritdoc}
     */
    public function down() {
        // processed in 015_add_help_tours.php
    }
}