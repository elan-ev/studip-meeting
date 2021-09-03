
<html>
    <head>
       <meta charset="UTF-8">
        <title><?= _('Standardfolie-News') ?></title>
        <style>
            div.news {
                letter-spacing: 1px;
            }
        </style>
    </head>
    <body>
        <div class="news">
            <h1><?= htmlReady($news_list['texts']['title']) ?>:</h1>
            <ul class="news-list">
                <? foreach ($news_list['news'] as $news): ?>
                    <li>
                        <strong><?= htmlReady($news['topic']) ?></strong>: <span>(<?= htmlReady(date("d.m.Y", $news['date'])) ?>)</span>
                        <div>
                            <?= formatReady($news['body']) ?>
                        </div>
                    </li>
                <? endforeach; ?>
            </ul>
        </div>
    </body>
</html>