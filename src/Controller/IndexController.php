<?php

namespace OrcidConnector\Controller;

use Orcid\Oauth;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $orcidClientId;

    protected $orcidClientSecret;

    protected $orcidRedirectUri;

    public function authenticateAction()
    {
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
$profile = $oauth->getProfile();
$view->setVariable('profile', $profile);
//
            $view->setVariable('code', $code);
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
