<?php

namespace App\Http\Livewire\Source\Github;

use App\Models\GithubApp;
use Livewire\Component;

class Create extends Component
{
    public string $name;
    public string|null $organization = null;
    public string $api_url = 'https://api.github.com';
    public string $html_url = 'https://github.com';
    public string $custom_user = 'git';
    public int $custom_port = 22;
    public bool $is_system_wide = false;

    public function mount()
    {
        $this->name = generate_random_name();
    }

    public function createGitHubApp()
    {
        try {
            $this->validate([
                "name" => 'required|string',
                "organization" => 'nullable|string',
                "api_url" => 'required|string',
                "html_url" => 'required|string',
                "custom_user" => 'required|string',
                "custom_port" => 'required|int',
                "is_system_wide" => 'required|bool',
            ]);
            $github_app = GithubApp::create([
                'name' => $this->name,
                'organization' => $this->organization,
                'api_url' => $this->api_url,
                'html_url' => $this->html_url,
                'custom_user' => $this->custom_user,
                'custom_port' => $this->custom_port,
                'is_system_wide' => $this->is_system_wide,
                'team_id' => currentTeam()->id,
            ]);
            if (session('from')) {
                session(['from' => session('from') + ['source_id' => $github_app->id]]);
            }
            redirect()->route('source.github.show', ['github_app_uuid' => $github_app->uuid]);
        } catch (\Exception $e) {
            return general_error_handler(err: $e, that: $this);
        }
    }
}
