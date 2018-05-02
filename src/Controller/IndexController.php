<?php

namespace OrcidConnector\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function authenticateAction()
    {
        $view = new ViewModel;
        $code = $this->params()->fromQuery('code', false);
        if ($code) {
            $view->setVariable('code', $code);
        }
        return $view;
    }
}
