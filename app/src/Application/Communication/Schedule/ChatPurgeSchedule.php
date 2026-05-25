<?php

declare(strict_types=1);

namespace App\Application\Communication\Schedule;

use App\Application\Communication\Message\PurgeChatMessagesMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('default')]
final class ChatPurgeSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::cron('0 * * * *', new PurgeChatMessagesMessage()));
    }
}
