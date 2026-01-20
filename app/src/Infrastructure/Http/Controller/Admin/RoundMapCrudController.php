<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Championship\Service\GbxParserService;
use App\Domain\Championship\Entity\RoundMap;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoundMapCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly GbxParserService $gbxParser,
        #[Autowire('%kernel.project_dir%/public')] private readonly string $publicDir
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return RoundMap::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Map')
            ->setEntityLabelInPlural('Maps')
            ->setSearchFields(['name', 'uid', 'author'])
            ->setDefaultSort(['round.id' => 'DESC', 'isSurprise' => 'ASC', 'name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('round', 'Manche')
            ->autocomplete();

        // File upload field only on form
        yield Field::new('gbxFile', 'Fichier GBX')
            ->setFormType(FileType::class)
            ->setFormTypeOptions([
                'required' => false,
                'attr' => [
                    'accept' => '.gbx,.Gbx,.GBX,.Map.Gbx',
                ],
            ])
            ->setHelp('Importez un fichier .gbx pour extraire automatiquement les informations de la map')
            ->onlyOnForms();

        yield TextField::new('name', 'Nom de la map')
            ->setFormTypeOption('disabled', true)
            ->setHelp('Extrait automatiquement du fichier GBX')
            ->hideWhenCreating();

        yield TextField::new('uid', 'UID')
            ->setFormTypeOption('disabled', true)
            ->hideWhenCreating();

        yield TextField::new('author', 'Auteur')
            ->setFormTypeOption('disabled', true)
            ->hideWhenCreating();

        yield TextField::new('environment', 'Environnement')
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex()
            ->hideWhenCreating();

        yield TextField::new('formatAuthorTime', 'Temps auteur')
            ->hideOnForm();

        yield BooleanField::new('isSurprise', 'Map surprise')
            ->setHelp('Cocher si cette map est la map surprise de la finale');

        yield ImageField::new('thumbnailPath', 'Miniature')
            ->setBasePath('/uploads/maps/thumbnails')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();
    }

    public function createEntity(string $entityFqcn): RoundMap
    {
        return new RoundMap();
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->parseGbxFile($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->parseGbxFile($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function parseGbxFile(RoundMap $map): void
    {
        $file = $map->getGbxFile();
        if (!$file instanceof UploadedFile) {
            return;
        }

        $data = $this->gbxParser->parseFile($file->getPathname());
        if (!$data) {
            $this->addFlash('warning', 'Impossible de parser le fichier GBX');
            return;
        }

        if ($data->uid) {
            $map->setUid($data->uid);
        }
        if ($data->name) {
            $map->setName($data->name);
        }
        if ($data->author) {
            $map->setAuthor($data->author);
        }
        if ($data->environment) {
            $map->setEnvironment($data->environment);
        }
        if ($data->authorTime) {
            $map->setAuthorTime($data->authorTime);
        }
        if ($data->goldTime) {
            $map->setGoldTime($data->goldTime);
        }
        if ($data->silverTime) {
            $map->setSilverTime($data->silverTime);
        }
        if ($data->bronzeTime) {
            $map->setBronzeTime($data->bronzeTime);
        }

        // Save thumbnail if available
        if ($data->thumbnail && $data->uid) {
            $thumbnailPath = $this->saveThumbnail($data->thumbnail, $data->uid);
            if ($thumbnailPath) {
                $map->setThumbnailPath($thumbnailPath);
            }
        }

        // Clear the file reference (not persisted)
        $map->setGbxFile(null);

        $this->addFlash('success', sprintf('Map "%s" importée avec succès', $data->name ?? 'inconnue'));
    }

    private function saveThumbnail(string $base64Data, string $uid): ?string
    {
        $thumbnailDir = $this->publicDir . '/uploads/maps/thumbnails';

        // Create directory if it doesn't exist
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $filename = $uid . '.jpg';
        $filepath = $thumbnailDir . '/' . $filename;

        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            return null;
        }

        if (file_put_contents($filepath, $imageData) === false) {
            return null;
        }

        return $filename;
    }
}
