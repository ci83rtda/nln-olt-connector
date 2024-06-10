<?php

namespace App\Traits;

trait SshConnectionTrait
{
    protected $ssh;

    public function closeConnection()
    {
        if ($this->ssh) {
            $this->ssh->disconnect();
        }
    }
}
