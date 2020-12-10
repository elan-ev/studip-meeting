#!/bin/bash

PO=locale/en/LC_MESSAGES/meetings.po
POTPHP=locale/en/LC_MESSAGES/meetings_php.pot
POTJS=locale/en/LC_MESSAGES/meetings_js.pot
POT=locale/en/LC_MESSAGES/meetings.pot
MO=locale/en/LC_MESSAGES/meetings.mo

rm -f $POT
rm -f $POTPHP

find * \( -iname "*.php" -o -iname "*.ihtml" \) | xargs xgettext --from-code=UTF-8 --add-location=full --package-name=Meetings --language=PHP -o $POTPHP

msgcat $POTJS $POTPHP -o $POT
msgmerge $PO $POT -o $PO
msgfmt $PO --output-file=$MO
