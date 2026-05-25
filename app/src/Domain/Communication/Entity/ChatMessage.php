<?php

declare(strict_types=1);

namespace App\Domain\Communication\Entity;

use App\Domain\Championship\Entity\Server;
use App\Domain\Communication\Enum\ChatMessageType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'chat_message')]
#[ORM\Index(name: 'idx_chat_message_server_created', columns: ['server_id', 'created_at'])]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Server::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Server $server;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $playerLogin = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $playerPseudo = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\Column(enumType: ChatMessageType::class)]
    private ChatMessageType $type;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Server $server,
        string $content,
        ChatMessageType $type,
        ?string $playerLogin = null,
        ?string $playerPseudo = null,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->server = $server;
        $this->content = $content;
        $this->type = $type;
        $this->playerLogin = $playerLogin;
        $this->playerPseudo = $playerPseudo;
        $this->createdAt = ($createdAt ?? new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getPlayerLogin(): ?string
    {
        return $this->playerLogin;
    }

    public function getPlayerPseudo(): ?string
    {
        return $this->playerPseudo;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): ChatMessageType
    {
        return $this->type;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        // The DATETIME column stores wall-clock UTC values, but Doctrine hydrates
        // them with PHP's default timezone. Re-tag (not convert) to UTC.
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $this->createdAt->format('Y-m-d H:i:s'),
            new \DateTimeZone('UTC'),
        ) ?: $this->createdAt;
    }
}
