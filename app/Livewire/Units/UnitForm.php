<?php

namespace App\Livewire\Units;

use App\Livewire\Concerns\HasAddressFields;
use App\Models\Unit;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Unit details')]
class UnitForm extends Component
{
    use HasAddressFields;

    public ?Unit $unit = null;

    public string $name = '';

    public string $description = '';

    public string $phone = '';

    public function mount(?Unit $unit = null): void
    {
        if ($unit?->exists) {
            $this->authorize('update', $unit);
            $this->unit = $unit;
            $this->name = $unit->name;
            $this->description = $unit->description ?? '';
            $this->phone = $unit->phone ?? '';
            $this->fillAddressFrom($unit);

            return;
        }

        $this->authorize('create', Unit::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            ...$this->addressRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return $this->addressMessages();
    }

    public function save(): void
    {
        $this->unit
            ? $this->authorize('update', $this->unit)
            : $this->authorize('create', Unit::class);

        $validated = $this->normalizeAddress($this->validate());
        $validated['description'] = filled($validated['description']) ? $validated['description'] : null;
        $validated['phone'] = filled($validated['phone']) ? $validated['phone'] : null;

        if ($this->unit) {
            $this->unit->update($validated);
            $unit = $this->unit;
        } else {
            $unit = Unit::create($validated);
        }

        Flux::toast(variant: 'success', text: __(':name saved.', ['name' => $unit->name]));

        $this->redirect(route('units.show', $unit, absolute: false), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.units.unit-form');
    }
}
