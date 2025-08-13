<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Api\Processor\CommentWriteProcessor;
use App\Entity\Traits\Set\BlameableEntity;
use App\Entity\Traits\Single\BoolActiveTrait;
use App\Entity\Traits\Single\UuidTrait;
use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Index(columns: ['created_at'])]
#[API\ApiResource(
    mercure: true,
    normalizationContext: ['groups' => ['comment:read']],
    denormalizationContext: ['groups' => ['comment:write']],
    operations: [
        new API\Get(security: "is_granted('ROLE_USER')"),
        new API\GetCollection(security: "is_granted('ROLE_USER')"),
        new API\Post(
            security: "is_granted('ROLE_USER')",
            processor: CommentWriteProcessor::class
        ),
        new API\Patch(
            security: "object.getAuthor() == user or is_granted('ROLE_MODERATOR')"
        ),
        new API\Delete(
            security: "object.getAuthor() == user or is_granted('ROLE_MODERATOR')"
        ),
    ]
)]
#[API\ApiFilter(SearchFilter::class, properties: [
    'thread.id' => 'exact',
    'parent.id' => 'exact',
])]
#[API\ApiFilter(OrderFilter::class, properties: ['createdAt' => 'ASC','id' => 'ASC'])]
class Comment
{
    use UuidTrait;
    use BlameableEntity;
    use BoolActiveTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read','comment:write'])]
    private ?Thread $thread = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[Groups(['comment:read','comment:write'])]
    private ?self $parent = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['comment:read','comment:write'])]
    private string $content = '';

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    #[Groups(['comment:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read'])]
    private ?\App\Entity\User $author = null;

    // Counter Caches (werden von Listener aktualisiert)
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['comment:read'])]
    private int $upCount = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['comment:read'])]
    private int $downCount = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['comment:read'])]
    private int $score = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getThread(): ?Thread
    {
        return $this->thread;
    }
    public function setThread(Thread $t): self
    {
        $this->thread = $t;
        return $this;
    }
    public function getParent(): ?self
    {
        return $this->parent;
    }
    public function setParent(?self $p): self
    {
        $this->parent = $p;
        return $this;
    }
    public function getContent(): string
    {
        return $this->content;
    }
    public function setContent(string $v): self
    {
        $this->content = $v;
        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getAuthor(): ?\App\Entity\User
    {
        return $this->author;
    }
    public function setAuthor(\App\Entity\User $u): self
    {
        $this->author = $u;
        return $this;
    }
    public function getUpCount(): int
    {
        return $this->upCount;
    }
    public function setUpCount(int $v): self
    {
        $this->upCount = $v;
        return $this;
    }
    public function getDownCount(): int
    {
        return $this->downCount;
    }
    public function setDownCount(int $v): self
    {
        $this->downCount = $v;
        return $this;
    }
    public function getScore(): int
    {
        return $this->score;
    }
    public function setScore(int $v): self
    {
        $this->score = $v;
        return $this;
    }
}
