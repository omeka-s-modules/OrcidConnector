<?php

namespace OrcidConnector;

use Omeka\Module\AbstractModule;
use OrcidConnector\Form\ConfigForm;
use EasyRdf_Graph;
use EasyRdf_Http_Client;
use Zend\View\Renderer\PhpRenderer;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function init(ModuleManager $moduleManager)
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            'OrcidConnector\Controller\Index'
            );
    }

    public function getConfig()
    {
        return include __DIR__.'/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');

        $sql = "
                CREATE TABLE orcid_researcher (
                    id INT AUTO_INCREMENT NOT NULL,
                    person_item_id INT NOT NULL,
                    user_id INT NOT NULL,
                    orcid_id VARCHAR(255) NOT NULL,
                    access_token VARCHAR(255) NOT NULL,
                    refresh_tokens VARCHAR(255) DEFAULT NULL,
                    scope VARCHAR(255) DEFAULT NULL,
                    expiry_token VARCHAR(255) DEFAULT NULL,
                    UNIQUE INDEX UNIQ_DA7788AC30F1E1D7 (person_item_id),
                    UNIQUE INDEX UNIQ_DA7788ACA76ED395 (user_id),
                    PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
                    ALTER TABLE orcid_researcher ADD CONSTRAINT FK_DA7788AC30F1E1D7 FOREIGN KEY (person_item_id) REFERENCES item (id);
                    ALTER TABLE orcid_researcher ADD CONSTRAINT FK_DA7788ACA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);
               ";
        $connection->exec($sql);

        $this->installResourceTemplate($serviceLocator);
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $globals = $this->getServiceLocator()->get('Omeka\Settings');

        $sql = "
                DROP TABLE IF EXISTS orcid_researcher;
                ";
        $connection->exec($sql);

        $globals->delete('orcid_redirect_uri');
        $globals->delete('orcid_client_id');
        $globals->delete('orcid_client_secret');
        $globals->delete('orcid_sample_client_id');

    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\User',
            'view.edit.section_nav',
            [$this, 'orcidNav']
            );

        $sharedEventManager->attach(
            'Omeka\Controller\Admin\User',
            'view.edit.form.after',
            [$this, 'orcidForm']
            );

        $sharedEventManager->attach(
            'Omeka\Controller\Admin\User',
            'view.edit.before',
            [$this, 'orcidAssets']
            );
        /*
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\User',
            'view.edit.before',
            [$this, 'appendOrcidData']
            );
        */
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            [$this, 'appendOrcidData']
            );

        $sharedEventManager->attach(
            'Omeka\Controller\Item',
            'view.show.after',
            [$this, 'appendOrcidData']
            );
    }

    public function orcidAssets($event)
    {
        $view = $event->getTarget();
        $view->headLink()->appendStylesheet($view->assetUrl('css/orcid-connector.css', 'OrcidConnector'));
    }

    public function orcidNav($event)
    {
        $sectionNav = $event->getParam('section_nav');
        $sectionNav['orcid_data'] = 'ORCID data';
        $event->setParam('section_nav', $sectionNav);
        $view = $event->getTarget();
    }

    public function orcidForm($event)
    {
        $view = $event->getTarget();
        $globals = $this->getServiceLocator()->get('Omeka\Settings');

        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $identity = $this->serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
        $user = $view->get('user');
        $researcherResponse = $api->search('orcid_researchers', ['user_id' => $user->id()])->getContent();
        $researcher = empty($researcherResponse) ? false : $researcherResponse[0];
        if ($researcher) {
            $orcidId = $researcher->orcidId();
            $graph = $this->fetchOrcidData($orcidId);
            if ($graph) {
                //$orcidId = '0000-0003-0902-4386';
        
                $orcidRdfHtml = $this->renderRdf($graph, $orcidId);
                echo $view->partial('orcid-connector/admin/orcid',
                    [
                        'orcid_redirect_uri'  => $globals->get('orcid_redirect_uri', ''),
                        'orcid_client_id'     => $globals->get('orcid_client_id', ''),
                        'orcid_client_secret' => $globals->get('orcid_client_secret', ''),
                        'orcid_sample_client_id' => $globals->get('orcid_sample_client_id', ''),
                        'orcid_researcher'       => $researcher,
                        'user' => $user,
                        'identity' => $identity,
                        'orcidRdfHtml' => $orcidRdfHtml,
                    ]
                );
            } else {
                echo "A problem occured gathering data from ORCID. Please reload the page."; // @translate
            }
        } else {
            echo $view->partial('orcid-connector/admin/orcid',
                [
                    'orcid_redirect_uri'  => $globals->get('orcid_redirect_uri', ''),
                    'orcid_client_id'     => $globals->get('orcid_client_id', ''),
                    'orcid_client_secret' => $globals->get('orcid_client_secret', ''),
                    'orcid_sample_client_id' => $globals->get('orcid_sample_client_id', ''),
                    'user' => $user,
                    'identity' => $identity,
                ]
                );
            $propertyMap = $this->preparePropertyMap($api);
        }
    }

    /**
     * Get this module's configuration form.
     *
     * @param ViewModel $view
     * @return string
     */
    public function getConfigForm(PhpRenderer $renderer)
    {
        $formElementManager = $this->getServiceLocator()->get('FormElementManager');
        $globals = $this->getServiceLocator()->get('Omeka\Settings');
        $form = $formElementManager->get(ConfigForm::class);
        $form->setData([
            'orcid_redirect_uri'     => $globals->get('orcid_redirect_uri', ''),
            'orcid_client_id'        => $globals->get('orcid_client_id', ''),
            'orcid_client_secret'    => $globals->get('orcid_client_secret', ''),
        ]);
        $html = $renderer->formCollection($form);
        return $html;
    }

    /**
     * Handle this module's configuration form.
     *
     * @param AbstractController $controller
     * @return bool False if there was an error during handling
     */
    public function handleConfigForm(AbstractController $controller)
    {
        $data = $controller->params()->fromPost();
        $globalSettings = $this->getServiceLocator()->get('Omeka\Settings');
        if (isset($data['orcid_redirect_uri'])) {
            $globalSettings->set('orcid_redirect_uri', $data['orcid_redirect_uri']);
        }

        if (isset($data['orcid_client_id'])) {
            $globalSettings->set('orcid_client_id', $data['orcid_client_id']);
        }

        if (isset($data['orcid_client_secret'])) {
            $globalSettings->set('orcid_client_secret', $data['orcid_client_secret']);
        }

        if (isset($data['orcid_sample_client_id'])) {
            $globalSettings->set('orcid_sample_client_id', $data['orcid_sample_client_id']);
        }
    }

    public function appendOrcidData($event)
    {
        $view = $event->getTarget();
        $api = $this->serviceLocator->get('Omeka\ApiManager');
        $item = $view->get('item');
        $itemId = $item->id();

        // dig up ORCID iD based on the Item
        $orcidResearcher = $api->search('orcid_researchers', ['person_item_id' => $itemId])->getContent()[0];
        $orcidId = $orcidResearcher->orcidId();
        $orcidGraph = $this->fetchOrcidData($orcidId);
        $html = $this->renderRdf($orcidGraph, $orcidId);
        echo $html;
    }

    protected function installResourceTemplate($serviceLocator)
    {
        $api = $serviceLocator->get('Omeka\ApiManager');
        $propertyMap = $this->preparePropertyMap($api);
        $personClass = $api->search('resource_classes', ['term' => 'foaf:Person'])->getContent();
        $templateJson = [
            'o:label' => 'Orcid Researcher', // @translate
            'o:resource_class' => ['o:id' => $personClass[0]->id()],
            'o:resource_template_property' => [
                'foaf:name' => [
                    'o:property' => [
                        'o:id' => $propertyMap['foaf:name'],
                    ],
                    'o:alternate_label' => 'Full name' // @translate
                ],
                'foaf:givenName' => [
                    'o:property' => [
                        'o:id' => $propertyMap['foaf:givenName']
                    ],
                    'o:alternate_label' => 'Given name' // @translate
                ],
                'foaf:familyName' => [
                    'o:property' => [
                        'o:id' => $propertyMap['foaf:familyName']
                    ],
                    'o:alternate_label' => 'Family name' // @translate
                ],
            ]
        ];
        $response = $api->create('resource_templates', $templateJson);
    }
    
    protected function fetchOrcidData($orcidId)
    {
        // request setup adapted from 
        // https://groups.google.com/d/msg/easyrdf/jLcGkfZ9gzs/p6pvgKxoJlYJ
        $orcidId = '0000-0003-0902-4386';
        $uri = "http://orcid.org/$orcidId";
        //$uri = "https://sandbox.orcid.org/$orcidId";
        $request = new EasyRdf_Http_Client();
        $request->setUri($uri);
        $request->setHeaders("Accept", "application/ld+json");
        try {
            $response = $request->request();
            $responseBody = $response->getBody();
            $graph = new EasyRdf_Graph();
            // @TODO network conditions make the parsing of schema.org fluctuate
            // partly, I guess, because it's so big. need error handling and/or
            // longer response time allowance
            $graph->parse($responseBody, 'jsonld');
            return $graph;
        } catch (\EasyRdf_Exception $e) {
            return false;
        }
    }
    
    protected function renderRdf($graph, $orcidId)
    {
        // Grab only the desired data. Sad that it's hard-coded, but everything is
        // hard to manage.
        $orcidId = '0000-0003-0902-4386';
        $uri = "http://orcid.org/$orcidId";
        //$uri = "http://sandbox.orcid.org/$orcidId";
        $reverses = $graph->reversePropertyUris($uri);
        $directs = $graph->propertyUris($uri);

        $propertyValuesToRender = [
            'http://schema.org/creator' =>
                'Creator', // @translate
            'http://schema.org/funder'  =>
                'Funded by', // @translate
            'http://schema.org/affiliation'  =>
                'Affiliation', // @translate
            'http://schema.org/alumniOf'  =>
                'Alumni of', // @translate
                
        ];
        $html = '';

        foreach ($directs as $directProperty) {
            if (array_key_exists($directProperty, $propertyValuesToRender)) {
                $html .= "<div class='property'>";
                $html .= "<h4>" . $propertyValuesToRender[$directProperty] . "</h4>";
                $directResources = $graph->all("$uri", "<$directProperty>");
                foreach ($directResources as $directResource) {
                    $html .= "<div class='values'>" . $directResource->getLiteral("schema:name")->getValue() . "</div>";
                }
                $html .= "</div>";
            }
        }

        
        // It is annoying that Easy(!)Rdf uses "reverse", from JsonLD. A real SPARQL query would have been
        // so much easier.

        
        foreach ($reverses as $property) {
            if (array_key_exists($property, $propertyValuesToRender)) {
                $reverseResources = $graph->resourcesMatching("$property");
                $html .= "<div class='property'>";
                $html .= "<h4>" . $propertyValuesToRender[$property] . "</h4>";
                foreach ($reverseResources as $reverseResource) {
                    $html .= "<div class='values'>" . $reverseResource->getLiteral("schema:name")->getValue() . "</div>";
                }
                $html .= "</div>";
            }
        }
        return $html;
    }
    
    protected function preparePropertyMap($api)
    {
        $propertyMap = [];
        $propertyMap['foaf:name'] = $api->search('properties',
            ['term' => 'foaf:name'])
            ->getContent()[0]->id();
        $propertyMap['foaf:givenName'] = $api->search('properties',
            ['term' => 'foaf:givenName'])
            ->getContent()[0]->id();
        $propertyMap['foaf:familyName'] = $api->search('properties',
            ['term' => 'foaf:familyName'])
            ->getContent()[0]->id();
        return $propertyMap;
    }
}
