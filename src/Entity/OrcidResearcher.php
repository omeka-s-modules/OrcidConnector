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
     * @Column(type="string")
     */
    protected $orcidId;

    /**
     * @Column(type="integer")
     * @ManyToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=false)
     */
    protected $personItem;

    /**
     * @Column(type="integer")
     * @ManyToOne(targetEntity="Omeka\Entity\User")
     * @JoinColumn(nullable=false)
     */
    protected $userId;

    /**
     * @Column(type="string")
     */
    protected $accessToken;

    /**
     * @Column(type="string")
     */
    protected $refreshTokens;

    /**
     * @Column(type="string")
     */
    protected $scope;

    /**
     * @Column(type="string")
     */
    protected $expiryToken;

    public function getOrcidId()
    {
        return $this->orcidId;
    }

    public function setOrcidId($id)
    {
        $this->orcidId = $id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
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

