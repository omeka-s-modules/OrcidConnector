<?php

namespace OrcidConnector;

use Omeka\Module\AbstractModule;
use Zend\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{

    public function getConfig()
    {
        return include __DIR__.'/config/module.config.php';
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
    }

    public function orcidForm($event)
    {
        $view = $event->getTarget();
        echo $view->partial('orcid-connector/admin/orcid',
            []
        );
    }
}
