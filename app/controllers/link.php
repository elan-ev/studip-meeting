<?php

use ElanEv\Driver\DriverFactory;
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\Driver;
use ElanEv\Model\InvitationsLink;

class LinkController extends PluginController
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        // Localization
        $this->_ = function ($string) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_'],
                func_get_args()
            );
        };

        $this->_n = function ($string0, $tring1, $n) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_n'],
                func_get_args()
            );
        };

    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws RuntimeException when method is not found
     */
    public function __call($method, $arguments)
    {
        $variables = get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        throw new RuntimeException("Method {$method} does not exist");
    }

    public function index_action($link_hex)
    {
        PageLayout::setTitle($this->_('Stud.IP Meeting'));
        $this->invitations_link = InvitationsLink::findOneBySQL('hex = ?', [$link_hex]);

        if(!$this->invitations_link) {
            throw new Exception($this->_('Das gesuchte Meeting existiert nicht mehr!'));
        }
        $widget = new SidebarWidget();
        $widget->setTitle($this->_('Meeting-Name'));
        $widget->addElement(
            new WidgetElement(htmlReady($this->invitations_link->meeting->name))
        );
        Sidebar::Get()->addWidget($widget);
    }

    public function join_meeting_action($link_hex)
    {
        $invitations_link = InvitationsLink::findOneBySQL('hex = ?', [$link_hex]);
        if(!$invitations_link) {
            throw new Exception($this->_('Das gesuchte Meeting existiert nicht mehr!'));
        }
        $name = trim(Request::get('name'));
        if (!$name) {
            $name = $invitations_link->default_name;
        }
        $meeting = $invitations_link->meeting;
        $driver_factory = new DriverFactory(Driver::getConfig());
        $driver = $driver_factory->getDriver($meeting->driver, $meeting->server_index);
        $joinParameters = new JoinParameters();
        $joinParameters->setMeetingId($meeting->id);
        $joinParameters->setIdentifier($meeting->identifier);
        $joinParameters->setRemoteId($meeting->remote_id);
        $joinParameters->setPassword($meeting->attendee_password);
        $joinParameters->setHasModerationPermissions(false);
        $joinParameters->setUsername('guest');
        $joinParameters->setFirstName($name);
        $join_url = $driver->getJoinMeetingUrl($joinParameters);
        header('Status: 301 Moved Permanently', false, 301);
        header('Location:' . $join_url);
        die;
    }
}