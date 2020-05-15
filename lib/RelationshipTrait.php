<?php

namespace Meetings;

Trait RelationshipTrait
{
    private function getRelLink($slug)
    {
        return \PluginEngine::getLink('meetingplugin/api/' . $slug);
    }
}
