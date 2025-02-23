<?php

namespace App\Notifications\Application;

use App\Notifications\Channels\DiscordChannel;
use App\Notifications\Channels\EmailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class StatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public $application;

    public string $application_name;
    public string|null $application_url = null;
    public string $project_uuid;
    public string $environment_name;
    public string|null $fqdn;

    public function __construct($application)
    {
        $this->application = $application;
        $this->application_name = data_get($application, 'name');
        $this->project_uuid = data_get($application, 'environment.project.uuid');
        $this->environment_name = data_get($application, 'environment.name');
        $this->fqdn = data_get($application, 'fqdn', null);
        if (Str::of($this->fqdn)->explode(',')->count() > 1) {
            $this->fqdn = Str::of($this->fqdn)->explode(',')->first();
        }
        $this->application_url = base_url() . "/project/{$this->project_uuid}/{$this->environment_name}/application/{$this->application->uuid}";
    }

    public function via(object $notifiable): array
    {
        $channels = [];
        $isEmailEnabled = data_get($notifiable, 'smtp_enabled');
        $isDiscordEnabled = data_get($notifiable, 'discord_enabled');
        $isSubscribedToEmailEvent = data_get($notifiable, 'smtp_notifications_status_changes');
        $isSubscribedToDiscordEvent = data_get($notifiable, 'discord_notifications_status_changes');

        if ($isEmailEnabled && $isSubscribedToEmailEvent) {
            $channels[] = EmailChannel::class;
        }
        if ($isDiscordEnabled && $isSubscribedToDiscordEvent) {
            $channels[] = DiscordChannel::class;
        }
        return $channels;
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage();
        $fqdn = $this->fqdn;
        $mail->subject("⛔ {$this->application_name} has been stopped");
        $mail->view('emails.application-status-changes', [
            'name' => $this->application_name,
            'fqdn' => $fqdn,
            'application_url' => $this->application_url,
        ]);
        return $mail;
    }

    public function toDiscord(): string
    {
        $message = '⛔ ' . $this->application_name . ' has been stopped.

';
        $message .= '[Application URL](' . $this->application_url . ')';
        return $message;
    }
}
