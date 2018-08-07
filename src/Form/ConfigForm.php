<?php
namespace OrcidConnector\Form;

use Zend\Form\Form;

class ConfigForm extends Form
{
    protected $orcidRedirectUri;

    protected $orcidClientId;

    protected $orcidClientSecret;

    protected $orcidSampleClientId;

    public function init()
    {

        $this->add([
            'type' => 'text',
            'name' => 'orcid_redirect_uri',
            'attributes' => [
                'value' => $this->orcidClientId,
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
                'value' => $this->orcidClientId,
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
                'value' => $this->orcidClientSecret,
            ],
            'options' => [
                'label' => 'Client Secret', // @translate
                'info' => 'The secret code ORCID gave you when registering', // @translate
            ],

        ]);
    }
}

