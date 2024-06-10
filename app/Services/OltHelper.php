<?php

namespace App\Services;

class OltHelper
{
    public static function parseOnuAutoFindOutput($output)
    {
        $lines = explode("\n", $output);
        $onus = [];

        foreach ($lines as $line) {
            if (preg_match('/^GPON\d+\/\d+:\d+\s+\S+\s+\S+$/', trim($line))) {
                $parts = preg_split('/\s+/', $line);
                $onus[] = [
                    'OnuIndex' => $parts[0],
                    'Sn' => $parts[1],
                    'State' => $parts[2],
                ];
            }
        }

        return $onus;
    }
}
