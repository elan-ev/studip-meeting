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

use ElanEv\Driver\DriverFactory;
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\Join;
use ElanEv\Model\Driver;

/**
 * @property \MeetingPlugin         $plugin
 * @property \Flexi\Factory         $templateFactory
 * @property bool                   $saved
 * @property array                  $errors
 * @property string                 $deleteAction
 */
class AdminController extends MeetingsController
{
    /**
     * @var ElanEv\Driver\DriverInterface
     */
    private $driver;

    /**
     * Constructs the controller and provide translations methods.
     *
     * @param \Trails\Dispatcher $dispatcher
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct(\Trails\Dispatcher $dispatcher)
    {
        parent::__construct($dispatcher);

        // Localization
        $this->_ = function ($string) use ($dispatcher) {
            return call_user_func_array(
                [$this->plugin, '_'],
                func_get_args()
            );
        };

        $this->_n = function ($string0, $tring1, $n) use ($dispatcher) {
            return call_user_func_array(
                [$this->plugin, '_n'],
                func_get_args()
            );
        };
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws RuntimeException when method is not found
     */
    public function __call($method, $arguments)
    {
        $variables = method_exists($this, 'get_assigned_variables') ? $this->get_assigned_variables() : get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        return parent::__call($method, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    function before_filter(&$action, &$args)
    {
        $this->validate_args($args, array('option', 'option'));

        parent::before_filter($action, $args);

        // Permission check
        if ($GLOBALS['user']->perms !== 'root') {
            throw new AccessDeniedException();
        }

        // set default layout
        $this->templateFactory = $GLOBALS['template_factory'];
        $layout = $this->templateFactory->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setHelpKeyword('Basis.Meetings');

        Navigation::activateItem('/admin/config/meetings');

        $this->flash = Trails_Flash::instance();

    }

    public function index_action()
    {
        PageLayout::setTitle($this->_('Meetings Administration'));
        $this->getHelpbarContent('main');

        $this->drivers = Driver::discover();

        $this->setSidebar();
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * H E L P E R   F U N C T I O N S * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    private function getHelpbarContent($id)
    {
        /** @var \Helpbar $helpBar */

        switch ($id) {

            case 'main':
                $helpText = $this->_('Administrationsseite für das Plugin zur Durchführung und Verwaltung von Live-Online-Treffen, Veranstaltungen und Videokonferenzen.');
                $helpBar = Helpbar::get();
                $helpBar->addPlainText('', $helpText);
                break;
        }
    }

    /**
     * Adds the content to sidebar
     */
    protected function setSidebar()
    {
        $sidebar = Sidebar::get();

        $views = new \TemplateWidget(
            _('Ansichten'),
            $this->get_template_factory()->open('admin/view_widget')
        );
        $sidebar->addWidget($views)->addLayoutCSSClass('meeting-admin-view-widget');
    }
}
