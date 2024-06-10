<?php

namespace App\Services;

class OltHelper
{
    public static function parseOnuAutoFindOutput($output)
    {
        $lines = explode("\n", $output);
        $onus = [];

        foreach ($lines as $line) {
            // Use regex to match the expected format and capture groups
            if (preg_match('/^(GPON\d+\/\d+:\d+)\s+(\S+)\s+(\S+)$/', trim($line), $matches)) {
                $onus[] = [
                    'OnuIndex' => $matches[1],
                    'Sn' => $matches[2],
                    'State' => $matches[3],
                ];
            }
        }

        return $onus;
    }
}
