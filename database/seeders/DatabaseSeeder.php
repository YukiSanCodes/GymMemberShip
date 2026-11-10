<?php

namespace Database\Seeders;

use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['Student', 'For currently enrolled students with a valid student ID.'], ['Regular', 'Standard gym membership.'], ['Senior Citizen', 'For senior members.'], ['Corporate', 'Employees enrolled through a company wellness plan.']] as [$name,$description]) {
            MembershipCategory::firstOrCreate(['name' => $name], ['description' => $description]);
        } foreach ([['Basic', 1200, 1, 'Gym floor access and changing room access.'], ['Silver', 3200, 3, 'Gym floor access, group classes, and fitness orientation.'], ['Gold', 6000, 6, 'All Silver benefits, guest passes, and monthly trainer check-in.'], ['Platinum', 10800, 12, 'All Gold benefits, locker access, and a quarterly personal training session.']] as [$name,$fee,$months,$benefits]) {
            MembershipLevel::firstOrCreate(['name' => $name], ['fee' => $fee, 'validity_months' => $months, 'benefits' => $benefits]);
        }
    }
}
