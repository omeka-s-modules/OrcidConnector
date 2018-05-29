<?php
namespace OrcidConnector\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class OrcidResearcherAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'OrcidConnector\Entity\OrcidResearcher';
    }
    
    public function getResourceName()
    {
        return 'orcid_researcher';
    }
    
    public function getRepresentationClass()
    {
        return 'OrcidConnector\Api\Representation\OrcidResearcherRepresentation';
    }
    
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
        ) {
            $data = $request->getContent();

            if (isset($data['o:user']['o:id'])) {
                $user = $this->getAdapter('users')->findEntity($data['o:user']['o:id']);
                $entity->setUser($user);
            }

            if (isset($data['o:person_item'])) {
                $item = $this->getAdapter('items')->findEntity($data['o:person_item']['o:id']);
                $entity->setPersonItem($item);
            }

            if (isset($data['access_token'])) {
                $entity->setAccessToken($data['access_token']);
            }

            if (isset($data['orcid_id'])) {
                $entity->setOrcidId($data['orcid_id']);
            }

            if (isset($data['refresh_tokens'])) {
                $entity->setRefreshTokens($data['refresh_tokens']);
            }

            if (isset($data['expiry_token'])) {
                $entity->setExpiryToken($data['expiry_token']);
            }

            if (isset($data['scope'])) {
                $entity->setScope($data['scope']);
            }

    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {

        if (isset($query['item_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.item',
                $this->createNamedParameter($qb, $query['item_id']))
                );
        }

        if (isset($query['user_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.user',
                $this->createNamedParameter($qb, $query['user_id']))
                );
        }
        
    }
}
