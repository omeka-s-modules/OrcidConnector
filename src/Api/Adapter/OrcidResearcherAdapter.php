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
            
            /*
            if (isset($data['o:job']['o:id'])) {
                $job = $this->getAdapter('jobs')->findEntity($data['o:job']['o:id']);
                $entity->setJob($job);
            }
            
            
            if (isset($data['comment'])) {
                $entity->setComment($data['comment']);
            }
            */
    }
    
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        /*
        if (isset($query['job_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.job',
                $this->createNamedParameter($qb, $query['job_id']))
                );
        }
        */
    }
}
