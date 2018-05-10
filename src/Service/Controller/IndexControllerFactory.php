<?php
namespace OrcidConnector\Service\Controller;

use OrcidConnector\Controller\IndexController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $globalSettings = $serviceLocator->get('Omeka\Settings');
        $redirectUri = $globalSettings->get('orcid_redirect_uri');
        $clientId = $globalSettings->get('omeka_client_id');
        $clientSecret = $globalSettings->get('orcid_client_secret');
        $indexController = new IndexController;
        $indexController->setOrcidRedirectUri($redirectUri);
        $indexController->setOrcidClientId($clientId);
        $indexController->setOrcidClientSecret($clientSecret);
        return $indexController;
    }
}
