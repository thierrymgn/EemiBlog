<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findArticlesPaginated(
        int $page = 1,
        ?string $categorySlug = null,
        ?string $tagSlug = null,
        ?string $search = null,
        int $limit = 10
    ): Paginator {
        $query = $this->createQueryBuilder('a')
            ->andWhere('a.published_at IS NOT NULL')
            ->andWhere('a.published_at <= :now')
            ->setParameter('now', new \DateTime())
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.tags', 't')
            ->orderBy('a.createdAt', 'DESC');

        if ($categorySlug) {
            $query->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $categorySlug);
        }

        if ($search) {
            $query->andWhere('a.title LIKE :search OR a.content LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($tagSlug) {
            $query->andWhere('t.slug = :tagSlug')
                ->setParameter('tagSlug', $tagSlug);
        }

        $query->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($query);
    }

    public function findSimilarArticles(Article $article, int $limit = 3): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.category = :category')
            ->andWhere('a.id != :id')
            ->andWhere('a.published_at IS NOT NULL')
            ->andWhere('a.published_at <= :now')
            ->setParameter('category', $article->getCategory())
            ->setParameter('id', $article->getId())
            ->setParameter('now', new \DateTime())
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Article[] Returns an array of Article objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Article
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
