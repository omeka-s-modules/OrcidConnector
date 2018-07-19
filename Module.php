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

        $this->installResourceTemplate();
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

        $api = $this->serviceLocator->get('Omeka\ApiManager');
        $identity = $this->serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
        $user = $view->get('user');
        $researcherResponse = $api->search('orcid_researchers', ['user_id' => $user->id()])->getContent();
        $researcher = empty($researcherResponse) ? false : $researcherResponse[0];
        $orcidId = $researcher->orcidId();
        $graph = $this->fetchOrcidData($orcidId);
        
        $orcidRdfHtml = $this->renderRdf($graph);
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
            'orcid_sample_client_id' => $globals->get('orcid_sample_client_id', ''),
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
        //$itemId = $item->id();
        
        //dig up ORCID iD based on the Item
        //$orcidResearcher = $api->search('OrcidResearcher', ['item_id' => $itemId])->response()[0];
        //$orcidId = $orcidResearcher->orcid_id();
       // $orcidRdf = $this->fetchOrcidData($orcidId);
        $orcidRdf = '';
        $html = $this->renderRdf($orcidRdf);
        echo $html;
    }
    
    protected function installResourceTemplate()
    {
        $api = $this->api();
        $this->preparePropertyMap();
        $personClass = $api->search('resource_classes', ['term' => 'foaf:Person'])->getContent();
        $templateJson = [
            'o:label' => 'Orcid Researcher', // @translate
            'o:resource_class' => ['o:id' => $personClass[0]->id()],
            'o:resource_template_property' => [
                'foaf:name' => [
                    'o:property' => [
                        'o:id' => $this->propertyMap['foaf:name'],
                    ],
                    'o:alternate_label' => 'Full name' // @translate
                ],
                'foaf:givenName' => [
                    'o:property' => [
                        'o:id' => $this->propertyMap['foaf:givenName']
                    ],
                    'o:alternate_label' => 'Given name' // @translate
                ],
                'foaf:familyName' => [
                    'o:property' => [
                        'o:id' => $this->propertyMap['foaf:familyName']
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
        $url = "https://orcid.org/$orcidId";
        $url = "https://sandbox.orcid.org/$orcidId";
        $request = new EasyRdf_Http_Client();
        $request->setUri($url);
        $request->setHeaders("Accept", "application/ld+json");
        $response = $request->request();
        $responseBody = $response->getBody();
        echo $orcidId;
        var_dump($responseBody);
        
        $graph = new EasyRdf_Graph();
        $graph->parse($responseBody, 'jsonld');
        return $graph;
    }
    
    protected function renderRdf($graph)
    {
        $html = 'RDF to html goes here.';
        $html .= $graph->dump();
        return $html;
    }
}
