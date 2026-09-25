<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Registry driven CRUD for every master table declared in config/loanpro.php.
 * Adding a master never requires code changes here.
 */
class MasterController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('masters.view'), 403);

        $masters = config('loanpro.masters');

        $summary = collect($masters)->map(function (array $master, string $key) {
            return [
                'key' => $key,
                'label' => $master['label'],
                'singular' => $master['singular'],
                'icon' => $master['icon'],
                'count' => $master['model']::query()->count(),
            ];
        })->values();

        return view('masters.index', [
            'masters' => $summary,
            'stats' => [
                'masters' => $summary->count(),
                'records' => $summary->sum('count'),
                'active' => 0,
            ],
        ]);
    }

    public function show(Request $request, string $master): View|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('masters.view'), 403);

        $config = $this->config($master);
        $model = $config['model'];

        $query = $model::query()
            ->when($request->filled('q'), function ($q) use ($request, $config) {
                $term = $request->string('q')->toString();
                $q->where(function ($sub) use ($term, $config) {
                    foreach ($config['searchable'] as $index => $column) {
                        $index === 0
                            ? $sub->where($column, 'like', "%{$term}%")
                            : $sub->orWhere($column, 'like', "%{$term}%");
                    }
                });
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')));

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model), true) && $request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $sort = $request->string('sort')->toString();
        $sortable = array_keys($config['columns']);
        $query->orderBy(in_array($sort, $sortable, true) ? $sort : $this->defaultSort($model), $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc');

        $items = $query->paginate($this->perPage($request))->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($items, 'masters.partials.table', [
                'masterKey' => $master,
                'masterConfig' => $config,
            ]);
        }

        return view('masters.show', [
            'masterKey' => $master,
            'masterConfig' => $config,
            'items' => $items,
            'related' => $this->relatedOptions($master),
            'filters' => $request->all(),
        ]);
    }

    public function store(Request $request, string $master): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('masters.manage'), 403);

        $config = $this->config($master);
        $model = $config['model'];

        $data = $this->validated($request, $master);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['code'] = $data['code'] ?? strtoupper(Str::substr(Str::slug($data['name'], ''), 0, 12));

        if ($this->hasColumn($model, 'sort_order') && ! isset($data['sort_order'])) {
            $data['sort_order'] = $model::query()->max('sort_order') + 1;
        }

        $record = $model::query()->create($data);

        if ($request->expectsJson()) {
            return $this->ok($config['singular'].' created.', ['record' => $record]);
        }

        return back()->with('success', $config['singular'].' created successfully.');
    }

    public function update(Request $request, string $master, int $id): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('masters.manage'), 403);

        $config = $this->config($master);
        $record = $config['model']::query()->findOrFail($id);

        $data = $this->validated($request, $master, $record->id);
        $data['slug'] = $data['slug'] ?? $record->slug ?? Str::slug($data['name']);

        $record->update($data);

        if ($request->expectsJson()) {
            return $this->ok($config['singular'].' updated.', ['record' => $record->refresh()]);
        }

        return back()->with('success', $config['singular'].' updated successfully.');
    }

    public function toggle(Request $request, string $master, int $id): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('masters.manage'), 403);

        $config = $this->config($master);
        $record = $config['model']::query()->findOrFail($id);

        $column = match (true) {
            $this->hasColumn($record, 'is_active') => 'is_active',
            $this->hasColumn($record, 'status') => 'status',
            default => null,
        };

        abort_if(! $column, 422, 'This master does not support activation.');

        if ($column === 'status') {
            $record->forceFill(['status' => $record->status === 'active' ? 'inactive' : 'active'])->save();
        } else {
            $record->forceFill(['is_active' => ! $record->is_active])->save();
        }

        $label = $column === 'status' ? $record->status : ($record->is_active ? 'active' : 'inactive');

        if ($request->expectsJson()) {
            return $this->ok($config['singular'].' marked as '.$label.'.', ['active' => $record->is_active ?? $record->status === 'active']);
        }

        return back()->with('success', $config['singular'].' marked as '.$label.'.');
    }

    public function destroy(Request $request, string $master, int $id): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('masters.manage'), 403);

        $config = $this->config($master);
        $record = $config['model']::query()->findOrFail($id);

        if ($request->boolean('restore')) {
            $record->restore();
        } else {
            $record->delete();
        }

        if ($request->expectsJson()) {
            return $this->ok($config['singular'].($request->boolean('restore') ? ' restored.' : ' deleted.'));
        }

        return back()->with('success', $config['singular'].($request->boolean('restore') ? ' restored.' : ' deleted.'));
    }

    public function options(Request $request, string $master): JsonResponse
    {
        $config = $this->config($master);

        return response()->json([
            'success' => true,
            'options' => $config['model']::query()
                ->when($request->filled('parent_id') && $this->hasColumn($config['model'], 'product_category_id'), fn ($q) => $q->where('product_category_id', $request->integer('parent_id')))
                ->when($request->filled('product_id') && $this->hasColumn($config['model'], 'product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($item) => ['value' => $item->id, 'label' => $item->name]),
        ]);
    }

    protected function validated(Request $request, string $master, ?int $ignoreId = null): array
    {
        $config = $this->config($master);
        $rules = $config['rules'];

        // Unique rules must ignore the record being updated.
        $rules = array_map(function ($rule) use ($ignoreId) {
            if (is_array($rule)) {
                $rule = array_map(fn ($r) => is_string($r) && str_starts_with($r, 'unique:') && $ignoreId
                    ? $r.','.$ignoreId
                    : $r, $rule);
            }

            return $rule;
        }, $rules);

        $base = [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:60'],
            'is_active' => ['nullable', 'boolean'],
        ];

        return $request->validate(array_merge($base, $rules));
    }

    protected function config(string $master): array
    {
        $config = config('loanpro.masters.'.$master);

        abort_if(! $config, 404, 'Unknown master: '.$master);

        return $config;
    }

    protected function defaultSort(string $model): string
    {
        return $this->hasColumn($model, 'sort_order') ? 'sort_order' : 'name';
    }

    protected function hasColumn($model, string $column): bool
    {
        $instance = is_string($model) ? new $model : $model;

        return in_array($column, $instance->getConnection()->getSchemaBuilder()->getColumnListing($instance->getTable()), true);
    }

    /** Extra dropdown data some masters need (parents, products, ...). */
    protected function relatedOptions(string $master): array
    {
        return match ($master) {
            'product-categories', 'product-subcategories' => [
                'products' => Product::query()->ordered()->get(['id', 'name']),
                'categories' => ProductCategory::query()->ordered()->get(['id', 'name', 'product_id']),
            ],
            'designations' => ['departments' => \App\Models\Department::query()->ordered()->get(['id', 'name'])],
            default => [],
        };
    }
}
