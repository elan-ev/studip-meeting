<?php

/*
 * Copyright (C) 2012 - Till Glöggler     <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * @author    tgloeggl@uos.de
 * @copyright (c) Authors
 */

require_once 'app/controllers/studip_controller.php';

use ElanEv\Driver\DriverFactory;
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\Join;
use ElanEv\Model\Driver;

/**
 * @property \MeetingPlugin         $plugin
 * @property \Flexi_TemplateFactory $templateFactory
 * @property bool                   $saved
 * @property array                  $errors
 * @property string                 $deleteAction
 */
class AdminController extends StudipController
{
    /**
     * @var ElanEv\Driver\DriverInterface
     */
    private $driver;

    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        $this->plugin = $GLOBALS['plugin'];
    }

    /**
     * {@inheritdoc}
     */
    function before_filter(&$action, &$args)
    {
        $this->validate_args($args, array('option', 'option'));

        parent::before_filter($action, $args);

        // set default layout
        $this->templateFactory = $GLOBALS['template_factory'];
        $layout = $this->templateFactory->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::addScript($this->plugin->getAssetsUrl().'/js/jquery.tablesorter.min.js');
        PageLayout::addScript($this->plugin->getAssetsUrl().'/js/meetings.js');
        PageLayout::addStylesheet($this->plugin->getAssetsUrl().'/css/meetings.css');
        PageLayout::setHelpKeyword('Basis.Meetings');

        Navigation::activateItem('/admin/config/meetings');

    }

    public function index_action()
    {
        PageLayout::setTitle(_('Meetings Administration'));
        $this->getHelpbarContent('main');

        $this->drivers = Driver::discover();
    }

    public function save_action()
    {
        if ($GLOBALS['perm']->have_perm('root')) {
            Config::get()->setValue('VC_CONFIG', '');

            foreach (Request::getArray('config') as $driver_name => $options) {
                $config_options = array();

                if (!isset($options['enable'])) {
                    $options['enable'] = '0';
                }

                foreach ($options as $name => $value) {
                    $config = new \ElanEv\Driver\ConfigOption($name);
                    $config->setValue($value);
                    $config_options[] = $config;
                }

                Driver::setConfigByDriver($driver_name, $config_options);
            }
        } else {
            throw new AccessDeniedException('You need to be root to perform this action!');
        }

        // TODO: FIXME -> set correct link main plugin class so there is no need for this hack
        $this->redirect(PluginEngine::getLink($this->plugin, array(), 'admin'));
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * H E L P E R   F U N C T I O N S * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    private function getHelpbarContent($id)
    {
        /** @var \Helpbar $helpBar */

        switch ($id) {

            case 'main':
                $helpText = _('Administrationsseite für das Plugin zur Durchführung und Verwaltung von Live-Online-Treffen, ***REMOVED***en und Videokonferenzen.');
                $helpBar = Helpbar::get();
                $helpBar->addPlainText('', $helpText);
                break;
        }
    }
}
