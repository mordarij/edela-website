<?php

namespace Acme\EdelaBundle\Repository;

use Acme\EdelaBundle\Entity\Goal;
use Acme\UserBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query;

/**
 * TaskRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TaskRepository extends EntityRepository
{
    public function getTasks(User $user, \DateTime $date = null, Goal $goal = null)
    {

        if (!$date) {
            $date = new \DateTime();
        }
        $date->modify('midnight');

        $builder = $this->createQueryBuilder('t');
        $tasks = $builder
            ->select('t.name as title')
            ->addSelect('t.id as id')
            ->addSelect('t.createdAt as created_at')
            ->addSelect('t.dateAt as date')
            ->addSelect('g.name as goal_title')
            ->addSelect('g.id as goal_id')
            ->addSelect('t.isDone as done')
            ->addSelect('t.isImportant as is_important')
            ->addSelect('t.isUrgent as is_urgent')
            ->addSelect('t.isSmsNotification as is_sms_notification')
            ->addSelect('t.notificationTime as notification_time')
            ->addSelect('IDENTITY(t.parent) as parent')
            ->addSelect('t.note as note')
            ->leftJoin('AcmeEdelaBundle:Goal', 'g', Join::WITH, 't.goal=g')
            ->leftJoin('AcmeEdelaBundle:Tag', 'tag', Join::WITH, 'tag MEMBER OF t.tags')
            ->addSelect('tag.title as tag_title')
            ->where('t.user=:user')->setParameter('user', $user)
            ->andWhere('t.isDone=:done')->setParameter('done', false);

        $orX = $builder->expr()->orX();
        $orX->add($builder->expr()->eq('t.dateAt', ':date'));
        $orX->add($builder->expr()->isNull('t.dateAt'));

        $tasks->andWhere($orX)
            ->setParameter('date', $date);

        if ($goal) {
            $tasks->andWhere('t.goal=:goal')->setParameter('goal', $goal);
        }
        $tasks = $tasks->getQuery()->getArrayResult();
        $tags = [];

        foreach ($tasks as $key => $task) {
            if (isset($task['tag_title']) && $task['tag_title']) {
                $title = $tasks[$key]['tag_title'];
                unset($tasks[$key]['tag_title']);
                if (!isset($tags[$task['id']])) {
                    $tags[$task['id']] = [];
                } else {
                    unset($tasks[$key]);
                }
                $tags[$task['id']][] = ['title' => $title];

            }
        }
        foreach ($tasks as $key => $task) {
            if (isset($tags[$task['id']])) {
                $tasks[$key]['tags'] = $tags[$task['id']];
            }
            $tasks[$key]['notification_time'] = $tasks[$key]['notification_time'] ? $tasks[$key]['notification_time']->format('H:i'): null;
            $tasks[$key]['date'] = $tasks[$key]['date'] ? $tasks[$key]['date']->format('Y-m-d') : null;
            if (!$tasks[$key]['parent']) {
                $tasks[$key]['parent'] = 0;
            }
        }
        return array_values($tasks);
    }
}
