<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /** permission slug => [label, module, group] */
    public const PERMISSIONS = [
        'dashboard.view' => ['View Dashboard', 'dashboard', 'core'],

        'leads.view' => ['View Leads', 'leads', 'sales'],
        'leads.create' => ['Create Leads', 'leads', 'sales'],
        'leads.update' => ['Update Leads', 'leads', 'sales'],
        'leads.delete' => ['Delete Leads', 'leads', 'sales'],
        'leads.assign' => ['Assign Leads', 'leads', 'sales'],
        'leads.export' => ['Export Leads', 'leads', 'sales'],

        'customers.view' => ['View Customers', 'customers', 'sales'],
        'customers.create' => ['Create Customers', 'customers', 'sales'],
        'customers.update' => ['Update Customers', 'customers', 'sales'],
        'customers.delete' => ['Delete Customers', 'customers', 'sales'],
        'customers.export' => ['Export Customers', 'customers', 'sales'],

        'applications.view' => ['View Applications', 'applications', 'operations'],
        'applications.create' => ['Create Applications', 'applications', 'operations'],
        'applications.update' => ['Update Applications', 'applications', 'operations'],
        'applications.delete' => ['Delete Applications', 'applications', 'operations'],
        'applications.approve' => ['Approve / Reject Applications', 'applications', 'operations'],

        'lenders.view' => ['View Lenders', 'lenders', 'operations'],
        'lenders.manage' => ['Manage Lenders & Products', 'lenders', 'operations'],

        'documents.upload' => ['Upload Documents', 'documents', 'operations'],
        'documents.verify' => ['Verify Documents', 'documents', 'operations'],
        'documents.delete' => ['Delete Documents', 'documents', 'operations'],

        'disbursements.view' => ['View Disbursements', 'disbursements', 'finance'],
        'disbursements.manage' => ['Manage Disbursements', 'disbursements', 'finance'],

        'invoices.view' => ['View Invoices', 'invoices', 'finance'],
        'invoices.manage' => ['Manage Invoices', 'invoices', 'finance'],

        'payments.view' => ['View Payments', 'payments', 'finance'],
        'payments.manage' => ['Manage Payments', 'payments', 'finance'],

        'insurance.view' => ['View Insurance', 'insurance', 'operations'],
        'insurance.manage' => ['Manage Insurance', 'insurance', 'operations'],

        'reports.view' => ['View Reports', 'reports', 'analytics'],
        'reports.export' => ['Export Reports', 'reports', 'analytics'],

        'employees.view' => ['View Employees', 'employees', 'admin'],
        'employees.manage' => ['Manage Employees', 'employees', 'admin'],

        'attendance.view' => ['View Attendance', 'attendance', 'hr'],
        'attendance.manage' => ['Manage Attendance', 'attendance', 'hr'],
        'attendance.mark' => ['Mark Own Attendance', 'attendance', 'hr'],

        'masters.view' => ['View Master Data', 'masters', 'admin'],
        'masters.manage' => ['Manage Master Data', 'masters', 'admin'],

        'settings.manage' => ['Manage Settings', 'settings', 'admin'],
        'audit.view' => ['View Audit Log', 'audit', 'admin'],
        'notifications.view' => ['View Notifications', 'notifications', 'core'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $slug => [$name, $module, $group]) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'module' => $module, 'group' => $group]
            );
        }

        $admin = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            [
                'name' => 'Administrator',
                'description' => 'Full access to every module, master and configuration.',
                'color' => 'primary',
                'is_system' => true,
                'is_active' => true,
                'level' => 1,
            ]
        );

        $employee = Role::query()->updateOrCreate(
            ['slug' => Role::EMPLOYEE],
            [
                'name' => 'Employee',
                'description' => 'Sales / operations user with access to assigned leads and customers.',
                'color' => 'success',
                'is_system' => true,
                'is_active' => true,
                'level' => 5,
            ]
        );

        $admin->syncPermissionSlugs(array_keys(self::PERMISSIONS));

        $employee->syncPermissionSlugs([
            'dashboard.view',
            'leads.view', 'leads.create', 'leads.update', 'leads.assign', 'leads.export',
            'customers.view', 'customers.create', 'customers.update', 'customers.export',
            'applications.view', 'applications.create', 'applications.update',
            'lenders.view',
            'documents.upload',
            'insurance.view', 'insurance.manage',
            'invoices.view', 'payments.view',
            'disbursements.view',
            'reports.view', 'reports.export',
            'attendance.view', 'attendance.mark',
            'notifications.view',
            'masters.view',
        ]);
    }
}
