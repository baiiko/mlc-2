<?php

declare(strict_types=1);

namespace App\Domain\Championship\Validator;

use App\Domain\Championship\Entity\RoundMap;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueSurpriseMapValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueSurpriseMap) {
            throw new UnexpectedTypeException($constraint, UniqueSurpriseMap::class);
        }

        if (!$value instanceof RoundMap) {
            throw new UnexpectedValueException($value, RoundMap::class);
        }

        if (!$value->isSurprise() || $value->getRound() === null) {
            return;
        }

        $existingSurprise = $this->entityManager->getRepository(RoundMap::class)
            ->findOneBy([
                'round' => $value->getRound(),
                'isSurprise' => true,
            ]);

        if ($existingSurprise !== null && $existingSurprise->getId() !== $value->getId()) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
