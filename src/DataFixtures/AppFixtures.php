<?php

namespace App\DataFixtures;

use App\Entity\Advertisement;
use App\Entity\BlogPost;
use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\CategoryViewLog;
use App\Entity\Color;
use App\Entity\CommissionTier;
use App\Entity\CustomMenuItem;
use App\Entity\DiscountCampaign;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\ProductRecommendation;
use App\Entity\ProductVariant;
use App\Entity\ProductViewLog;
use App\Entity\Review;
use App\Entity\Size;
use App\Entity\StoreProfile;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /** Translittère les accents en ASCII pour des slugs propres (ex: "Robe imprimée été" → "robe-imprimee-ete"). */
    private function slugify(string $text): string
    {
        return strtolower((string) (new AsciiSlugger())->slug($text));
    }

    public function load(ObjectManager $manager): void
    {
        // ---------- Marques ----------
        $brandNames = ['Samsung', 'Tecno', 'Bosch', 'Nike', 'Adidas'];
        $brands = [];
        foreach ($brandNames as $i => $name) {
            $brand = new Brand();
            $brand->setName($name);
            $brand->setSlug(strtolower($name));
            $brand->setVerified(true);
            $brand->setFeaturedHomepage(true);
            $brand->setFeaturedHomepagePosition($i);
            $manager->persist($brand);
            $brands[] = $brand;
        }

        // ---------- Couleurs & tailles ----------
        $colorNames = ['Rouge' => '#e53935', 'Bleu' => '#2FA8E0', 'Vert' => '#43a047', 'Noir' => '#222222'];
        $colors = [];
        foreach ($colorNames as $name => $hex) {
            $color = new Color();
            $color->setName($name);
            $color->setHexCode($hex);
            $manager->persist($color);
            $colors[] = $color;
        }

        $sizeNames = ['S', 'M', 'L', 'XL'];
        $sizes = [];
        foreach ($sizeNames as $name) {
            $size = new Size();
            $size->setName($name);
            $size->setType('clothing');
            $manager->persist($size);
            $sizes[] = $size;
        }

        // ---------- Arbre de catégories : 3 rayons ----------
        $rayonsData = [
            ['name' => 'Mode', 'color' => '#C9992A', 'icon' => 'bi-bag-heart', 'children' => ['Vêtements Homme', 'Vêtements Femme', 'Chaussures']],
            ['name' => 'Électronique', 'color' => '#2FA8E0', 'icon' => 'bi-cpu', 'children' => ['Téléphones', 'Ordinateurs', 'Accessoires']],
            ['name' => 'Maison & Jardin', 'color' => '#43a047', 'icon' => 'bi-house-door', 'children' => ['Mobilier', 'Décoration', 'Cuisine']],
        ];

        $allLeafCategories = [];
        foreach ($rayonsData as $ri => $rayonData) {
            $rayon = new Category();
            $rayon->setName($rayonData['name']);
            $rayon->setSlug(strtolower(str_replace([' ', '&'], ['-', ''], $rayonData['name'])) . '-' . uniqid());
            $rayon->setPosition($ri);
            $rayon->setThemeColor($rayonData['color']);
            $rayon->setFeaturedHomepageTab(true);
            $rayon->setFeaturedHomepagePosition($ri);
            $rayon->setFeaturedHomepageBlock(true);
            $rayon->setFeaturedHomepageBlockPosition($ri);
            $rayon->setTopRayon(true);
            $rayon->setTopRayonPosition($ri);
            $manager->persist($rayon);

            foreach ($rayonData['children'] as $ci => $childName) {
                $child = new Category();
                $child->setName($childName);
                $child->setSlug(strtolower(str_replace([' ', '&'], ['-', ''], $childName)) . '-' . uniqid());
                $child->setPosition($ci);
                $child->setParent($rayon);
                $manager->persist($child);
                $allLeafCategories[] = $child;
            }
        }

        // Quelques catégories illustrées pour "Top Catégories" (réutilise les feuilles créées)
        foreach (array_slice($allLeafCategories, 0, 6) as $i => $cat) {
            $cat->setImageName('placeholder-category.jpg'); // remplace par une vraie image plus tard
        }

        // ---------- Utilisateur admin (pour Review/BlogPost author) ----------
        $admin = new User();
        $admin->setEmail('admin@kongobazar.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password123'));
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setPhone('+243900000001');
        $manager->persist($admin);

        // ---------- Vendeurs (StoreProfile) ----------
        $vendorNames = ['Boutique Marvelus', 'Oreva Fashion', 'Cenva Électro'];
        $sellerProfiles = [];
        foreach ($vendorNames as $i => $vendorName) {
            $vendorUser = new User();
            $vendorUser->setEmail("vendeur{$i}@kongobazar.com");
            $vendorUser->setPassword($this->passwordHasher->hashPassword($vendorUser, 'password123'));
            $vendorUser->setRoles(['ROLE_STORE_ADMIN']);
            $vendorUser->setPhone('+24390000000' . ($i + 2));
            $manager->persist($vendorUser);

            $store = new StoreProfile();
            $store->setUser($vendorUser);
            $store->setStoreName($vendorName);
            $store->setDisplayName($vendorName);
            $store->setSlug(strtolower(str_replace(' ', '-', $vendorName)) . '-' . uniqid());
            $store->setStatus('active');
            $store->setLogoName(null); // pas d'image de test pour l'instant
            $manager->persist($store);
            $sellerProfiles[] = $store;
        }

        // ---------- Acheteur de test (pour Review) ----------
        $buyer = new User();
        $buyer->setEmail('acheteur@kongobazar.com');
        $buyer->setPassword($this->passwordHasher->hashPassword($buyer, 'password123'));
        $buyer->setRoles(['ROLE_BUYER']);
        $buyer->setPhone('+243900000099');
        $manager->persist($buyer);

        // ---------- Produits ----------
        $productNames = [
            'Casque audio sans fil', 'Robe imprimée été', 'Montre connectée', 'Sac à main cuir',
            'Chaussures de sport', 'Chaise de bureau', 'Smartphone Android', 'Veste en jean',
            'Lampe de salon', 'Sandales femme', 'Ordinateur portable', 'T-shirt coton',
        ];

        $products = [];
        foreach ($productNames as $i => $name) {
            $category = $allLeafCategories[$i % count($allLeafCategories)];
            $sellerProfile = $sellerProfiles[$i % count($sellerProfiles)];
            $brand = $brands[$i % count($brands)];

            $basePrice = random_int(15, 300);
            $onSale = $i % 3 === 0;

            $product = new Product();
            $product->setSellerProfile($sellerProfile);
            $product->setCategory($category);
            $product->setBrand($brand);
            $product->setTitle($name);
            $product->setSlug(strtolower(str_replace(' ', '-', $name)) . '-' . uniqid());
            $product->setDescription('Description de démonstration pour ' . $name . '. Produit de qualité, livraison rapide via point relais.');
            $product->setReference('REF-' . strtoupper(substr(md5($name), 0, 6)));
            $product->setBasePrice((string) $basePrice);
            $product->setCurrency('USD');
            $product->setNegotiable($i % 4 === 0);
            $product->setStatus('active');
            $product->setFeatured($i % 5 === 0);
            $product->setSalesCount(random_int(0, 200));
            $product->setShippingMinDays(2);
            $product->setShippingMaxDays(5);
            if ($onSale) {
                $product->setCompareAtPrice((string) ($basePrice + random_int(10, 50)));
            }
            $manager->persist($product);
            $products[] = $product;

            // Une variante simple par produit (stock garanti > 0)
            $variant = new ProductVariant();
            $variant->setProduct($product);
            $variant->setColor($colors[$i % count($colors)]);
            $variant->setSize($sizes[$i % count($sizes)]);
            $variant->setQuantity(random_int(5, 50));
            $manager->persist($variant);

            // Une DiscountCampaign active pour 1 produit sur 4
            if ($i % 4 === 0) {
                $campaign = new DiscountCampaign();
                $campaign->setProduct($product);
                $campaign->setMode('immediate');
                $campaign->setDiscountedPrice((string) round($basePrice * 0.8, 2));
                $campaign->setStartAt(new \DateTimeImmutable('-1 day'));
                $campaign->setEndAt(new \DateTimeImmutable('+3 days'));
                $campaign->setStatus('active');
                $manager->persist($campaign);
            }
        }

        // ---------- Avis vendeurs (pour la note moyenne du Top Vendeur) ----------
        foreach ($sellerProfiles as $sellerProfile) {
            for ($r = 0; $r < 3; $r++) {
                $review = new Review();
                $review->setReviewer($buyer);
                $review->setTarget($sellerProfile->getUser());
                $review->setDirection('buyer_to_seller');
                $review->setRating(random_int(3, 5));
                $review->setOrder($this->createDummyOrder($manager, $buyer, $sellerProfile));
                $manager->persist($review);
            }
        }

        // ---------- Publicités (toutes les zones utilisées par la homepage) ----------
        $adZones = [
            'homepage_hero_main', 'homepage_hero_main', 'homepage_hero_main',
            'homepage_hero_side_top', 'homepage_hero_side_bottom',
            'sidebar_top', 'sidebar_middle',
            'homepage_promo_strip', 'homepage_center_banner',
            'mega_menu_catalogue',
            'homepage_lifestyle_left', 'homepage_lifestyle_center', 'homepage_lifestyle_right',
            'footer_social_banner',
        ];
        foreach ($adZones as $i => $zone) {
            $ad = new Advertisement();
            $ad->setTitle('Publicité ' . $zone . ' #' . $i);
            $ad->setTargetSpace('public');
            $ad->setZoneKey($zone);
            $ad->setPosition($i);
            $ad->setStartAt(new \DateTimeImmutable('-1 day'));
            $ad->setEndAt(new \DateTimeImmutable('+30 days'));
            $ad->setStatus('active');
            $ad->setIsPaid(false);
            $manager->persist($ad);
        }

        // Bannière liée à chaque rayon en bloc complet
        foreach (array_slice($allLeafCategories, 0, 3) as $i => $cat) {
            $ad = new Advertisement();
            $ad->setTitle('Bannière bloc catégorie #' . $i);
            $ad->setTargetSpace('public');
            $ad->setZoneKey('category_block_banner');
            $ad->setRelatedCategory($cat->getParent()); // liée au rayon parent
            $ad->setStartAt(new \DateTimeImmutable('-1 day'));
            $ad->setEndAt(new \DateTimeImmutable('+30 days'));
            $ad->setStatus('active');
            $manager->persist($ad);
        }

        // ---------- Menu personnalisé (footer + header) ----------
        $footerLinks = [
            'footer_col_1' => ['À propos' => '/a-propos', 'Blog' => '/blog'],
            'footer_col_2' => ['Mon compte' => '/compte', 'Mes commandes' => '/compte/commandes'],
            'footer_col_3' => ['CGU' => '/cgu', 'Politique de confidentialité' => '/confidentialite'],
            'footer_col_4' => ['Retours' => '/retours', 'Réclamations' => '/reclamations'],
            'footer_col_5' => ['Livraison' => '/livraison', 'Paiement' => '/paiement'],
            'footer_col_6' => ['Devenir vendeur' => '/devenir-vendeur'],
            'footer_col_7' => ['FAQ' => '/faq', 'Centre d\'aide' => '/aide'],
            'footer_col_8' => ['Contact' => '/contact'],
        ];

        $networkMenuItem = new CustomMenuItem();
        $networkMenuItem->setLocation('header_main');
        $networkMenuItem->setLabel('Notre Réseau');
        $networkMenuItem->setInternalRoute('network_index');
        $networkMenuItem->setTargetSpace('public');
        $networkMenuItem->setActive(true);
        $manager->persist($networkMenuItem);
        
        foreach ($footerLinks as $location => $links) {
            foreach ($links as $label => $url) {
                $item = new CustomMenuItem();
                $item->setLocation($location);
                $item->setLabel($label);
                $item->setUrl($url);
                $item->setTargetSpace('public');
                $item->setActive(true);
                $manager->persist($item);
            }
        }

        // ---------- Article de blog ----------
        $post = new BlogPost();
        $post->setAuthor($admin);
        $post->setTitle('Comment bien choisir son point relais');
        $post->setSlug('comment-bien-choisir-son-point-relais');
        $post->setExcerpt('Nos conseils pour bien choisir votre point relais KongoBazar.');
        $post->setContent('Contenu de démonstration...');
        $post->setStatus('published');
        $post->setPublishedAt(new \DateTimeImmutable('-2 days'));
        $manager->persist($post);

        // ---------- Journaux de visites (Tendances / Most Viewed) ----------
        foreach ($allLeafCategories as $cat) {
            for ($v = 0; $v < random_int(1, 15); $v++) {
                $log = new CategoryViewLog();
                $log->setCategory($cat);
                $manager->persist($log);
            }
        }
        foreach ($products as $product) {
            for ($v = 0; $v < random_int(1, 20); $v++) {
                $log = new ProductViewLog();
                $log->setProduct($product);
                $manager->persist($log);
            }
        }

        if (count($products) >= 4) {
            // Cas 1 : sens unique — jouet a besoin de piles, l'inverse ne fait pas sens
            $toyNeedsBattery = new ProductRecommendation();
            $toyNeedsBattery->setProduct($products[0]);
            $toyNeedsBattery->setRecommendedProduct($products[1]);
            $toyNeedsBattery->setPosition(0);
            $toyNeedsBattery->setMutual(false);
            $manager->persist($toyNeedsBattery);

            // Cas 2 : réciproque — chemise et pantalon se recommandent mutuellement
            $shirtPantsCombo = new ProductRecommendation();
            $shirtPantsCombo->setProduct($products[2]);
            $shirtPantsCombo->setRecommendedProduct($products[3]);
            $shirtPantsCombo->setPosition(0);
            $shirtPantsCombo->setMutual(true);
            $manager->persist($shirtPantsCombo);
        }

        $manager->flush();
    }

    private function createDummyOrder(ObjectManager $manager, User $buyer, StoreProfile $seller): \App\Entity\Order
    {
        $order = new \App\Entity\Order();
        $order->setBuyer($buyer);
        $order->setSellerProfile($seller);
        $order->setCheckoutGroup(uniqid('grp_', true));
        $order->setStatus('delivered');
        $order->setTotalAmount('50.00');
        $order->setCurrency('USD');
        $order->setTotalAmountUsd('50.00');
        $order->setEscrowStatus('released');
        $manager->persist($order);
        $manager->flush();

        return $order;
    }
}