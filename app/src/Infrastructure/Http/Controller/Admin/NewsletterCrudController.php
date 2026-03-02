<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Communication\Service\NewsletterSendingService;
use App\Domain\Communication\Entity\Newsletter;
use App\Domain\Player\Entity\Player;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class NewsletterCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly NewsletterSendingService $newsletterSendingService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Newsletter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.newsletter.singular')
            ->setEntityLabelInPlural('admin.newsletter.plural')
            ->setSearchFields(['subject'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendAction = Action::new('sendNewsletter', 'admin.newsletter.action.send', 'fa fa-paper-plane')
            ->linkToCrudAction('sendNewsletter')
            ->displayIf(fn (Newsletter $newsletter): bool => !$newsletter->isSent())
            ->addCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $sendAction)
            ->disable(Action::EDIT, Action::DELETE)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::NEW]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('subject', 'admin.newsletter.subject');

        if ($pageName === Crud::PAGE_DETAIL) {
            yield TextareaField::new('content', 'admin.newsletter.content')
                ->renderAsHtml();
        } elseif ($pageName === Crud::PAGE_NEW) {
            yield TextareaField::new('content', 'admin.newsletter.content')
                ->setFormTypeOption('attr', ['data-summernote-editor' => 'true'])
                ->addCssFiles('https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-lite.min.css')
                ->addJsFiles(
                    'https://code.jquery.com/jquery-3.7.1.slim.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-lite.min.js',
                )
                ->addHtmlContentsToHead($this->getSummernoteDarkThemeCss())
                ->addHtmlContentsToBody($this->getSummernoteInitScript());
        }

        yield AssociationField::new('sentBy', 'admin.newsletter.sent_by')
            ->hideOnForm();

        yield DateTimeField::new('sentAt', 'admin.newsletter.sent_at')
            ->hideOnForm();

        yield IntegerField::new('recipientCount', 'admin.newsletter.recipient_count')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'admin.newsletter.created_at')
            ->hideOnForm();
    }

    public function createEntity(string $entityFqcn): Newsletter
    {
        $newsletter = new Newsletter();

        /** @var Player $player */
        $player = $this->getUser();
        $newsletter->setSentBy($player);

        return $newsletter;
    }

    public function sendNewsletter(): Response
    {
        /** @var Newsletter $newsletter */
        $newsletter = $this->getContext()->getEntity()->getInstance();

        $count = $this->newsletterSendingService->send($newsletter);

        $this->addFlash('success', \sprintf('Newsletter envoyée à %d destinataire(s).', $count));

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($newsletter->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    private function getSummernoteDarkThemeCss(): string
    {
        return <<<'HTML'
        <style>
            .note-editor.note-frame {
                border-color: #3d4148 !important;
                border-radius: 6px;
                overflow: hidden;
            }
            .note-editor .note-toolbar {
                background-color: #2a2d32 !important;
                border-bottom: 1px solid #3d4148 !important;
                padding: 5px !important;
            }
            .note-editor .note-toolbar .note-btn {
                background-color: #1a1c1f !important;
                border-color: #3d4148 !important;
                color: #c9d1d9 !important;
                font-size: 13px;
                padding: 4px 8px;
            }
            .note-editor .note-toolbar .note-btn:hover,
            .note-editor .note-toolbar .note-btn.active {
                background-color: #3d4148 !important;
                color: #fff !important;
            }
            .note-editor .note-editing-area .note-editable {
                background-color: #1a1c1f !important;
                color: #c9d1d9 !important;
                min-height: 300px;
                padding: 15px;
            }
            .note-editor .note-statusbar {
                background-color: #2a2d32 !important;
                border-top: 1px solid #3d4148 !important;
            }
            .note-editor .note-statusbar .note-resizebar .note-icon-bar {
                border-top-color: #3d4148 !important;
            }
            .note-dropdown-menu {
                background-color: #2a2d32 !important;
                border-color: #3d4148 !important;
            }
            .note-dropdown-menu .note-dropdown-item:hover {
                background-color: #3d4148 !important;
            }
            .note-dropdown-menu .note-dropdown-item,
            .note-dropdown-menu .note-palette .note-color-name {
                color: #c9d1d9 !important;
            }
            .note-modal-content {
                background-color: #2a2d32 !important;
                border-color: #3d4148 !important;
                color: #c9d1d9 !important;
            }
            .note-modal-content .note-modal-header {
                border-bottom-color: #3d4148 !important;
            }
            .note-modal-content .note-modal-footer {
                border-top-color: #3d4148 !important;
            }
            .note-modal-content .note-input {
                background-color: #1a1c1f !important;
                border-color: #3d4148 !important;
                color: #c9d1d9 !important;
            }
            .note-modal-content .note-modal-title {
                color: #c9d1d9 !important;
            }
            .note-editor .note-toolbar .note-color-all .note-dropdown-menu {
                padding: 5px !important;
            }
            .note-placeholder {
                color: #6c757d !important;
            }
        </style>
        HTML;
    }

    private function getSummernoteInitScript(): string
    {
        return <<<'HTML'
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const textarea = document.querySelector('[data-summernote-editor]');
                if (!textarea || typeof jQuery === 'undefined' || typeof jQuery.fn.summernote === 'undefined') {
                    return;
                }

                jQuery(textarea).summernote({
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link']],
                    ],
                    styleTags: ['p', 'h1', 'h2', 'h3', 'h4'],
                    height: 300,
                    disableDragAndDrop: true,
                    callbacks: {
                        onPaste: function(e) {
                            const clipboardData = e.originalEvent.clipboardData;
                            if (!clipboardData) return;

                            const items = clipboardData.items;
                            for (let i = 0; i < items.length; i++) {
                                if (items[i].type.indexOf('image') !== -1) {
                                    e.preventDefault();
                                    return;
                                }
                            }
                        }
                    }
                });
            });
        </script>
        HTML;
    }
}
