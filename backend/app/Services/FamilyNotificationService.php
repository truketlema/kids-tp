<?php

namespace App\Services;

use App\Models\Family;
use App\Models\User;
use App\Notifications\FamilyEventNotification;

class FamilyNotificationService
{
    private const DEFAULT_PREFERENCES = [
        'points_recorded' => true,
        'activity_assignment_changed' => true,
        'family_access_changed' => true,
    ];

    public function notifyOtherParents(
        Family $family,
        User $actor,
        string $event,
        string $title,
        string $message,
        array $metadata = [],
    ): void {
        $family->users()
            ->where('id', '!=', $actor->id)
            ->get()
            ->each(function (User $recipient) use ($event, $title, $message, $metadata) {
                $preferences = array_replace(
                    self::DEFAULT_PREFERENCES,
                    $recipient->notification_preferences ?? [],
                );

                if ($preferences[$event] ?? false) {
                    $recipient->notify(new FamilyEventNotification(
                        $event,
                        $title,
                        $message,
                        $metadata,
                    ));
                }
            });
    }
}
