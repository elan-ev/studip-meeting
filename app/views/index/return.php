<h4 id="loading">
    <img style="vertical-align: middle; margin-right: 3px;" src="<?= Assets::url('images/ajax-indicator-black.svg') ?>" width="24" height="24" alt="waiting">
    <span><?= $_('Vorgang läuft. Bitte warten Sie einen Moment') ?>&hellip;</span>
</h4>
<div id="duplicationInfo" style="display: none;">
    <?= MessageBox::info(_('Dies ist ein doppelter Tab. Bitte schließen Sie dieses Fenster manuell oder nutzen Sie den Button "Zurück zu den Meetings". Ihr Browser blockiert möglicherweise das automatische Schließen.')) ?>
    <?= \Studip\LinkButton::create(_('Zurück zu den Meetings'), $return_url)?>
</div>
<script type="text/javascript">
    window.onload = () => {
        if (typeof window.page_available !== 'undefined') {
            if (window.page_available) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('duplicationInfo').style.display = 'block';
            } else {
                setTimeout(() => {
                    window.open('<?= $return_url ?>', "_self");
                }, 1000);
            }
        }
    };
</script>
