<?php

use DI\Attribute\Inject;

/**
 * @SuppressWarnings(StaticAccess)
 */
class MeetingsController extends StudipController
{
    #[Inject]
    public StudIPPlugin $plugin;

    public function url_for($to = '')
    {
        $args = func_get_args();

        // find params
        $params = array();
        if (is_array(end($args))) {
            $params = array_pop($args);
        }

        // urlencode all but the first argument
        $args = array_map('urlencode', $args);
        $args[0] = $to;

        return PluginEngine::getURL($this->plugin, $params, join('/', $args));
    }
}
