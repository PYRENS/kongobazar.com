<?php

namespace App\Uploader\Namer;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

class CategoryIllustrationNamer implements NamerInterface
{
    public function name(object $object, PropertyMapping $mapping): string
    {
        $file = $mapping->getFile($object);
        $extension = $file ? $file->guessExtension() : 'bin';

        $slugger = new AsciiSlugger();
        $nameSlug = (method_exists($object, 'getName') && $object->getName())
            ? strtolower($slugger->slug($object->getName()))
            : 'sans-nom';

        return sprintf('illustration_%s_%s.%s', $nameSlug, uniqid(), $extension);
    }
}