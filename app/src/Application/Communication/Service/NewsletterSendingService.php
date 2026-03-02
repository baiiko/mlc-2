<?php

declare(strict_types=1);

namespace App\Application\Communication\Service;

use App\Domain\Communication\Entity\Newsletter;
use App\Domain\Communication\Repository\NewsletterRepositoryInterface;
use App\Domain\Player\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class NewsletterSendingService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private EntityManagerInterface $entityManager,
        private NewsletterRepositoryInterface $newsletterRepository,
    ) {
    }

    public function send(Newsletter $newsletter): int
    {
        $players = $this->entityManager
            ->getRepository(Player::class)
            ->findBy(['newsletter' => true, 'isActive' => true]);

        $html = $this->twig->render('emails/communication/newsletter.html.twig', [
            'newsletter' => $newsletter,
        ]);

        $count = 0;

        foreach ($players as $player) {
            $email = (new Email())
                ->from('modlapchampionship@gmail.com')
                ->to($player->getEmail())
                ->subject($newsletter->getSubject())
                ->html($html);

            $this->mailer->send($email);
            ++$count;
        }

        $newsletter->markAsSent($count);
        $this->newsletterRepository->save($newsletter);

        return $count;
    }
}
