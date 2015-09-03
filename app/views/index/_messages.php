<? if (!empty($messages)) foreach ($messages as $type => $msgs) : ?>
    <? foreach ($msgs as $msg) : ?>
        <?= MessageBox::$type($msg) ?>
    <? endforeach; ?>
<? endforeach; ?>
