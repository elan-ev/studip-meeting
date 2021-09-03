
<html>
    <head>
       <meta charset="UTF-8">
        <title><?= _('Standardfolie-Intro') ?></title>
        <style>
            div#intro {
                letter-spacing: 2px;
            }
        </style>
    </head>
    <body>
        <div id="intro">
            <h1><?= htmlReady($texts['welcome']) ?></h1>
            <h3><?= htmlReady($texts['course_name'] . ' - ' . $texts['meeting_name']) ?></h3>
            <? if (isset($texts['date'])): ?>
                <h5><?= htmlReady($texts['date']) ?></h5>
            <? endif; ?>
        </div>
    </body>
</html>