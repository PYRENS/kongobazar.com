<?php

namespace App\Command;

use App\Entity\AdministrativeUnit;
use App\Repository\AdministrativeUnitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(name: 'app:geo:import', description: 'Importe des unités administratives RDC depuis un CSV')]
class ImportAdministrativeUnitsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdministrativeUnitRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Chemin vers le fichier CSV');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('file');

        if (!file_exists($path)) {
            $output->writeln("<error>Fichier introuvable : {$path}</error>");
            return Command::FAILURE;
        }

        // Format CSV attendu : level;name;parent_slug (parent_slug vide pour le niveau 1)
        $handle = fopen($path, 'r');
        $slugger = new AsciiSlugger();
        $created = 0;
        $skipped = 0;
        $line = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $line++;
            if ($line === 1 && strtolower($row[0]) === 'level') {
                continue; // en-tête
            }

            [$level, $name, $parentSlug] = array_pad($row, 3, null);
            $level = (int) $level;
            $name = trim($name);
            $parentSlug = $parentSlug ? trim($parentSlug) : null;

            if ('' === $name) {
                continue;
            }

            $baseSlug = strtolower($slugger->slug($name));

            $parent = null;
            if (null !== $parentSlug) {
                $parent = $this->repository->findOneBy(['slug' => $parentSlug]);
                if (null === $parent) {
                    $output->writeln("<comment>Ligne {$line} ignorée : parent '{$parentSlug}' introuvable pour '{$name}'</comment>");
                    $skipped++;
                    continue;
                }
            }

            $existing = $this->repository->findOneBy(['name' => $name, 'parent' => $parent]);
            if (null !== $existing) {
                $skipped++;
                continue;
            }

            $unit = new AdministrativeUnit();
            $unit->setName($name);
            $unit->setLevel($level);
            $unit->setSlug($baseSlug . '-' . uniqid());
            $unit->setParent($parent);
            
            $this->em->persist($unit);
            $created++;

            if ($created % 200 === 0) {
                $this->em->flush();
                $this->em->clear();
                $output->writeln("{$created} unités importées...");
            }
        }

        fclose($handle);
        $this->em->flush();

        $output->writeln("<info>Terminé : {$created} unités créées, {$skipped} ignorées (doublons ou parent introuvable).</info>");

        return Command::SUCCESS;
    }
}