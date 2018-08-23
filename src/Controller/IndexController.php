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
        $redirectUrl = $this->url()->fromRoute('admin/orcidconnector', [], ['force_canonical' => true]);
        $api = $this->api();
        $view = new ViewModel;
        $researcherResponse = $api->search('orcid_researchers', ['user_id' => $this->identity()->getId()]);

        if (empty($researcherResponse->getContent())) {
            $researcher = false;
        } else {
            $researcher = $researcherResponse ? $researcherResponse->getContent()[0] : false;
        }
        $view->setVariable('researcher', $researcher);
        $code = $this->params()->fromQuery('code', false);
        $oauth = new Oauth;
        $oauth->setClientId($this->orcidClientId);
        $oauth->setClientSecret($this->orcidClientSecret);
        $oauth->setRedirectUri($redirectUrl);

        if ($code) {

// for dev only
 $oauth->useSandboxEnvironment();
//

            $oauth->authenticate($code);
            $profile = $oauth->getProfile();
            $view->setVariable('code', $code);
            $view->setVariable('profile', $profile);
            $view->setVariable('oauth', $oauth);
        } else {
            $orcidResearcherJson = [
                'orcid_id'       => $oauth->getOrcid(),
                'person_item_id'    => null,
                'user_id'        => $this->identity()->getId(),
                'access_token'   => $oauth->getAccessToken(),
            ];
            $profile = $oauth->getProfile();
            $OrcidResearchersResponse = $this->api()->create('orcid_researchers', $orcidResearcherJson);
            $this->preparePropertyMap();
            $itemJson = $this->buildItemJson($oauth, $profile);
            $view->setVariable('itemJson', $itemJson);
            $personItemResponse = $this->api()->create('items', $itemJson);
            $personItemContent = $personItemResponse->getContent();
            $personItemId = $personItemContent->id();
            $orcidResearcherJson['person_item_id'] = $personItemId;
            $orcidResearchersResponse = $this->api()->create('orcid_researchers', $orcidResearcherJson);
            $view->setVariable('orcid_researcher_json', $orcidResearcherJson);
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
