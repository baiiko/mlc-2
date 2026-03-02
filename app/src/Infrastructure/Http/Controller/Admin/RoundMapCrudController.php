<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Championship\Service\RoundMapService;
use App\Domain\Championship\Entity\RoundMap;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoundMapCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RoundMapService $roundMapService,
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

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleGbxImport($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleGbxImport($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function handleGbxImport(RoundMap $map): void
    {
        $file = $map->getGbxFile();

        if (!$file instanceof UploadedFile) {
            return;
        }

        $error = $this->roundMapService->importFromGbxFile($map, $file);

        if ($error) {
            $this->addFlash('warning', $error);
        } else {
            $this->addFlash('success', \sprintf('Map "%s" importée avec succès', $map->getName() ?? 'inconnue'));
        }
    }
}
