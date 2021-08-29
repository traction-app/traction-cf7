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
        $this->fetchRepositoryInfo();
        $this->fetchLocalInfo();
        add_filter('site_transient_update_plugins', array( $this, 'update' ));
    }


    private function fetchRepositoryInfo()
    {
        $data = get_file_data('https://raw.githubusercontent.com/traction-app/traction-cf7/main/readme.txt', ['Stable tag', 'Tested up to', 'Requires at least']);

        $this->plugin_slug = 'traction-cf7';
        $this->latest_version = $data[0];
        $this->tested_up = $data[1];
        $this->requires = $data[2];
        $this->latest_download_url = 'https://github.com/traction-app/traction-cf7/releases/download/' . $this->latest_version . '/traction-cf7.zip';
    }


    private function fetchLocalInfo()
    {
        $data = get_file_data(dirname(__FILE__) . '/../readme.txt', ['Stable tag', 'Tested up to', 'Requires at least']);
        $this->current_version = $data[0];
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
            $transient->response[ $res->plugin ] = $res;
        }

        return $transient;
    }
}

new TractionUpdater();
