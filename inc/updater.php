<?php

if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

class TractionUpdater
{
    private $plugin_slug;
    private $current_version;
    private $latest_version;
    private $tested_up;
    private $requires;
    private $latest_download_url;

    public function __construct()
    {
        try {
            $this->fetchLocalInfo();
            $this->fetchRepositoryInfo();
            add_filter('site_transient_update_plugins', array($this, 'update'));
        } catch (Exception $e) {
            error_log('TractionUpdater error: ' . $e->getMessage());
        }
    }

    private function fetchRepositoryInfo()
    {
        $response = wp_remote_get('https://raw.githubusercontent.com/traction-app/traction-cf7/main/readme.txt');
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch repository info.');
        }

        $data = $this->parse_readme($response['body']);
        $this->plugin_slug = 'traction-cf7';
        $this->latest_version = $data['Stable tag'];
        $this->tested_up = $data['Tested up to'];
        $this->requires = $data['Requires at least'];
        $this->latest_download_url = 'https://github.com/traction-app/traction-cf7/releases/download/' . $this->latest_version . '/traction-cf7.zip';
    }

    private function fetchLocalInfo()
    {
        $data = get_file_data(plugin_dir_path(__FILE__) . '/../readme.txt', [
            'Stable tag' => 'Stable tag',
            'Tested up to' => 'Tested up to',
            'Requires at least' => 'Requires at least',
        ]);

        $this->current_version = $data['Stable tag'];
    }

    private function parse_readme($content)
    {
        $data = [];
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (strpos($line, 'Stable tag:') !== false) {
                $data['Stable tag'] = trim(str_replace('Stable tag:', '', $line));
            } elseif (strpos($line, 'Tested up to:') !== false) {
                $data['Tested up to'] = trim(str_replace('Tested up to:', '', $line));
            } elseif (strpos($line, 'Requires at least:') !== false) {
                $data['Requires at least'] = trim(str_replace('Requires at least:', '', $line));
            }
        }
        return $data;
    }

    public function update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        if (version_compare($this->latest_version, $this->current_version, '>')) {
            $res = new stdClass();
            $res->slug = $this->plugin_slug;
            $res->plugin = PLUGIN_BASE_FILE;
            $res->new_version = $this->latest_version;
            $res->tested = $this->tested_up;
            $res->package = $this->latest_download_url;
            $transient->response[$res->plugin] = $res;
        }

        return $transient;
    }
}

new TractionUpdater();