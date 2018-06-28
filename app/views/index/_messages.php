<? if (!empty($flash['messages'])) foreach ($flash['messages'] as $type => $msgs) : ?>
    <? foreach ($msgs as $msg) : ?>
        <?= MessageBox::$type($msg) ?>
    <? endforeach; ?>
<? endforeach; ?>
