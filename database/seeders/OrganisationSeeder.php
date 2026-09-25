<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrganisationSeeder extends Seeder
{
    public const DEPARTMENTS = [
        ['Sales', 'SALES', 'Lead generation, customer acquisition and closures.'],
        ['Credit & Operations', 'CREDIT', 'Verification, underwriting coordination and disbursal.'],
        ['Finance & Accounts', 'FIN', 'Invoicing, collections, payouts and reconciliation.'],
        ['Insurance Desk', 'INSU', 'Life, health and general insurance placement.'],
        ['Customer Support', 'SUPPORT', 'Post disbursal service and documentation support.'],
        ['Human Resources', 'HR', 'People operations, attendance and payroll inputs.'],
    ];

    public const DESIGNATIONS = [
        ['Sales Executive', 'SALES', 'Sales', 'L2'],
        ['Senior Sales Executive', 'SALES-SR', 'Sales', 'L3'],
        ['Sales Manager', 'SALES-MGR', 'Sales', 'L4'],
        ['Relationship Manager', 'REL-MGR', 'Credit & Operations', 'L3'],
        ['Credit Analyst', 'CREDIT-AN', 'Credit & Operations', 'L3'],
        ['Operations Executive', 'OPS-EX', 'Credit & Operations', 'L2'],
        ['Disbursement Officer', 'DISB-OFF', 'Finance & Accounts', 'L3'],
        ['Accounts Executive', 'ACC-EX', 'Finance & Accounts', 'L2'],
        ['Insurance Advisor', 'INSU-ADV', 'Insurance Desk', 'L2'],
        ['Support Executive', 'SUP-EX', 'Customer Support', 'L1'],
        ['HR Executive', 'HR-EX', 'Human Resources', 'L2'],
        ['Chief Operating Officer', 'COO', 'Credit & Operations', 'L1'],
    ];

    public const USERS = [
        [
            'name' => 'Rajesh Sharma', 'email' => 'admin@loanpro.in', 'phone' => '+91 9820011122',
            'role' => Role::ADMIN, 'designation' => 'Chief Operating Officer', 'department' => 'Credit & Operations',
            'code' => 'EMP1001', 'gender' => 'male', 'city' => 'Mumbai',
        ],
        [
            'name' => 'Neha Singh', 'email' => 'neha.singh@loanpro.in', 'phone' => '+91 9820033445',
            'role' => Role::EMPLOYEE, 'designation' => 'Sales Executive', 'department' => 'Sales',
            'code' => 'EMP1002', 'gender' => 'female', 'city' => 'Mumbai',
        ],
        [
            'name' => 'Amit Kumar', 'email' => 'amit.kumar@loanpro.in', 'phone' => '+91 9820055667',
            'role' => Role::EMPLOYEE, 'designation' => 'Senior Sales Executive', 'department' => 'Sales',
            'code' => 'EMP1003', 'gender' => 'male', 'city' => 'Thane',
        ],
        [
            'name' => 'Priya Desai', 'email' => 'priya.desai@loanpro.in', 'phone' => '+91 9820077889',
            'role' => Role::EMPLOYEE, 'designation' => 'Relationship Manager', 'department' => 'Credit & Operations',
            'code' => 'EMP1004', 'gender' => 'female', 'city' => 'Navi Mumbai',
        ],
        [
            'name' => 'Ravi Verma', 'email' => 'ravi.verma@loanpro.in', 'phone' => '+91 9820099001',
            'role' => Role::EMPLOYEE, 'designation' => 'Insurance Advisor', 'department' => 'Insurance Desk',
            'code' => 'EMP1005', 'gender' => 'male', 'city' => 'Mumbai',
        ],
        [
            'name' => 'Kavita Joshi', 'email' => 'kavita.joshi@loanpro.in', 'phone' => '+91 9820099002',
            'role' => Role::EMPLOYEE, 'designation' => 'Accounts Executive', 'department' => 'Finance & Accounts',
            'code' => 'EMP1006', 'gender' => 'female', 'city' => 'Pune',
        ],
        [
            'name' => 'Suresh Pillai', 'email' => 'suresh.pillai@loanpro.in', 'phone' => '+91 9820099003',
            'role' => Role::EMPLOYEE, 'designation' => 'Disbursement Officer', 'department' => 'Finance & Accounts',
            'code' => 'EMP1007', 'gender' => 'male', 'city' => 'Mumbai',
        ],
        [
            'name' => 'Anjali Mehta', 'email' => 'anjali.mehta@loanpro.in', 'phone' => '+91 9820099004',
            'role' => Role::EMPLOYEE, 'designation' => 'Support Executive', 'department' => 'Customer Support',
            'code' => 'EMP1008', 'gender' => 'female', 'city' => 'Mumbai',
        ],
    ];

    public function run(): void
    {
        foreach (self::DEPARTMENTS as $index => [$name, $code, $description]) {
            Department::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'description' => $description, 'is_active' => true, 'sort_order' => $index + 1]
            );
        }

        foreach (self::DESIGNATIONS as $index => [$name, $code, $department, $level]) {
            $departmentModel = Department::query()->where('name', $department)->first();

            Designation::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'department_id' => $departmentModel?->id,
                    'level' => $level,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        foreach (self::USERS as $index => $data) {
            $role = Role::query()->where('slug', $data['role'])->firstOrFail();
            $department = Department::query()->where('name', $data['department'])->first();
            $designation = Designation::query()->where('name', $data['designation'])->first();

            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'role_id' => $role->id,
                    'designation' => $data['designation'],
                    'department_id' => $department?->id,
                    'status' => 'active',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'must_change_password' => false,
                ]
            );

            Employee::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_code' => $data['code'],
                    'department_id' => $department?->id,
                    'designation_id' => $designation?->id,
                    'joining_date' => now()->subMonths(18 - $index)->startOfMonth(),
                    'employment_status' => 'active',
                    'mobile' => $data['phone'],
                    'city' => $data['city'],
                    'state' => 'Maharashtra',
                    'pincode' => '4000'.str_pad((string) (69 + $index), 2, '0', STR_PAD_LEFT),
                    'bank_name' => 'HDFC Bank',
                    'bank_account_number' => '50100'.str_pad((string) random_int(100000, 999999), 6, '0'),
                    'bank_ifsc' => 'HDFC0000123',
                    'monthly_target' => $data['role'] === Role::EMPLOYEE ? 1500000 : null,
                    'address' => 'Andheri East, Mumbai',
                ]
            );
        }
    }
}
