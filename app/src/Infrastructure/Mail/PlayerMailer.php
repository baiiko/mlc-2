<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Application\Player\Notification\PlayerNotificationInterface;
use App\Domain\Player\Entity\Player;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class PlayerMailer implements PlayerNotificationInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private string $defaultLocale = 'fr',
    ) {
    }

    public function sendWelcomeEmail(Player $player, string $locale = null): void
    {
        $locale = $locale ?? $this->defaultLocale;

        $activationUrl = $this->urlGenerator->generate(
            'app_activate_account',
            ['token' => $player->getActivationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $html = $this->twig->render('emails/player/welcome.html.twig', [
            'player' => $player,
            'activationUrl' => $activationUrl,
            'locale' => $locale,
        ]);

        $subject = $this->translator->trans('email.welcome.subject', [], 'player', $locale);

        $email = (new Email())
            ->from('modlapchampionship@gmail.com')
            ->to($player->getEmail())
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(Player $player, string $locale = null): void
    {
        $locale = $locale ?? $this->defaultLocale;

        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $player->getResetPasswordToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $html = $this->twig->render('emails/player/reset_password.html.twig', [
            'player' => $player,
            'resetUrl' => $resetUrl,
            'locale' => $locale,
        ]);

        $subject = $this->translator->trans('email.reset_password.subject', [], 'player', $locale);

        $email = (new Email())
            ->from('modlapchampionship@gmail.com')
            ->to($player->getEmail())
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }
}
