<?php
declare(strict_types=1);

namespace Test\Acceptance;

use DateTimeImmutable;
use LeanpubBookClub\Application\UpcomingSessions\CouldNotFindSession;
use LeanpubBookClub\Application\UpcomingSessions\Sessions;
use LeanpubBookClub\Application\UpcomingSessions\UpcomingSession;
use LeanpubBookClub\Application\UpcomingSessions\SessionForAdministrator;
use LeanpubBookClub\Domain\Model\Member\LeanpubInvoiceId;
use LeanpubBookClub\Domain\Model\Session\AttendeeCancelledTheirAttendance;
use LeanpubBookClub\Domain\Model\Session\AttendeeRegisteredForSession;
use LeanpubBookClub\Domain\Model\Session\SessionId;
use LeanpubBookClub\Domain\Model\Session\SessionWasPlanned;

final class UpcomingSessionsInMemory implements Sessions
{
    /**
     * @var array<string,UpcomingSession>
     */
    private array $sessions = [];

    /**
     * @var array<string,SessionForAdministrator>
     */
    private array $sessionsForAdministrator = [];

    /**
     * @var array<string,array<string,bool>>
     */
    private array $attendees = [];

    public function whenSessionWasPlanned(SessionWasPlanned $event): void
    {
        $this->sessions[$event->sessionId()->asString()] = new UpcomingSession(
            $event->sessionId()->asString(),
            $event->date()->asString(),
            $event->description(),
            false
        );

        $this->sessionsForAdministrator[$event->sessionId()->asString()] = new SessionForAdministrator(
            $event->sessionId()->asString(),
            $event->date()->asString(),
            $event->description(),
            $event->maximumNumberOfAttendees()
        );
    }

    public function whenAttendeeRegisteredForSession(AttendeeRegisteredForSession $event): void
    {
        $this->attendees[$event->sessionId()->asString()][$event->memberId()->asString()] = true;
    }

    public function whenAttendeeCancelledTheirAttendance(AttendeeCancelledTheirAttendance $event): void
    {
        $this->attendees[$event->sessionId()->asString()][$event->memberId()->asString()] = false;
    }

    public function upcomingSessions(DateTimeImmutable $currentTime, LeanpubInvoiceId $activeMemberId): array
    {
        return array_map(
            function (UpcomingSession $upcomingSession) use ($activeMemberId): UpcomingSession {
                if ($this->attendees[$upcomingSession->sessionId()][$activeMemberId->asString()] ?? false) {
                    return $upcomingSession->withActiveMemberRegisteredAsAttendee();
                }

                return $upcomingSession;
            },
            array_filter(
                $this->sessions,
                function (UpcomingSession $session) use ($currentTime): bool {
                    return $session->isToBeConsideredUpcoming($currentTime);
                }
            )
        );
    }

    public function upcomingSessionsForAdministrator(DateTimeImmutable $currentTime): array
    {
        return array_map(
            [$this, 'updateUpcomingSessionForAdministratorReadModel'],
            array_filter(
                $this->sessionsForAdministrator,
                function (SessionForAdministrator $session) use ($currentTime): bool {
                    return $session->isToBeConsideredUpcoming($currentTime);
                }
            )
        );
    }

    public function getSessionForAdministrator(SessionId $sessionId): SessionForAdministrator
    {
        if (!isset($this->sessionsForAdministrator[$sessionId->asString()])) {
            throw CouldNotFindSession::withId($sessionId);
        }

        return $this->updateUpcomingSessionForAdministratorReadModel(
            $this->sessionsForAdministrator[$sessionId->asString()]
        );
    }

    private function updateUpcomingSessionForAdministratorReadModel(
        SessionForAdministrator $upcomingSession
    ): SessionForAdministrator {
        $attendeesForSession = $this->attendees[$upcomingSession->sessionId()] ?? [];

        return $upcomingSession->withNumberOfAttendees(count($attendeesForSession));
    }
}
