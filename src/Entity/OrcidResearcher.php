<?php

namespace OrcidConnector\Entity;

use Omeka\Entity\AbstractEntity;

//see https://members.orcid.org/api/tutorial/get-orcid-id

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
     *
     */
    protected $accessToken;

    /**
     * @Column(type="string")
     *
     */
    protected $refreshToken;

    /**
     * @Column(type="string")
     *
     */
    protected $scope;

    public function getId()
    {
        return $this->id;
    }
}
