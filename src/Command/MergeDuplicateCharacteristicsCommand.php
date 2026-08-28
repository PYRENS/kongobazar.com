<?php

namespace App\Command;

use App\Entity\CategoryAttribute;
use App\Entity\Characteristic;
use App\Entity\ProductAttributeValue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:characteristics:merge-duplicates', description: 'Fusionne les caractéristiques en double (même nom, à la normalisation près)')]
class MergeDuplicateCharacteristicsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Applique réellement la fusion (sans cette option : simulation uniquement)');
    }

    private function normalize(string $name): string
    {
        $name = str_replace(['’', '`'], "'", $name);
        $name = preg_replace('/\s+/u', ' ', $name);

        return mb_strtolower(trim($name));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = $input->getOption('apply');

        if (!$apply) {
            $io->warning('Mode simulation (aucune modification appliquée). Relance avec --apply pour fusionner réellement.');
        }

        $all = $this->em->getRepository(Characteristic::class)->findBy([], ['id' => 'ASC']);

        $groups = [];
        foreach ($all as $characteristic) {
            $key = $this->normalize($characteristic->getName());
            $groups[$key][] = $characteristic;
        }

        $mergedGroups = 0;
        $deletedCharacteristics = 0;
        $movedValues = 0;
        $deletedCategoryAttributes = 0;

        foreach ($groups as $normalizedName => $characteristics) {
            if (count($characteristics) < 2) {
                continue;
            }

            $keeper = $characteristics[0]; // le plus ancien (id le plus petit)
            $losers = array_slice($characteristics, 1);

            $io->section('Doublon détecté : "' . $keeper->getName() . '" (garde #' . $keeper->getId() . ')');
            foreach ($losers as $loser) {
                $io->text('  — fusion de #' . $loser->getId() . ' ("' . $loser->getName() . '")');
            }
            $mergedGroups++;

            foreach ($losers as $loser) {
                $loserCategoryAttributes = $this->em->getRepository(CategoryAttribute::class)->findBy(['characteristic' => $loser]);

                foreach ($loserCategoryAttributes as $loserCa) {
                    $category = $loserCa->getCategory();
                    $keeperCa = $this->em->getRepository(CategoryAttribute::class)->findOneBy([
                        'category' => $category,
                        'characteristic' => $keeper,
                    ]);

                    if ($keeperCa) {
                        $loserValues = $this->em->getRepository(ProductAttributeValue::class)->findBy(['categoryAttribute' => $loserCa]);
                        foreach ($loserValues as $value) {
                            $existingOnKeeper = $this->em->getRepository(ProductAttributeValue::class)->findOneBy([
                                'product' => $value->getProduct(),
                                'categoryAttribute' => $keeperCa,
                            ]);
                            if ($existingOnKeeper) {
                                if ($apply) {
                                    $this->em->remove($value);
                                }
                            } else {
                                if ($apply) {
                                    $value->setCategoryAttribute($keeperCa);
                                }
                                $movedValues++;
                            }
                        }
                        if ($apply) {
                            $this->em->remove($loserCa);
                        }
                        $deletedCategoryAttributes++;
                    } else {
                        if ($apply) {
                            $loserCa->setCharacteristic($keeper);
                        }
                    }
                }

                if ($apply) {
                    $this->em->remove($loser);
                }
                $deletedCharacteristics++;
            }
        }

        if ($apply) {
            $this->em->flush();
        }

        $io->success(sprintf(
            '%d groupe(s) de doublons — %d caractéristique(s) %s, %d CategoryAttribute %s, %d valeur(s) produit %s.',
            $mergedGroups,
            $deletedCharacteristics,
            $apply ? 'supprimée(s)' : 'à supprimer',
            $deletedCategoryAttributes,
            $apply ? 'supprimé(s)' : 'à supprimer',
            $movedValues,
            $apply ? 'déplacée(s)' : 'à déplacer'
        ));

        return Command::SUCCESS;
    }
}