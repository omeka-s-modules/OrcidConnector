<?php

namespace OrcidConnector\Controller;

use Orcid\Oauth;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use OrcidConnector\Entity\OrcidResearcher;

class IndexController extends AbstractActionController
{
    protected $orcidClientId;

    protected $orcidClientSecret;

    protected $orcidRedirectUri;

    protected $propertyMap;

    public function authenticateAction()
    {
        //$api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $view = new ViewModel;
        $code = $this->params()->fromQuery('code', false);
        if ($code) {
            $oauth = new Oauth;
// for dev only
$oauth->useSandboxEnvironment();
//
            $oauth->setClientId($this->orcidClientId);
            $oauth->setClientSecret($this->orcidClientSecret);
            $oauth->setRedirectUri($this->orcidRedirectUri);
            $oauth->authenticate($code);
// dev/testing
$serializedOauth = serialize($oauth);
file_put_contents('/var/www/html/omekas/modules/OrcidConnector/dev/oauth.php', $serializedOauth);
$profile = $oauth->getProfile();

$serializedProfile = serialize($profile);
file_put_contents('/var/www/html/omekas/modules/OrcidConnector/dev/profile.php', $serializedProfile);



$view->setVariable('profile', $profile);
$view->setVariable('oauth', $oauth);
//
            $view->setVariable('code', $code);
        } else {
            $serializedOauth = file_get_contents('/var/www/html/omekas/modules/OrcidConnector/dev/oauth.php');
            $oauth = unserialize($serializedOauth);
            $serializedProfile = file_get_contents('/var/www/html/omekas/modules/OrcidConnector/dev/profile.php');
            $profile = unserialize($serializedProfile);
            $view->setVariable('profile', $profile);
            $view->setVariable('oauth', $oauth);
            $orcidResearcherJson = [
                'orcid_id'       => $oauth->getOrcid(),
                'person_item'    => null,
                'o:user'        => ['o:id' => $this->identity()->getId()],
                'access_token'   => $oauth->getAccessToken(),
            ];

            $response = $this->api()->create('orcid_researchers', $orcidResearcherJson);
            $this->preparePropertyMap();
            $itemJson = $this->buildItemJson($oauth, $profile);
            $view->setVariable('itemJson', $itemJson);
            $this->api()->create('items', $itemJson);
            
            $this->installResourceTemplate();
        }
        return $view;
    }

    public function setOrcidClientId($id)
    {
        $this->orcidClientId = $id;
    }

    public function setOrcidClientSecret($secret)
    {
        $this->orcidClientSecret = $secret;
    }

    public function setOrcidRedirectUri($uri)
    {
        $this->orcidRedirectUri = $uri;
    }

    protected function buildItemJson($oauth, $profile)
    {
        $api = $this->api();
        $personClass = $api->search('resource_classes', ['term' => 'foaf:Person'])->getContent();
        
        $itemJson = ['o:resource_class' => ['o:id' => $personClass[0]->id()],
                     'foaf:givenName' => [['property_id' => $this->propertyMap['foaf:givenName'],
                                          '@value' => $profile->person->name->{'given-names'}->value,
                                          'type' => 'literal'
                                         ]],
                     'foaf:familyName' => [['property_id' => $this->propertyMap['foaf:familyName'],
                                           '@value' => $profile->person->name->{'family-name'}->value,
                                           'type' => 'literal'
                                          ]],
                     'foaf:name'       => [['property_id' => $this->propertyMap['foaf:name'],
                                            '@value' => $profile->person->name->{'given-names'}->value . ' ' . $profile->person->name->{'family-name'}->value,
                                            'type' => 'literal'
                                          ]],
          ];
        return $itemJson;
    }

    protected function preparePropertyMap()
    {
        $api = $this->api();
        $this->propertyMap = [
            'dcterms:description'   => $api->search('properties', ['term' => 'dcterms:description'])->getContent()[0]->id(),
            'foaf:givenName'        => $api->search('properties', ['term' => 'foaf:givenName'])->getContent()[0]->id(),
            'foaf:familyName'       => $api->search('properties', ['term' => 'foaf:familyName'])->getContent()[0]->id(),
            'foaf:name'             => $api->search('properties', ['term' => 'foaf:name'])->getContent()[0]->id(),
        ];
    }
    //@todo move this to Module.php when it works
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
        print_r($templateJson);
        $response = $api->create('resource_templates', $templateJson);
        die();
/*
        $resourceTemplateId = $api->create('resource_templates', $templateJson)->getContent()->id();
        foreach ($templateJson['o:resource_template_property'] as $propertyJson) {
            $propertyJson['o:resource_template_id'] = $resourceTemplateId;
            $api->create('resource_template_property', $propertyJson);
        }
*/        
        //die();
    }
}
