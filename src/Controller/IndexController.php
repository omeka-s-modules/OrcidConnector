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
}
