<?php
declare(strict_types=1);

namespace LeanpubBookClub\Infrastructure\TalisOrm;

use Assert\Assert;
use LeanpubBookClub\Domain\Model\Member\LeanpubInvoiceId;
use LeanpubBookClub\Domain\Model\Member\Member;
use LeanpubBookClub\Domain\Model\Member\MemberRepository;
use LeanpubBookClub\Domain\Model\Purchase\CouldNotFindPurchase;
use TalisOrm\AggregateNotFoundException;
use TalisOrm\AggregateRepository;

final class MemberTalisOrmRepository implements MemberRepository
{
    /**
     * @var AggregateRepository
     */
    private AggregateRepository $aggregateRepository;

    public function __construct(AggregateRepository $aggregateRepository)
    {
        $this->aggregateRepository = $aggregateRepository;
    }

    public function save(Member $member): void
    {
        $this->aggregateRepository->save($member);
    }

    public function getById(LeanpubInvoiceId $invoiceId): Member
    {
        try {
            $member = $this->aggregateRepository->getById(
                Member::class,
                $invoiceId
            );
            Assert::that($member)->isInstanceOf(Member::class);
            /** @var Member $member */

            return $member;
        } catch (AggregateNotFoundException $exception) {
            throw CouldNotFindPurchase::withInvoiceId($invoiceId);
        }
    }
}
