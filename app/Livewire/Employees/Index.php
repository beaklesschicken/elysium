<?php

namespace App\Livewire\Employees;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Employees')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'active')]
    public string $status = 'active';

    #[Url(except: '')]
    public string $unit = '';

    public const SORTABLE = ['name', 'start_date', 'status'];

    #[Url(except: 'name')]
    public string $sortBy = 'name';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Employee::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'unit'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $user = Auth::user();

        $employees = Employee::query()
            ->visibleTo($user)
            ->with('units:units.id,units.name')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn (Builder $inner) => $inner->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when(EmployeeStatus::tryFrom($this->status), fn (Builder $query, EmployeeStatus $status) => $query->where('status', $status))
            ->when($this->unit !== '', fn (Builder $query) => $query->whereHas('units', fn (Builder $units) => $units->whereKey((int) $this->unit)))
            ->orderBy(in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'name', $this->sortDirection === 'desc' ? 'desc' : 'asc')
            ->orderBy('id')
            ->paginate(25);

        return view('livewire.employees.index', [
            'employees' => $employees,
            'units' => Unit::query()->visibleTo($user)->orderBy('name')->get(['id', 'name']),
            'statuses' => EmployeeStatus::cases(),
        ]);
    }
}
