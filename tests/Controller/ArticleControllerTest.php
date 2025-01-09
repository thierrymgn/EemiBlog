<?php

namespace App\Tests\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArticleControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testIndex(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/article');

        $this->assertResponseStatusCodeSame(200, 'Expected status code 200, got ' . $this->client->getResponse()->getStatusCode());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Article index');
    }

    public function testNew(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/article/new');

        $this->assertResponseIsSuccessful();

        $category = $this->entityManager
            ->getRepository(Category::class)
            ->findOneBy([]);

        $form = $crawler->selectButton('Save')->form([
            'article[title]' => 'Test Article',
            'article[content]' => 'Test Content',
            'article[metaTitle]' => 'Test Meta',
            'article[metaDescription]' => 'Test Meta Description',
            'article[category]' => $category->getId(),
            'article[viewCount]' => 0,
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/article');
    }

    public function testShow(): void
    {
        $article = $this->createArticle();
        $this->loginAsAdmin();

        $this->client->request('GET', '/article/'.$article->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $article->getTitle());
    }

    public function testEdit(): void
    {
        $article = $this->createArticle();
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/article/'.$article->getId().'/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Update')->form([
            'article[title]' => 'Updated Title',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/article');

        $updatedArticle = $this->entityManager
            ->getRepository(Article::class)
            ->find($article->getId());

        $this->assertEquals('Updated Title', $updatedArticle->getTitle());
    }

    public function testDelete(): void
    {
        $article = $this->createArticle();
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/article');

        // Utiliser le token CSRF depuis le formulaire
        $form = $crawler->filter('form[action="/article/'.$article->getId().'"]')->form([
            '_token' => $this->client->getContainer()
                ->get('security.csrf.token_manager')
                ->getToken('delete'.$article->getId())
                ->getValue()
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/article');

        $deletedArticle = $this->entityManager
            ->getRepository(Article::class)
            ->find($article->getId());

        $this->assertNull($deletedArticle);
    }

    private function createArticle(): Article
    {
        $category = $this->entityManager
            ->getRepository(Category::class)
            ->findOneBy([]);

        $article = new Article();
        $article->setTitle('Test Article')
            ->setContent('Test Content')
            ->setSlug('test-article')
            ->setCustomer($this->getTestUser())
            ->setCategory($category)
            ->setPublished(true)
            ->setViewCount(0)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $article;
    }

    private function getTestUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@webminds.fr']);
    }

    private function loginAsAdmin(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'admin@webminds.fr']);
        $this->client->loginUser($testUser);
    }
}
