<?php

namespace OrcidConnector\Entity;

use Omeka\Entity\AbstractEntity;

// see https://members.orcid.org/api/tutorial/get-orcid-id
// and http://members.orcid.org/api/workflow/RIM-systems

/**
 * @Entity
 */
class OrcidResearcher extends AbstractEntity
{
    
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @UniqueConstraint
     * @Column(type="string")
     */
    protected $orcidId;

    /**
     * @UniqueConstraint
     * @OneToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=false)
     */
    protected $personItem;

    /**
     * @UniqueConstraint
     * @OneToOne(targetEntity="Omeka\Entity\User")
     * @JoinColumn(nullable=false)
     */
    protected $user;

    /**
     * @Column(type="string")
     */
    protected $accessToken;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $refreshTokens;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $scope;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $expiryToken;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getOrcidId()
    {
        return $this->orcidId;
    }

    public function setOrcidId($orcidId)
    {
        $this->orcidId = $orcidId;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setPersonItem($item)
    {
        $this->item = $item;
    }
    
    public function getPersonItem()
    {
        return $this->personItem;
    }
    
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getRefreshTokens()
    {
        return $this->refreshTokens;
    }

    public function setRefreshTokens($refreshTokens)
    {
        $this->refreshTokens = $refreshTokens;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setExpiryToken($expiryToken)
    {
        $this->expiryToken = $expiryToken;
    }

    public function getExpiryToken()
    {
        return $this->expiryToken;
    }
}
