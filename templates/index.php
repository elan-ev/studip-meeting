<? $uid = uniqid(); ?>
<article class="studip" id="meeting-widget-anchor-<?= $uid ?>">
    <? if (count($items)): ?>
        <? if (isset($items['current'])): ?>
            <article class="studip toggle open <?= ContentBoxHelper::classes('meeting-widget-current') ?>" id="meeting-widget-current">
                <header>
                    <h1>
                        <a href="<?= ContentBoxHelper::href('meeting-widget-current') ?>">
                            <?= htmlReady($texts['current']) ?>
                        </a>
                    </h1>
                </header>
                <? foreach ($items['current'] as $item): ?>
                    <article class="studip toggle">
                        <header>
                            <? if (isset($item['privacy_notice']) && $item['privacy_notice'] == true): ?>
                                <h1 onclick="<?= sprintf($texts['privacy_onclick'], $item['meeting_join_url'], '_self') ?>">
                                    <?= $meeting_icons['black-header']->asImg() ?>
                                    <?= htmlReady($item['item_title']) ?>
                                </h1>
                            <? else: ?>
                                <h1 onclick="window.open('<?= $item['meeting_join_url'] ?>', '_self');return false;">
                                    <?= $meeting_icons['black-header']->asImg() ?>
                                    <?= htmlReady($item['item_title']) ?>
                                </h1>
                            <? endif; ?>
                            <nav>
                                <? if (isset($item['privacy_notice']) && $item['privacy_notice'] == true): ?>
                                    <a href="#meeting-widget-anchor-<?= $uid ?>" title="<?= htmlReady($texts['to_meeting']) ?>" onclick="<?= sprintf($texts['privacy_onclick'], $item['meeting_join_url'], '_blank') ?>">
                                        <?= $meeting_icons['blue']->asImg(['class' => 'text-bottom']) ?>
                                    </a>
                                <? else: ?>
                                    <a href="<?= $item['meeting_join_url'] ?>" title="<?= htmlReady($texts['to_meeting']) ?>" target="_blank">
                                        <?= $meeting_icons['blue']->asImg(['class' => 'text-bottom']) ?>
                                    </a>
                                <? endif; ?>
                                <a href="<?= $item['meeting_course_url'] ?>" title="<?= htmlReady($texts['to_course']) ?>">
                                    <?= Icon::create('seminar', 'clickable')->asImg(['class' => 'text-bottom']) ?>
                                </a>
                            </nav>
                        </header>
                    </article>
                <? endforeach; ?>
            </article>
        <? endif; ?>
        <? if (isset($items['upcoming'])): ?>
            <article class="studip toggle <?= \ContentBoxHelper::classes('meeting-widget-upcoming') ?>" id="meeting-widget-upcoming">
                <header>
                    <h1>
                        <a href="<?= \ContentBoxHelper::href('meeting-widget-upcoming') ?>">
                            <?= htmlReady($texts['upcoming']) ?>
                        </a>
                    </h1>
                </header>
                <? foreach ($items['upcoming'] as $item): ?>
                    <article class="studip toggle">
                        <header>
                            <? if (isset($item['privacy_notice']) && $item['privacy_notice'] == true): ?>
                                <h1 onclick="<?= sprintf($texts['privacy_onclick'], $item['meeting_join_url'], '_self') ?>">
                                    <?= $meeting_icons['black-header']->asImg() ?>
                                    <?= htmlReady($item['item_title']) ?>
                                </h1>
                            <? else: ?>
                                <h1 onclick="window.open('<?= $item['meeting_join_url'] ?>', '_self');return false;">
                                    <?= $meeting_icons['black-header']->asImg() ?>
                                    <?= htmlReady($item['item_title']) ?>
                                </h1>
                            <? endif; ?>
                            <nav>
                                <? if (isset($item['privacy_notice']) && $item['privacy_notice'] == true): ?>
                                    <a href="#meeting-widget-anchor-<?= $uid ?>" title="<?= htmlReady($texts['to_meeting']) ?>" onclick="<?= sprintf($texts['privacy_onclick'], $item['meeting_join_url'], '_blank') ?>">
                                        <?= $meeting_icons['blue']->asImg(['class' => 'text-bottom']) ?>
                                    </a>
                                <? else: ?>
                                    <a href="<?= $item['meeting_join_url'] ?>" target="_blank">
                                        <?= $meeting_icons['blue']->asImg(['class' => 'text-bottom']) ?>
                                    </a>
                                <? endif; ?>
                                <a href="<?= $item['meeting_course_url'] ?>">
                                    <?= Icon::create('seminar', 'clickable')->asImg(['class' => 'text-bottom']) ?>
                                </a>
                            </nav>
                        </header>
                    </article>
                <? endforeach; ?>
            </article>
        <? endif; ?>
    <? else: ?>
        <section>
            <?= htmlReady($texts['empty']) ?>
        </section>
    <? endif; ?>
</article>
