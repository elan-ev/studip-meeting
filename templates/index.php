<article class="studip">
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
                            <h1 onclick="window.open('<?= $item['meeting_join_url'] ?>', '_self');return false;">
                                <?= Icon::create('chat', 'inactive')->asImg(['class' => 'text-bottom']) ?>
                                <?= htmlReady($item['item_title']) ?>
                            </h1>
                            <nav>
                                <a href="<?= $item['meeting_join_url'] ?>" title="<?= htmlReady($texts['to_meeting']) ?>" target="_blank">
                                    <?= Icon::create('door-enter', 'clickable')->asImg(['class' => 'text-bottom']) ?>
                                </a>
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
                            <h1 onclick="window.open('<?= $item['meeting_join_url'] ?>', '_self');return false;">
                                <?= Icon::create('chat', 'inactive')->asImg(['class' => 'text-bottom']) ?>
                                <?= htmlReady($item['item_title']) ?>
                            </h1>
                            <nav>
                                <a href="<?= $item['meeting_join_url'] ?>" target="_blank">
                                    <?= Icon::create('door-enter', 'clickable')->asImg(['class' => 'text-bottom']) ?>
                                </a>
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
