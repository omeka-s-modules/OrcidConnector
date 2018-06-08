<?php
namespace OrcidConnector\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class OrcidResearcherRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return [
            'id' => $this->resource->getId(),
            'orcid_id' => $this->resource->getOrcidId(),
            'refresh_tokens' => $this->resource->getRefreshTokens(),
            'expiry_token' => $this->resource->getExpiryToken(),
            'access_token' => $this->resource->getAccessToken(),
            'scope' => $this->resource->getScope(),
            'o:user' => $this->getReference(),
//            'o:item' => $this->getReference(), //this looks broken, and so broken in FedoraConnector, too
            'o:item' => $this->getPersonItem(),
        ];
    }
    
    public function getJsonLdType()
    {
        return 'o:OrcidResearcher';
    }
    
    public function user()
    {
        return $this->getAdapter('user')->getRepresentation($this->resource->getUser());
    }
    
    public function item()
    {
        return $this->getAdapter('item')->getRepresentation($this->resource->getPersonItem());
    }
    
    public function orcidId()
    {
        return $this->resource->getOrcidId();
    }

}
