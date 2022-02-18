<?php

class TractionChannel
{
    private $channels = [];

    private $channel;

    private $paths = [];

    public function __construct($referer, $entrypoint)
    {
        $this->paths = [
          $referer, $entrypoint
        ];
        $this->init();
    }

    public function loadChannel ()
    {
      $db = json_decode(file_get_contents(__DIR__ . '/channels.json'), true);
      $order = array_column($db, 'order');
      array_multisort($order, SORT_ASC, $db);
      return $db;
    }

    private function init()
    {
      $this->channels = $this->loadChannel();
      $this->searchForPattern();
    }

    private function searchForPattern()
    {
      foreach ($this->channels as $channel) {
            foreach ($this->paths as $path) {
                if (preg_match('/'.$channel['pattern'].'/i', $path)) {
                    $this->channel = $channel;
                    return true;
                }
                $this->channel = $channel;
            }
        }
        return false;
    }

    public function getChannel()
    {
        return $this->channel;
    }
}
