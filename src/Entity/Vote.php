<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Api\Processor\VoteDeleteProcessor;
use App\Api\Processor\VoteWriteProcessor;
use App\Entity\Traits\Set\BlameableEntity;
use App\Entity\Traits\Single\BoolActiveTrait;
use App\Entity\Traits\Single\UuidTrait;
use App\Repository\VoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VoteRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_comment_voter', columns: ['comment_id','voter_id'])]
#[API\ApiResource(
    mercure: true,
    normalizationContext: ['groups' => ['vote:read']],
    denormalizationContext: ['groups' => ['vote:write']],
    operations: [
        new API\Post(
            security: "is_granted('ROLE_USER')",
            processor: VoteWriteProcessor::class
        ),
        new API\Patch(
            security: "object.getVoter() == user",
            processor: VoteWriteProcessor::class
        ),
        new API\Delete(
            security: "object.getVoter() == user or is_granted('ROLE_MODERATOR')",
            processor: VoteDeleteProcessor::class
        ),
        new API\Get(security: "is_granted('ROLE_MODERATOR')"),
        new API\GetCollection(security: "is_granted('ROLE_MODERATOR')"),
    ]
)]
class Vote
{
    use UuidTrait;
    use BlameableEntity;
    use BoolActiveTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['vote:read','vote:write'])]
    private ?Comment $comment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['vote:read'])]
    private ?\App\Entity\User $voter = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\Choice(choices: [-1, 1])]
    #[Groups(['vote:read','vote:write'])]
    private int $value = 1;

    public function getComment(): ?Comment
    {
        return $this->comment;
    }
    public function setComment(Comment $c): self
    {
        $this->comment = $c;
        return $this;
    }
    public function getVoter(): ?\App\Entity\User
    {
        return $this->voter;
    }
    public function setVoter(\App\Entity\User $u): self
    {
        $this->voter = $u;
        return $this;
    }
    public function getValue(): int
    {
        return $this->value;
    }
    public function setValue(int $v): self
    {
        $this->value = $v;
        return $this;
    }
}
