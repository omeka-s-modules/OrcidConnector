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
        $api = $this->api();
        $researcherResponse = $api->search('orcid_researchers', ['user_id' => $this->identity()->getId()]);
        $researcher = $researcherResponse ? $researcherResponse->getContent()[0] : false;
        $view = new ViewModel;
        $view->setVariable('researcher', $researcher);
        $code = $this->params()->fromQuery('code', false);
        if ($code) {
            $oauth = new Oauth;
// for dev only
// $oauth->useSandboxEnvironment();
//
            $oauth->setClientId($this->orcidClientId);
            $oauth->setClientSecret($this->orcidClientSecret);
            $oauth->setRedirectUri($this->orcidRedirectUri);
            $oauth->authenticate($code);
            $view->setVariable('code', $code);
            $view->setVariable('profile', $profile);
            $view->setVariable('oauth', $oauth);
        } else {

            $orcidResearcherJson = [
                'orcid_id'       => $oauth->getOrcid(),
                'person_item'    => null,
                'user_id'        => $this->identity()->getId(),
                'access_token'   => $oauth->getAccessToken(),
            ];

            //$response = $this->api()->create('orcid_researchers', $orcidResearcherJson);
            $this->preparePropertyMap();
            //$itemJson = $this->buildItemJson($oauth, $profile);
            $view->setVariable('itemJson', $itemJson);
            $api->create('items', $itemJson);

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
}
