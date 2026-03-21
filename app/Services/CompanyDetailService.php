<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;

class CompanyDetailService
{
    /**
     * Get comprehensive details for a company.
     *
     * @param int $companyId
     * @return array
     */
    public function getCompanyDetails(int $companyId): array
    {
        // Load company with general counts
        $company = Company::withCount(['users', 'coordinators', 'drivers', 'deliveries'])->findOrFail($companyId);
        
        // Load users from this company, with their specific role relationship if driver to get delivered packages count
        $users = User::where('company_id', $companyId)
            ->with(['driver' => function ($query) {
                $query->withCount(['deliveries as delivered_packages_count' => function ($q) {
                    $q->where('status', 'delivered');
                }]);
            }])
            ->get();

        // Group by role
        $groupedUsers = [
            'company_admin' => $users->where('role', 'company_admin')->values(),
            'coordinator' => $users->where('role', 'coordinator')->values(),
            'driver' => $users->where('role', 'driver')->values(),
        ];

        return [
            'company' => $company,
            'users' => $groupedUsers,
        ];
    }
}
