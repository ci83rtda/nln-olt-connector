<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OltHelper
{
    public static function parseOnuAutoFindOutput($output)
    {
        $lines = explode("\n", $output);
        $onus = [];

        foreach ($lines as $line) {
            // Use regex to match the expected format and capture groups
            if (preg_match('/^(GPON\d+\/\d+:\d+)\s+(\S+)\s+(\S+)$/', trim($line), $matches)) {
                // Log the matched groups for debugging
                Log::info('Matched line:', $matches);

                $onus[] = [
                    'OnuIndex' => trim($matches[1]),
                    'Sn' => trim($matches[2]),
                    'State' => trim($matches[3]),
                ];
            } else {
                // Log the unmatched lines for debugging
                Log::info('Unmatched line:', ['line' => $line]);
            }
        }

        return $onus;
    }
}
