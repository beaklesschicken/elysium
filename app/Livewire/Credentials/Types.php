<?php

namespace App\Livewire\Credentials;

use App\Enums\CredentialCategory;
use App\Enums\CredentialHolderType;
use App\Models\CredentialType;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The catalog of credential types: the shared public list plus the organization's own.
 */
#[Title('Credential catalog')]
class Types extends Component
{
    #[Url(except: '')]
    public string $holder = '';

    #[Url(except: '')]
    public string $search = '';

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $category = 'certificate';

    public string $holderType = 'employee';

    public string $issuingAuthority = '';

    public string $usState = '';

    public string $validityMonths = '';

    public string $infoLink = '';

    public string $description = '';

    public function mount(): void
    {
        $this->authorize('viewAny', CredentialType::class);
    }

    public function startCreate(): void
    {
        $this->authorize('create', CredentialType::class);
        $this->resetForm();

        Flux::modal('credential-type-form')->show();
    }

    public function startEdit(int $typeId): void
    {
        $type = CredentialType::query()->findOrFail($typeId);
        $this->authorize('update', $type);
        $this->resetForm();

        $this->editingId = $type->id;
        $this->name = $type->name;
        $this->category = $type->category->value;
        $this->holderType = $type->holder_type->value;
        $this->issuingAuthority = $type->issuing_authority ?? '';
        $this->usState = $type->us_state ?? '';
        $this->validityMonths = $type->validity_months === null ? '' : (string) $type->validity_months;
        $this->infoLink = $type->info_link ?? '';
        $this->description = $type->description ?? '';

        Flux::modal('credential-type-form')->show();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(CredentialCategory::class)],
            'holderType' => ['required', Rule::enum(CredentialHolderType::class)],
            'issuingAuthority' => ['nullable', 'string', 'max:150'],
            'usState' => ['nullable', 'string', 'regex:/^[A-Za-z]{2}$/'],
            'validityMonths' => ['nullable', 'integer', 'min:1', 'max:240'],
            'infoLink' => ['nullable', 'url:http,https', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'usState.regex' => __('Use the two-letter state code, like IN.'),
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $attributes = [
            'name' => trim($validated['name']),
            'category' => $validated['category'],
            'holder_type' => $validated['holderType'],
            'issuing_authority' => filled($validated['issuingAuthority']) ? trim($validated['issuingAuthority']) : null,
            'us_state' => filled($validated['usState']) ? Str::upper($validated['usState']) : null,
            'validity_months' => filled($validated['validityMonths']) ? (int) $validated['validityMonths'] : null,
            'info_link' => filled($validated['infoLink']) ? $validated['infoLink'] : null,
            'description' => filled($validated['description']) ? $validated['description'] : null,
        ];

        if ($this->editingId) {
            $type = CredentialType::query()->findOrFail($this->editingId);
            $this->authorize('update', $type);

            if ($type->credentials()->exists()) {
                unset($attributes['holder_type']);
            }

            $type->update($attributes);
        } else {
            $this->authorize('create', CredentialType::class);
            $type = CredentialType::create([...$attributes, 'organization_id' => Auth::user()->current_organization_id]);
        }

        Flux::modal('credential-type-form')->close();
        Flux::toast(variant: 'success', text: __(':name saved.', ['name' => $type->name]));
        $this->resetForm();
    }

    public function delete(int $typeId): void
    {
        $type = CredentialType::query()->findOrFail($typeId);
        $this->authorize('delete', $type);

        $type->delete();

        Flux::modal('delete-type-'.$typeId)->close();
        Flux::toast(text: __(':name removed from the catalog.', ['name' => $type->name]));
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'category', 'holderType', 'issuingAuthority', 'usState', 'validityMonths', 'infoLink', 'description');
        $this->resetValidation();
    }

    public function render(): View
    {
        $term = '%'.trim($this->search).'%';

        $types = CredentialType::query()
            ->withCount('credentials')
            ->when(CredentialHolderType::tryFrom($this->holder), fn (Builder $query, CredentialHolderType $holderType) => $query->forHolder($holderType))
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', $term)
                ->orWhere('issuing_authority', 'like', $term)))
            ->orderByRaw('organization_id is null')
            ->orderBy('name')
            ->get();

        return view('livewire.credentials.types', [
            'types' => $types,
            'holderTypes' => CredentialHolderType::cases(),
            'categories' => CredentialCategory::cases(),
            'editingHasCredentials' => $this->editingId !== null && CredentialType::query()->whereKey($this->editingId)->whereHas('credentials')->exists(),
        ]);
    }
}
