<?php

namespace App\Livewire\Employees;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Employee')]
class EmployeeForm extends Component
{
    public ?Employee $employee = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $start_date = '';

    public string $birth_date = '';

    public string $status = 'active';

    public string $notes = '';

    /** @var list<string> */
    public array $unitIds = [];

    #[Url(as: 'unit')]
    public string $preselectedUnit = '';

    public function mount(?Employee $employee = null): void
    {
        if ($employee?->exists) {
            $this->authorize('update', $employee);
            $this->employee = $employee;
            $this->name = $employee->name;
            $this->email = $employee->email ?? '';
            $this->phone = $employee->phone ?? '';
            $this->start_date = $employee->start_date?->toDateString() ?? '';
            $this->birth_date = $employee->birth_date?->toDateString() ?? '';
            $this->status = $employee->status->value;
            $this->notes = $employee->notes ?? '';
            $this->unitIds = $employee->units()
                ->whereIn('units.id', $this->assignableUnits->modelKeys())
                ->pluck('units.id')
                ->map(fn (int $id): string => (string) $id)
                ->all();

            return;
        }

        $this->authorize('create', Employee::class);

        if (in_array((int) $this->preselectedUnit, $this->assignableUnits->modelKeys(), true)) {
            $this->unitIds = [$this->preselectedUnit];
        } elseif ($this->assignableUnits->count() === 1) {
            $this->unitIds = [(string) $this->assignableUnits->first()->id];
        }
    }

    /**
     * Units the current user may put employees on.
     *
     * @return Collection<int, Unit>
     */
    #[Computed]
    public function assignableUnits(): Collection
    {
        return Unit::query()->visibleTo(Auth::user())->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $mustPickUnit = ! Auth::user()->isOrganizationAdmin();

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'start_date' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'unitIds' => [$mustPickUnit ? 'required' : 'nullable', 'array'],
            'unitIds.*' => ['integer', Rule::in($this->assignableUnits->modelKeys())],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'unitIds.required' => __('Pick at least one of your units.'),
            'unitIds.*.in' => __('Pick units from your organization.'),
        ];
    }

    public function save(): void
    {
        $this->employee
            ? $this->authorize('update', $this->employee)
            : $this->authorize('create', Employee::class);

        $validated = $this->validate();
        $selectedUnitIds = array_map('intval', $validated['unitIds'] ?? []);

        $attributes = [
            'name' => trim($validated['name']),
            'email' => filled($validated['email']) ? $validated['email'] : null,
            'phone' => filled($validated['phone']) ? $validated['phone'] : null,
            'start_date' => filled($validated['start_date']) ? $validated['start_date'] : null,
            'birth_date' => filled($validated['birth_date']) ? $validated['birth_date'] : null,
            'status' => $validated['status'],
            'notes' => filled($validated['notes']) ? $validated['notes'] : null,
        ];

        DB::transaction(function () use ($attributes, $selectedUnitIds): void {
            if ($this->employee) {
                $this->employee->update($attributes);
            } else {
                $this->employee = Employee::create($attributes);
            }

            $unselectedUnitIds = array_diff($this->assignableUnits->modelKeys(), $selectedUnitIds);
            $this->employee->units()->detach($unselectedUnitIds);
            $this->employee->units()->syncWithoutDetaching($selectedUnitIds);
        });

        session()->flash('status', __(':name saved.', ['name' => $this->employee->name]));

        $this->redirect(route('employees.index', absolute: false), navigate: true);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->employee);

        $this->employee->delete();

        $this->redirect(route('employees.index', absolute: false), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.employees.employee-form', [
            'statuses' => EmployeeStatus::cases(),
        ]);
    }
}
