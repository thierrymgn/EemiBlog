<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Tag;
use App\Entity\Language;
use App\Entity\Translation;
use App\Entity\Media;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class AppFixtures extends Fixture
{
    private $faker;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $languages = $this->loadLanguages($manager);

        $users = $this->loadUsers($manager);

        $categories = $this->loadCategories($manager);

        $tags = $this->loadTags($manager);

        $this->loadArticlesAndComments($manager, $users, $categories, $tags, $languages);

        $manager->flush();
    }

    private function loadLanguages(ObjectManager $manager): array
    {
        $languages = [];
        $languagesData = [
            ['fr', 'Français', 'fr_FR', true],
            ['en', 'English', 'en_GB', false],
            ['es', 'Español', 'es_ES', false],
        ];

        foreach ($languagesData as [$code, $name, $locale, $isDefault]) {
            $language = new Language();
            $language->setCode($code)
                ->setName($name)
                ->setLocale($locale)
                ->setDefault($isDefault)
                ->setActive(true)
                ->setPosition(array_search([$code, $name, $locale, $isDefault], $languagesData))
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());

            $manager->persist($language);
            $languages[] = $language;
        }

        return $languages;
    }

    private function loadUsers(ObjectManager $manager): array
    {
        $users = [];

        $admin = new User();
        $admin->setEmail('admin@webminds.fr')
            ->setFirstname('Admin')
            ->setLastname('WebMinds')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'))
            ->setVerified(true)
            ->setBanned(false)
            ->setBio('Administrateur principal de WebMinds')
            ->setLocale('fr')
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($admin);
        $users[] = $admin;

        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setEmail($this->faker->email())
                ->setFirstname($this->faker->firstName())
                ->setLastname($this->faker->lastName())
                ->setRoles(['ROLE_USER'])
                ->setPassword($this->passwordHasher->hashPassword($user, 'user123'))
                ->setVerified(true)
                ->setBanned(false)
                ->setBio($this->faker->text(200))
                ->setLocale('fr')
                ->setCreatedAt($this->faker->dateTimeBetween('-1 year'))
                ->setUpdatedAt(new \DateTime());
            $manager->persist($user);
            $users[] = $user;
        }

        $banned = new User();
        $banned->setEmail('banned@webminds.fr')
            ->setFirstname('Banned')
            ->setLastname('User')
            ->setRoles(['ROLE_BANNED'])
            ->setPassword($this->passwordHasher->hashPassword($banned, 'banned123'))
            ->setVerified(true)
            ->setBanned(true)
            ->setBio('Compte banni pour spam')
            ->setLocale('fr')
            ->setCreatedAt($this->faker->dateTimeBetween('-6 months'))
            ->setUpdatedAt(new \DateTime());
        $manager->persist($banned);
        $users[] = $banned;

        return $users;
    }

    private function loadCategories(ObjectManager $manager): array
    {
        $categories = [];
        $categoryData = [
            ['Développement Web', 'code-bracket', '#4A90E2'],
            ['Design UI/UX', 'palette', '#9B59B6'],
            ['Marketing Digital', 'trending-up', '#2ECC71'],
            ['Intelligence Artificielle', 'cpu', '#E74C3C'],
            ['Cybersécurité', 'shield', '#F1C40F']
        ];

        foreach ($categoryData as $i => [$name, $icon, $color]) {
            $category = new Category();
            $category->setName($name)
                ->setSlug($this->faker->slug())
                ->setDescription($this->faker->paragraph())
                ->setIcon($icon)
                ->setPosition($i)
                ->setColor($color)
                ->setVisible(true)
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());

            $manager->persist($category);
            $categories[] = $category;
        }

        return $categories;
    }

    private function loadTags(ObjectManager $manager): array
    {
        $tags = [];
        $tagNames = ['PHP', 'Symfony', 'JavaScript', 'React', 'Vue.js', 'Docker', 'DevOps',
            'SEO', 'UX Design', 'Responsive', 'API', 'GraphQL', 'MySQL', 'MongoDB'];

        foreach ($tagNames as $name) {
            $tag = new Tag();
            $tag->setName($name)
                ->setSlug($this->faker->slug())
                ->setDescription($this->faker->sentence())
                ->setColor(sprintf('#%06X', mt_rand(0, 0xFFFFFF)))
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());

            $manager->persist($tag);
            $tags[] = $tag;
        }

        return $tags;
    }

    private function loadArticlesAndComments(ObjectManager $manager, array $users, array $categories, array $tags, array $languages): void
    {
        foreach ($categories as $category) {
            $numArticles = rand(3, 7);

            for ($i = 0; $i < $numArticles; $i++) {
                $article = new Article();
                $article->setTitle($this->faker->sentence())
                    ->setContent($this->faker->paragraphs(rand(3, 8), true))
                    ->setSlug($this->faker->slug())
                    ->setPublished(true)
                    ->setViewCount(rand(10, 1000))
                    ->setPublishedAt($this->faker->dateTimeBetween('-1 year'))
                    ->setCreatedAt(new \DateTime())
                    ->setUpdatedAt(new \DateTime())
                    ->setCustomer($users[array_rand($users)])
                    ->setCategory($category)
                    ->setMetaTitle($this->faker->words(3, true))
                    ->setMetaDescription($this->faker->sentence());

                $numTags = rand(2, 4);
                $shuffledTags = $tags;
                shuffle($shuffledTags);
                for ($t = 0; $t < $numTags; $t++) {
                    $article->addTag($shuffledTags[$t]);
                }

                $manager->persist($article);

                foreach ($languages as $language) {
                    if ($language->getCode() !== 'fr') {
                        $translation = new Translation();
                        $translation->setEntityType('Article')
                            ->setEntityId($i)
                            ->setField('title')
                            ->setContent($this->faker->sentence())
                            ->setLanguage($language)
                            ->setCreatedAt(new \DateTime())
                            ->setUpdatedAt(new \DateTime());
                        $manager->persist($translation);
                    }
                }

                $this->createCommentsForArticle($manager, $article, $users);
            }
        }
    }

    private function createCommentsForArticle(ObjectManager $manager, Article $article, array $users): void
    {
        $numComments = rand(0, 8);
        $threadDepth = [0, 0, 0, 1, 1, 2]; // Pour contrôler la probabilité de profondeur des commentaires

        for ($i = 0; $i < $numComments; $i++) {
            $comment = new Comment();
            $comment->setContent($this->faker->paragraph())
                ->setApproved(true)
                ->setStatus('approved')
                ->setLikesCount(rand(0, 50))
                ->setCreatedAt($this->faker->dateTimeBetween($article->getCreatedAt()))
                ->setLevel(0)
                ->setUpdatedAt(new \DateTime())
                ->setArticle($article)
                ->setPublisher($users[array_rand($users)])
                ->setIpAddress($this->faker->ipv4())
                ->setUserAgent($this->faker->userAgent());

            $manager->persist($comment);

            $depth = $threadDepth[array_rand($threadDepth)];
            if ($depth > 0) {
                $this->createCommentReplies($manager, $comment, $users, $depth);
            }
        }
    }

    private function createCommentReplies(ObjectManager $manager, Comment $parentComment, array $users, int $depth): void
    {
        if ($depth <= 0) return;

        $numReplies = rand(1, 3);
        for ($i = 0; $i < $numReplies; $i++) {
            $reply = new Comment();
            $reply->setContent($this->faker->paragraph())
                ->setApproved(true)
                ->setStatus('approved')
                ->setLikesCount(rand(0, 20))
                ->setCreatedAt($this->faker->dateTimeBetween($parentComment->getCreatedAt()))
                ->setUpdatedAt(new \DateTime())
                ->setLevel($parentComment->getLevel() + 1)
                ->setArticle($parentComment->getArticle())
                ->setPublisher($users[array_rand($users)])
                ->setParent($parentComment)
                ->setIpAddress($this->faker->ipv4())
                ->setUserAgent($this->faker->userAgent());

            $manager->persist($reply);

            $this->createCommentReplies($manager, $reply, $users, $depth - 1);
        }
    }
}
