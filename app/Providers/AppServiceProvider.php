<?php

namespace App\Providers;

use App\Models\AppNotification;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\DisbursementPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeadPolicy;
use App\Policies\LoanApplicationPolicy;
use App\Policies\PaymentPolicy;
use App\Services\NotificationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->registerGates();
        $this->registerBladeDirectives();
        $this->registerViewComposers();

        // An admin bypasses every policy; module access is still enforced by
        // the `permission:` middleware and by query scopes on listings.
        Gate::before(function (User $user, string $ability) {
            return $user->isAdmin() ? true : null;
        });

        Gate::define('manage-masters', fn (User $user) => $user->hasPermissionTo('masters.manage'));
        Gate::define('manage-employees', fn (User $user) => $user->hasPermissionTo('employees.manage'));
        Gate::define('manage-settings', fn (User $user) => $user->hasPermissionTo('settings.manage'));
        Gate::define('view-reports', fn (User $user) => $user->hasPermissionTo('reports.view'));
        Gate::define('verify-documents', fn (User $user) => $user->hasPermissionTo('documents.verify'));
        Gate::define('approve-applications', fn (User $user) => $user->hasPermissionTo('applications.approve'));
    }

    protected function registerGates(): void
    {
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(\App\Models\Customer::class, CustomerPolicy::class);
        Gate::policy(\App\Models\LoanApplication::class, LoanApplicationPolicy::class);
        Gate::policy(\App\Models\InsuranceApplication::class, LoanApplicationPolicy::class);
        Gate::policy(\App\Models\Invoice::class, InvoicePolicy::class);
        Gate::policy(\App\Models\Payment::class, PaymentPolicy::class);
        Gate::policy(\App\Models\Disbursement::class, DisbursementPolicy::class);
        Gate::policy(\App\Models\Document::class, DocumentPolicy::class);
        Gate::policy(\App\Models\Employee::class, EmployeePolicy::class);
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('money', fn ($expression) => "<?php echo \App\Support\Format::money({$expression}); ?>");
        Blade::directive('compactMoney', fn ($expression) => "<?php echo \App\Support\Format::compactInr({$expression}); ?>");
        Blade::directive('badge', fn ($expression) => "<?php echo \App\Support\StatusBadge::color({$expression}); ?>");

        Blade::if('canPermission', fn (string $slug) => auth()->check() && auth()->user()->hasPermissionTo($slug));
        Blade::if('admin', fn () => auth()->check() && auth()->user()->isAdmin());
    }

    protected function registerViewComposers(): void
    {
        View::composer(['layouts.app', 'partials.topbar', 'partials.sidebar'], function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            static $payload = null;

            if ($payload === null) {
                $notifications = app(NotificationService::class)->recentFor($user, 6);

                $payload = [
                    'navNotifications' => $notifications,
                    'navUnreadCount' => AppNotification::query()
                        ->where('notifiable_type', User::class)
                        ->where('notifiable_id', $user->id)
                        ->whereNull('read_at')
                        ->count(),
                    'navProducts' => Product::query()->active()->ordered()->get(),
                    'navDraftLeads' => Lead::query()->ownedBy($user)->draft()->count(),
                    'companyName' => Setting::get('company_name', config('app.name')),
                ];
            }

            $view->with($payload);
        });
    }
}
