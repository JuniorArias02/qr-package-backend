<?php

namespace App\Services;

use App\Models\SystemSetting;

class SystemSettingService
{
    /**
     * Get the current assignment mode.
     */
    public function getAssignmentMode(): string
    {
        $setting = SystemSetting::first();
        return $setting ? $setting->assignment_mode : 'automatic';
    }

    /**
     * Update the assignment mode.
     */
    public function setAssignmentMode(string $mode): bool
    {
        if (!in_array($mode, ['automatic', 'assign_to'])) {
            return false;
        }

        $setting = SystemSetting::first();
        if ($setting) {
            return $setting->update(['assignment_mode' => $mode]);
        }

        return SystemSetting::create(['assignment_mode' => $mode]) instanceof SystemSetting;
    }
}
