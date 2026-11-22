<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Employee')]
class Show extends Component
{
    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $this->authorize('view', $employee);
        $this->employee = $employee;
    }

    public function render(): View
    {
        return view('livewire.employees.show', [
            'units' => $this->employee->units()->orderBy('name')->get(['units.id', 'units.name']),
        ]);
    }
}
