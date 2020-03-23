<?php

namespace Meetings\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;

use Meetings\Models\Config;
use Meetings\Models\OCEndpoints;
use Meetings\Models\OCSeriesModel;

use Meetings\Models\REST\Config as RESTConfig;
use Meetings\Models\REST\ServicesClient;

use Meetings\Models\LTI\LtiLink;

use Meetings\Models\I18N as _;

class ConfigAdd extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        \SimpleOrMap::expireTableScheme();

        $json = $this->getRequestData($request);
        $config_id = 1;

        // store config in db
        $config = Config::find($config_id);

        if (!$config) {
            $config = new Config;
            $config->id = $config_id;
        }

        $config->config = $json['config'];
        $config->store();

        // check Configuration and load endpoints
        $message = null;

        // invalidate series-cache when editing configuration
        \StudipCacheFactory::getCache()->expire('oc_allseries');

        $service_url =  parse_url($config->config['url']);

        // check the selected url for validity
        if (!array_key_exists('scheme', $service_url)) {
            $message = [
                'type' => 'error',
                'text' => sprintf(
                    _('Ungültiges URL-Schema: "%s"'),
                    $config->config['url']
                )
            ];

            OCEndpoints::deleteBySql('config_id = ?', [$config_id]);
        } else {
            $service_host =
                $service_url['scheme'] .'://' .
                $service_url['host'] .
                (isset($service_url['port']) ? ':' . $service_url['port'] : '');

            try {
                $version = RESTConfig::getOCBaseVersion($config->id);

                OCEndpoints::deleteBySql('config_id = ?', [$config_id]);

                $config->config['version'] = $version;
                $config->store();

                OCEndpoints::setEndpoint($config_id, $service_host .'/services', 'services');

                $services_client = new ServicesClient($config_id);

                $comp = null;
                $comp = $services_client->getRESTComponents();
            } catch (AccessDeniedException $e) {
                OCEndpoints::removeEndpoint($config_id, 'services');

                $message = [
                    'type' => 'error',
                    'text' => sprintf(
                        $this->_('Fehlerhafte Zugangsdaten für die Meetings Installation mit der URL "%s". Überprüfen Sie bitte die eingebenen Daten.'),
                        $service_host
                    )
                ];

                $this->redirect('admin/config');
                return;
            }

            if ($comp) {
                $services = RESTConfig::retrieveRESTservices($comp, $service_url['scheme']);

                if (empty($services)) {
                    OCEndpoints::removeEndpoint($config_id, 'services');

                    $message = [
                        'type' => 'error',
                        'text' => sprintf(
                            $this->_('Es wurden keine Endpoints für die Meetings Installation mit der URL "%s" gefunden. '
                                . 'Überprüfen Sie bitte die eingebenen Daten, achten Sie dabei auch auf http vs https und '
                                . 'ob ihre Meetings-Installation https unterstützt.'),
                            $service_host
                        )
                    ];
                } else {

                    foreach($services as $service_url => $service_type) {
                        if (in_array(
                                strtolower($service_type),
                                $this->container['Meetings']['services']
                            ) !== false
                        ) {
                            OCEndpoints::setEndpoint($config_id, $service_url, $service_type);
                        } else {
                            unset($services[$service_url]);
                        }
                    }

                    $success_message[] = sprintf(
                        _('Die Meetings Installation "%s" wurde erfolgreich konfiguriert.'),
                        $service_host
                    );

                    $message = [
                        'type' => 'success',
                        'text' => implode('<br>', $success_message)
                    ];

                    $config->config['checked'] = true;
                    $config->store();
                }
            } else {
                //OCEndpoints::removeEndpoint($config_id, 'services');
                $message = [
                    'type' => 'error',
                    'text' => sprintf(
                        _('Es wurden keine Endpoints für die Meetings Installation mit der URL "%s" gefunden. Überprüfen Sie bitte die eingebenen Daten.'),
                        $service_host
                    )
                ];
            }
        }

        // return lti data to test lti connection
        $search_config = Config::getConfigForService('search', $config_id);
        $url = parse_url($search_config['service_url']);

        $search_url = $url['scheme'] . '://'. $url['host']
            . ($url['port'] ? ':' . $url['port'] : '') . '/lti';

        $lti_link = new LtiLink(
            $search_url,
            $config->config['ltikey'],
            $config->config['ltisecret']
        );

        $launch_data = $lti_link->getBasicLaunchData();
        $signature   = $lti_link->getLaunchSignature($launch_data);

        $launch_data['oauth_signature'] = $signature;

        $lti = [
            'launch_url'  => $lti_link->getLaunchURL(),
            'launch_data' => $launch_data
        ];

        // after updating the configuration, clear the cached series data
        OCSeriesModel::clearCachedSeriesData();
        #MeetingsLTI::generate_complete_acl_mapping();

        return $this->createResponse([
            'config' => $config->config->getArrayCopy(),
            'message'=> $message,
            'lti' => $lti
        ], $response);
    }
}
