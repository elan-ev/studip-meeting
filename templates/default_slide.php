
<html>
    <head>
       <meta charset="UTF-8">
        <title><?= _('Standardfolie') ?></title>
        <style>
            h1, h2, h3, h4 {
                letter-spacing: 1.5px;
            }
            div#intro {
                text-align: center;
                width: 100%;
            }
            div.news {
                letter-spacing: 1px;
            }
        </style>
    </head>
    <body>
        <div id="intro">
            <h3><?= htmlReady($texts['welcome']) ?></h3>
            <h4><?= htmlReady($texts['course_name'] . ' - ' . $texts['meeting_name']) ?></h4>
            <? if (isset($texts['date'])): ?>
                <h5><?= htmlReady($texts['date']) ?></h5>
            <? endif; ?>
        </div>
        <? if (isset($course_news)): ?>
            <h4><?= htmlReady($texts['course_news_title']) ?>:</h4>
            <ul class="news-list">
                <? foreach ($course_news as $news): ?>
                    <li>
                        <b><?= htmlReady($news['topic']) ?></b>: <small>(<?= htmlReady(date("d.m.Y", $news['date'])) ?>)</small>
                        <div>
                            <span style="font-size: medium;"><?= formatReady($news['body']) ?></span>
                        </div>
                    </li>
                <? endforeach; ?>
            </ul>
        <? endif; ?>
        <? if (isset($course_news) && isset($studip_news)): ?>
            <tcpdf method="AddPage" />
            <br pagebreak="true"/>
        <? endif; ?>
        <? if (isset($studip_news)): ?>
            <h4><?= htmlReady($texts['studip_news_title']) ?>:</h4>
            <ul class="news-list">
                <? foreach ($studip_news as $news): ?>
                    <li>
                        <b><?= htmlReady($news['topic']) ?></b>: <small>(<?= htmlReady(date("d.m.Y", $news['date'])) ?>)</small>
                        <div>
                            <span style="font-size: medium;"><?= formatReady($news['body']) ?></span>
                        </div>
                    </li>
                <? endforeach; ?>
            </ul>
        <? endif; ?>
    </body>
</html>