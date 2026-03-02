<?php

declare(strict_types=1);

namespace App\Application\Content\Service;

use App\Domain\Content\Entity\Rule;
use App\Domain\Content\Repository\RuleRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class RuleService
{
    public function __construct(
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Create a new rule pre-filled with the content of the latest rule.
     */
    public function createNewRule(): Rule
    {
        $rule = new Rule();

        $latestRule = $this->ruleRepository->findLatest();

        if ($latestRule instanceof Rule) {
            $rule->setContent($latestRule->getContent());
            $rule->setContentEn($latestRule->getContentEn());
        }

        return $rule;
    }

    /**
     * Persist a new rule and archive (soft-delete) the previous one.
     */
    public function saveAndArchivePrevious(Rule $newRule): void
    {
        $latestRule = $this->ruleRepository->findLatest();

        $this->entityManager->persist($newRule);
        $this->entityManager->flush();

        if ($latestRule instanceof Rule && $latestRule->getId() !== $newRule->getId()) {
            $this->entityManager->remove($latestRule);
            $this->entityManager->flush();
        }
    }
}
