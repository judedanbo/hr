<?php

namespace App\Support;

use App\Notifications\PhotoApprovedNotification;
use App\Notifications\PhotoPendingApprovalNotification;
use App\Notifications\PhotoRejectedNotification;
use App\Notifications\QualificationPendingApprovalNotification;

class NotificationMeta
{
    /**
     * @return array<class-string, array{label: string, icon: string, url: \Closure}>
     */
    protected static function map(): array
    {
        return [
            PhotoPendingApprovalNotification::class => [
                'label' => 'Photo pending approval',
                'icon' => 'camera',
                'url' => fn (array $data): string => route('photo-approvals.index'),
            ],
            PhotoApprovedNotification::class => [
                'label' => 'Photo approved',
                'icon' => 'check-circle',
                'url' => fn (array $data): string => route('my-profile.show'),
            ],
            PhotoRejectedNotification::class => [
                'label' => 'Photo rejected',
                'icon' => 'x-circle',
                'url' => fn (array $data): string => route('my-profile.show'),
            ],
            QualificationPendingApprovalNotification::class => [
                'label' => 'Qualification pending approval',
                'icon' => 'academic-cap',
                'url' => fn (array $data): string => route('qualification.index'),
            ],
        ];
    }

    /**
     * Build a payload for a notification type, merging title/icon/url with event-specific data.
     *
     * @param  class-string  $type
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function for(string $type, array $data): array
    {
        $meta = static::map()[$type] ?? null;

        return array_merge($data, [
            'title' => $meta['label'] ?? class_basename($type),
            'icon' => $meta['icon'] ?? 'bell',
            'url' => $meta ? ($meta['url'])($data) : null,
        ]);
    }

    /**
     * Friendly label for a given notification type.
     *
     * @param  class-string  $type
     */
    public static function label(string $type): string
    {
        return static::map()[$type]['label'] ?? class_basename($type);
    }

    /**
     * Registered notification type keys.
     *
     * @return array<int, class-string>
     */
    public static function types(): array
    {
        return array_keys(static::map());
    }
}
