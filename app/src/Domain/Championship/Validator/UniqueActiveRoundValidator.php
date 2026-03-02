<?php

declare(strict_types=1);

namespace App\Domain\Championship\Validator;

use App\Domain\Championship\Entity\Round;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueActiveRoundValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueActiveRound) {
            throw new UnexpectedTypeException($constraint, UniqueActiveRound::class);
        }

        if (!$value instanceof Round) {
            throw new UnexpectedValueException($value, Round::class);
        }

        if (!$value->isActive()) {
            return;
        }

        $existingActive = $this->entityManager->getRepository(Round::class)
            ->findOneBy(['isActive' => true]);

        if ($existingActive !== null && $existingActive->getId() !== $value->getId()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ round }}', $existingActive->getName() ?? '')
                ->addViolation();
        }
    }
}
