<h4>
    <img style="vertical-align: middle; margin-right: 3px;" src="<?= Assets::url('images/ajax-indicator-black.svg') ?>" width="24" height="24" alt="waiting">
    <span><?= $_('Vorgang läuft. Bitte warten Sie einen Moment') ?>&hellip;</span>
</h4>
<script type="text/javascript">
    window.onload = () => {
        if (typeof window.page_available !== 'undefined') {
            console.log(window.page_available);
            if (window.page_available) {
                window.close();
            } else {
                setTimeout(() => {
                    window.open('<?= $return_url ?>', "_self");
                }, 1000);
            }
        }
    };
</script>