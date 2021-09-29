<?php
/**
 * This is a sample file to help admins, how to use templates in order to align html texts in pdf.
 * The admin should have a basic knowledge of StudIP Flexi_Template class, in order to use the placeholders inside the template file!
 */

// Available Flexi_Template placeholders:
/*
$text['welcome']            : a welcome message with this text "Willkommen zur Videokonferenz"
$text['meeting_name']       : name of the meeting
$text['course_name']        : name of course/seminar
$text['course_news_title']  : the title for course news section
$text['studip_news_title']  : the title for StudIP news section

Example: <?= htmlReady($texts['welcome']) ?>
*/

/*
Array of the latest 3 StudipNews object for course:

$course_news = [
    0 => [
        'topic' => '' // news topic,
        'date' => '' // News date,
        'author' => '' // News Author,
        'body' => '' // News content
        ...
    ],
    ...
]

Example:
<? if (isset($course_news)): ?>
    <? foreach ($course_news as $news): ?>
        <li>
            <b><?= htmlReady($news['topic']) ?></b>: <small>(<?= htmlReady(date("d.m.Y", $news['date'])) ?>)</small>
            <div>
                <span style="font-size: medium;"><?= formatReady($news['body']) ?></span>
            </div>
        </li>
    <? endforeach; ?>
<? endif; ?>
*/

/*
Array of the latest 3 StudipNews object for Studip:

$studip_news = [
     0 => [
        'topic' => '' // news topic,
        'date' => '' // News date,
        'author' => '' // News Author,
        'body' => '' // News content
        ...
    ],
    ...
]

Example:
<? if (isset($studip_news)): ?>
    <? foreach ($studip_news as $news): ?>
        <li>
            <b><?= htmlReady($news['topic']) ?></b>: <small>(<?= htmlReady(date("d.m.Y", $news['date'])) ?>)</small>
            <div>
                <span style="font-size: medium;"><?= formatReady($news['body']) ?></span>
            </div>
        </li>
    <? endforeach; ?>
<? endif; ?>
*/
?>

<?php
    // https://tcpdf.org/examples/example_006/
    // create some HTML content
?>
<h1>HTML Example</h1>
Some special characters: &lt; € &euro; &#8364; &amp; è &egrave; &copy; &gt; \\slash \\\\double-slash \\\\\\triple-slash
<h2>List</h2>
List example:
<ol>
    <li><img src="images/logo_example.png" alt="test alt attribute" width="30" height="30" border="0" /> test image</li>
    <li><b>bold text</b></li>
    <li><i>italic text</i></li>
    <li><u>underlined text</u></li>
    <li><b>b<i>bi<u>biu</u>bi</i>b</b></li>
    <li><a href="http://www.tecnick.com" dir="ltr">link to http://www.tecnick.com</a></li>
    <li>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.<br />Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</li>
    <li>SUBLIST
        <ol>
            <li>row one
                <ul>
                    <li>sublist</li>
                </ul>
            </li>
            <li>row two</li>
        </ol>
    </li>
    <li><b>T</b>E<i>S</i><u>T</u> <del>line through</del></li>
    <li><font size="+3">font + 3</font></li>
    <li><small>small text</small> normal <small>small text</small> normal <sub>subscript</sub> normal <sup>superscript</sup> normal</li>
</ol>
<dl>
    <dt>Coffee</dt>
    <dd>Black hot drink</dd>
    <dt>Milk</dt>
    <dd>White cold drink</dd>
</dl>
<div style="text-align:center">IMAGES<br />
<img src="images/logo_example.png" alt="test alt attribute" width="100" height="100" border="0" /><img src="images/tcpdf_box.svg" alt="test alt attribute" width="100" height="100" border="0" /><img src="images/logo_example.jpg" alt="test alt attribute" width="100" height="100" border="0" />
</div>


<?php
    // https://tcpdf.org/examples/example_006/
    // output some RTL HTML content
?>
<div style="text-align:center">The words &#8220;<span dir="rtl">&#1502;&#1494;&#1500; [mazel] &#1496;&#1493;&#1489; [tov]</span>&#8221; mean &#8220;Congratulations!&#8221;</div>

<?php
    // https://tcpdf.org/examples/example_006/
    //some inline CSS
?>
<p>This is just an example of html code to demonstrate some supported CSS inline styles.
<span style="font-weight: bold;">bold text</span>
<span style="text-decoration: line-through;">line-trough</span>
<span style="text-decoration: underline line-through;">underline and line-trough</span>
<span style="color: rgb(0, 128, 64);">color</span>
<span style="background-color: rgb(255, 0, 0); color: rgb(255, 255, 255);">background color</span>
<span style="font-weight: bold;">bold</span>
<span style="font-size: xx-small;">xx-small</span>
<span style="font-size: x-small;">x-small</span>
<span style="font-size: small;">small</span>
<span style="font-size: medium;">medium</span>
<span style="font-size: large;">large</span>
<span style="font-size: x-large;">x-large</span>
<span style="font-size: xx-large;">xx-large</span>
</p>

<?php
    // https://tcpdf.org/examples/example_006/
    // table
?>
<h2>HTML TABLE:</h2>
<table border="1" cellspacing="3" cellpadding="4">
    <tr>
        <th>#</th>
        <th align="right">RIGHT align</th>
        <th align="left">LEFT align</th>
        <th>4A</th>
    </tr>
    <tr>
        <td>1</td>
        <td bgcolor="#cccccc" align="center" colspan="2">A1 ex<i>amp</i>le <a href="http://www.tcpdf.org">link</a> column span. One two tree four five six seven eight nine ten.<br />line after br<br /><small>small text</small> normal <sub>subscript</sub> normal <sup>superscript</sup> normal  bla bla bla bla bla bla bla bla bla bla bla bla bla bla bla bla bla bla bla bla bla bla bla<ol><li>first<ol><li>sublist</li><li>sublist</li></ol></li><li>second</li></ol><small color="#FF0000" bgcolor="#FFFF00">small small small small small small small small small small small small small small small small small small small small</small></td>
        <td>4B</td>
    </tr>
    <tr>
        <td><?= htmlReady($texts['welcome']) ?></td>
        <td bgcolor="#0000FF" color="yellow" align="center">A2 € &euro; &#8364; &amp; è &egrave;<br/>A2 € &euro; &#8364; &amp; è &egrave;</td>
        <td bgcolor="#FFFF00" align="left"><font color="#FF0000">Red</font> Yellow BG</td>
        <td>4C</td>
    </tr>
    <tr>
        <td>1A</td>
        <td rowspan="2" colspan="2" bgcolor="#FFFFCC">2AA<br />2AB<br />2AC</td>
        <td bgcolor="#FF0000">4D</td>
    </tr>
    <tr>
        <td>1B</td>
        <td>4E</td>
    </tr>
    <tr>
        <td>1C</td>
        <td>2C</td>
        <td>3C</td>
        <td>4F</td>
    </tr>
</table>

<?php
    // https://tcpdf.org/examples/example_006/
    // Image
?>
<h1>Image alignments on HTML table</h1>
<table cellpadding="1" cellspacing="1" border="1" style="text-align:center;">
<tr><td><img src="images/logo_example.png" border="0" height="41" width="41" /></td></tr>
<tr style="text-align:left;"><td><img src="images/logo_example.png" border="0" height="41" width="41" align="top" /></td></tr>
<tr style="text-align:center;"><td><img src="images/logo_example.png" border="0" height="41" width="41" align="middle" /></td></tr>
<tr style="text-align:right;"><td><img src="images/logo_example.png" border="0" height="41" width="41" align="bottom" /></td></tr>
<tr><td style="text-align:left;"><img src="images/logo_example.png" border="0" height="41" width="41" align="top" /></td></tr>
<tr><td style="text-align:center;"><img src="images/logo_example.png" border="0" height="41" width="41" align="middle" /></td></tr>
<tr><td style="text-align:right;"><img src="images/logo_example.png" border="0" height="41" width="41" align="bottom" /></td></tr>
</table>