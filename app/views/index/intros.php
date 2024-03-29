
<form action="<?= $controller->link_for('index/bulk_delete_intro') ?>" method="post" class="default">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default meeting-introductions">
        <caption>
            <?= $_('Informationstexte') ?>
        </caption>
        <colgroup>
            <col width="1%">
            <col width="20%">
            <col width="70%">
            <col width="9%">
        </colgroup>
        <thead>
            <tr>
                <th>
                    <? if (!empty($introductions)): ?>
                        <input type="checkbox" name="all" value="1" title="<?= $_('Alle auswählen') ?>"
                            data-proxyfor=":checkbox[name='indices[]']"
                            data-activates=".meeting-introductions button[name=bulk_delete]">
                    <? endif; ?>
                </th>
                <th scope="col"><?= $_('Titel') ?></th>
                <th scope="col"><?= $_('Text') ?></th>
                <th scope="col"><?= $_('Aktionen') ?></th>
            </tr>
        </thead>
        <tbody>
            <? if (empty($introductions)): ?>
                <tr class="empty">
                    <td colspan="4">
                        <?= $_('Keine Einträge vorhanden') ?>
                    </td>
                </tr>
            <? endif; ?>
            <? foreach ($introductions as $index => $introduction): ?>
                <tr>
                    <td style="text-align: center">
                        <input type="checkbox" data-proxiedby=":checkbox[name='all']" name="indices[]" value="<?= $index ?>">
                    </td>
                    <td><?= $introduction->title ? htmlReady($introduction->title) : '<i>' . $_('Einleitung') . '</i>' ?></td>
                    <td><?= formatReady($introduction->text) ?></td>
                    <td class="actions">
                        <a href="<?= $controller->link_for("index/edit_intro/{$index}") ?>" data-dialog="size=auto">
                            <?= Icon::create('edit')->asImg(['title' => $_('Einleitung bearbeiten')]) ?>
                        </a>
                        <a href="<?= $controller->link_for("index/delete_intro/{$index}") ?>">
                            <?= Icon::create('trash')->asImg([
                                'title'        => $_('Einleitung löschen'),
                                'data-confirm' => $_('Soll diese Einleitung wirklich gelöscht werden?'),
                            ]) ?>
                        </a>
                    </td>
                </tr>
            <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">
                    <?= _('Markierte Einträge') ?>
                    <?= Studip\Button::create($_('Löschen'), 'bulk_delete', [
                        'data-confirm' => $_('Sollen die Informationstexte wirklich gelöscht werden?'),
                        'disabled' => empty($introductions),
                        'title' => $_('Alle markierte Einträge löschen'),
                    ]) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
