<?php

namespace App\Command;

use App\Entity\AdministrativeUnit;
use App\Repository\AdministrativeUnitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(name: 'app:geo:seed-kinshasa', description: 'Active uniquement Kinshasa et crée sa hiérarchie (ville, 24 communes, quartiers de Lemba)')]
class GeoSeedKinshasaCommand extends Command
{
    private const COMMUNES = [
        'Bandalungwa', 'Barumbu', 'Bumbu', 'Gombe', 'Kalamu', 'Kasa-Vubu',
        'Kimbanseke', 'Kinshasa', 'Kintambo', 'Kisenso', 'Lemba', 'Limete',
        'Lingwala', 'Makala', 'Maluku', 'Masina', 'Matete', 'Mont-Ngafula',
        'Ndjili', 'Ngaba', 'Ngaliema', 'Ngiri-Ngiri', 'Nsele', 'Selembao',
    ];

    private const LEMBA_QUARTIERS = [
        'Kimpwanza', 'Madrandele', 'Ecole', 'Masano', 'Foire', 'Salongo',
        'Livulu', 'Kemi', 'Mbanza-Lemba', 'Molo', 'Gombele', 'Commercial', 'Echangeur',
    ];

    public function __construct(
        private readonly AdministrativeUnitRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $slugger = new AsciiSlugger();

        // 1. Retrouver la province Kinshasa (déjà importée au niveau 1)
        $kinshasaProvince = $this->repository->findOneBy(['name' => 'Kinshasa', 'level' => 1]);
        if (null === $kinshasaProvince) {
            $output->writeln('<error>Province "Kinshasa" introuvable (niveau 1). As-tu bien importé les 26 provinces ?</error>');
            return Command::FAILURE;
        }

        // 2. Désactiver toutes les AUTRES provinces, renseigner le type sur toutes
        $allProvinces = $this->repository->findBy(['level' => 1]);
        foreach ($allProvinces as $province) {
            $province->setActive($province->getId() === $kinshasaProvince->getId());
            $province->setTypeLabel('Province');
        }
        $output->writeln('Provinces désactivées : ' . (count($allProvinces) - 1) . ' (seule Kinshasa reste active)');

        // 3. Créer la ville de Kinshasa (niveau 2), si elle n'existe pas déjà
        $kinshasaVille = $this->repository->findOneBy(['name' => 'Kinshasa', 'level' => 2, 'parent' => $kinshasaProvince]);
        if (null === $kinshasaVille) {
            $kinshasaVille = new AdministrativeUnit();
            $kinshasaVille->setName('Kinshasa');
            $kinshasaVille->setLevel(2);
            $kinshasaVille->setSlug(strtolower($slugger->slug('Kinshasa-ville')) . '-' . uniqid());
            $kinshasaVille->setParent($kinshasaProvince);
            $kinshasaVille->setActive(true);
            $kinshasaVille->setTypeLabel('Ville');
            $this->em->persist($kinshasaVille);
            $output->writeln('Ville "Kinshasa" créée (niveau 2).');
        } else {
            $kinshasaVille->setTypeLabel('Ville');
            $output->writeln('Ville "Kinshasa" déjà existante, mise à jour.');
        }

        // 4. Créer les 24 communes (niveau 3)
        $communeEntities = [];
        foreach (self::COMMUNES as $communeName) {
            $existing = $this->repository->findOneBy(['name' => $communeName, 'level' => 3, 'parent' => $kinshasaVille]);
            if (null !== $existing) {
                $existing->setTypeLabel('Commune');
                $communeEntities[$communeName] = $existing;
                continue;
            }

            $commune = new AdministrativeUnit();
            $commune->setName($communeName);
            $commune->setLevel(3);
            $commune->setSlug(strtolower($slugger->slug($communeName)) . '-' . uniqid());
            $commune->setParent($kinshasaVille);
            $commune->setActive(true);
            $commune->setTypeLabel('Commune');
            $this->em->persist($commune);
            $communeEntities[$communeName] = $commune;
        }
        $output->writeln(count(self::COMMUNES) . ' communes traitées (créées ou déjà existantes).');

        // 5. Créer les quartiers de Lemba (niveau 4)
        $lemba = $communeEntities['Lemba'] ?? null;
        if (null === $lemba) {
            $output->writeln('<error>Commune "Lemba" introuvable après création — anomalie.</error>');
            return Command::FAILURE;
        }

        $createdQuartiers = 0;
        foreach (self::LEMBA_QUARTIERS as $quartierName) {
            $existing = $this->repository->findOneBy(['name' => $quartierName, 'level' => 4, 'parent' => $lemba]);
            if (null !== $existing) {
                $existing->setTypeLabel('Quartier');
                continue;
            }

            $quartier = new AdministrativeUnit();
            $quartier->setName($quartierName);
            $quartier->setLevel(4);
            $quartier->setSlug(strtolower($slugger->slug($quartierName)) . '-' . uniqid());
            $quartier->setParent($lemba);
            $quartier->setActive(true);
            $quartier->setTypeLabel('Quartier');
            $this->em->persist($quartier);
            $createdQuartiers++;
        }
        $output->writeln("{$createdQuartiers} quartiers de Lemba créés.");

        $this->em->flush();

        $output->writeln('<info>Terminé : Kinshasa est maintenant la seule province active, avec sa ville, ses 24 communes, et les quartiers de Lemba (typeLabel renseigné partout).</info>');

        return Command::SUCCESS;
    }
}