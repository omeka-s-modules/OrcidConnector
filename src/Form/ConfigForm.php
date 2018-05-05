<?php
namespace OrcidConnector\Form;

use Zend\Form\Form;

class ConfigForm extends Form
{
    
    public function init()
    {
        /*
        $this->add([
            'type' => '',
            'name' => '',
            'attributes' => [
                'label' => '',
                'info' => '',
            ],
            'options' => [
            ],

        ]);
        */
        $this->add([
            'type' => 'text',
            'name' => 'orcid_redirect_uri',
            'attributes' => [

            ],
            'options' => [
                'label' => 'Redirect URI', // @translate
                'info' => 'The redirect URI you have listed with ORCID for your site', // @translate
            ],
        ]);

        $this->add([
            'type' => 'text',
            'name' => 'orcid_client_id',
            'attributes' => [

            ],
            'options' => [
                'label' => 'Client Id', // @translate
                'info' => 'The client ID you have registered with ORCID', // @translate
            ],
        ]);

        $this->add([
            'type' => 'text',
            'name' => 'orcid_client_secret',
            'attributes' => [

            ],
            'options' => [
                'label' => 'Client Secret', // @translate
                'info' => 'The secret code ORCID gave you when registering', // @translate
            ],

        ]);
    }
}

