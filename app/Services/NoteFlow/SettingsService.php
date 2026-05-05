<?php

namespace App\Services\NoteFlow;

use App\Models\User;
use App\Models\UserSetting;

class SettingsService
{
    public function getOrCreate(User $user): UserSetting
    {
        return UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'appearance' => $this->defaultAppearance(),
                'email_notifications' => $this->defaultEmailNotifications($user->email),
                'push_notifications' => $this->defaultPushNotifications(),
                'ai' => $this->defaultAiSettings(),
            ]
        );
    }

    public function updateAppearance(User $user, array $data): UserSetting
    {
        $settings = $this->getOrCreate($user);
        $settings->update(['appearance' => array_merge($this->defaultAppearance(), $settings->appearance ?? [], $data)]);

        return $settings;
    }

    public function updateNotifications(User $user, array $data): UserSetting
    {
        $settings = $this->getOrCreate($user);

        if (array_key_exists('email_notifications', $data)) {
            $settings->email_notifications = array_merge(
                $this->defaultEmailNotifications($user->email),
                $settings->email_notifications ?? [],
                $data['email_notifications']
            );
        }

        if (array_key_exists('push_notifications', $data)) {
            $settings->push_notifications = array_merge(
                $this->defaultPushNotifications(),
                $settings->push_notifications ?? [],
                $data['push_notifications']
            );
        }

        $settings->save();

        return $settings;
    }

    public function updateAi(User $user, array $data): UserSetting
    {
        $settings = $this->getOrCreate($user);
        $settings->update(['ai' => array_merge($this->defaultAiSettings(), $settings->ai ?? [], $data)]);

        return $settings;
    }

    private function defaultAppearance(): array
    {
        return [
            'theme' => 'Light',
            'compact_mode' => false,
            'show_animations' => true,
        ];
    }

    private function defaultEmailNotifications(string $email): array
    {
        return [
            'email_address' => $email,
            'daily_digest' => true,
            'weekly_digest' => true,
            'event_reminders' => true,
            'ai_completions' => false,
            'note_collaborations' => true,
        ];
    }

    private function defaultPushNotifications(): array
    {
        return [
            'shared_notes' => true,
            'comments' => true,
            'ai_generation_completions' => false,
            'system_updates' => true,
            'weekly_activity_summary' => true,
        ];
    }

    private function defaultAiSettings(): array
    {
        return [
            'default_language' => 'English',
            'tone' => 'Professional',
            'output_length' => 'Medium',
        ];
    }
}
