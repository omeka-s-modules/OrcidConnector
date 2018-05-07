<?php

namespace OrcidConnector;

use Omeka\Module\AbstractModule;
use OrcidConnector\Form\ConfigForm;
use Zend\View\Renderer\PhpRenderer;
use Zend\Mvc\Controller\AbstractController;
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
        $globals = $this->getServiceLocator()->get('Omeka\Settings');
        echo $view->partial('orcid-connector/admin/orcid',
            [
                'orcid_redirect_uri'  => $globals->get('orcid_redirect_uri', ''),
                'orcid_client_id'     => $globals->get('orcid_client_id', ''),
                'orcid_client_secret' => $globals->get('orcid_client_secret', ''),
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
            'orcid_redirect_uri'  => $globals->get('orcid_redirect_uri', ''),
            'orcid_client_id'     => $globals->get('orcid_client_id', ''),
            'orcid_client_secret' => $globals->get('orcid_client_secret', ''),
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
    }
}
