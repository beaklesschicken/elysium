<?php

namespace App\Livewire\Credentials;

use App\Enums\CredentialHolderType;
use App\Models\Credential;
use App\Models\CredentialDocument;
use App\Models\CredentialType;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Lists, adds, edits, renews and deletes the credentials of one employee, unit or organization.
 */
class HolderCredentials extends Component
{
    use WithFileUploads;

    public const DOCUMENT_RULES = ['file', 'mimes:pdf,jpg,jpeg,png,webp,heic', 'max:10240'];

    #[Locked]
    public string $holderType;

    #[Locked]
    public int $holderId;

    public string $heading = '';

    public bool $showHistory = false;

    /** 'create', 'edit' or 'renew' */
    #[Locked]
    public string $mode = 'create';

    #[Locked]
    public ?int $editingId = null;

    public string $credentialTypeId = '';

    public string $number = '';

    public string $issuedOn = '';

    public string $expiresOn = '';

    public string $notes = '';

    public bool $verified = false;

    /** @var TemporaryUploadedFile|null */
    public $document = null;

    public function mount(Model $holder, ?string $heading = null): void
    {
        $this->holderType = CredentialHolderType::fromModel($holder)->value;
        $this->holderId = $holder->id;
        $this->heading = $heading ?? __('Credentials');

        Gate::authorize('viewHolder', [Credential::class, $holder]);
    }

    #[Computed]
    public function holder(): Model
    {
        return CredentialHolderType::from($this->holderType)->modelClass()::findOrFail($this->holderId);
    }

    #[Computed]
    public function canManage(): bool
    {
        return Gate::allows('manageFor', [Credential::class, $this->holder]);
    }

    /**
     * @return Collection<int, Credential>
     */
    #[Computed]
    public function credentials(): Collection
    {
        return $this->baseQuery()
            ->current()
            ->with(['type', 'documents', 'verifier:id,name'])
            ->get()
            ->sortBy([
                fn (Credential $a, Credential $b) => $a->status()->urgency() <=> $b->status()->urgency(),
                fn (Credential $a, Credential $b) => ($a->expires_on?->timestamp ?? PHP_INT_MAX) <=> ($b->expires_on?->timestamp ?? PHP_INT_MAX),
            ])
            ->values();
    }

    /**
     * @return Collection<int, Credential>
     */
    #[Computed]
    public function history(): Collection
    {
        return $this->baseQuery()
            ->whereNotNull('superseded_at')
            ->with(['type', 'documents'])
            ->latest('superseded_at')
            ->get();
    }

    /**
     * @return Collection<int, CredentialType>
     */
    #[Computed]
    public function availableTypes(): Collection
    {
        return CredentialType::query()
            ->forHolder(CredentialHolderType::from($this->holderType))
            ->orderBy('name')
            ->get();
    }

    public function startCreate(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->mode = 'create';

        Flux::modal($this->modalName())->show();
    }

    public function startEdit(int $credentialId): void
    {
        $credential = $this->findCredential($credentialId);
        $this->resetForm();
        $this->mode = 'edit';
        $this->editingId = $credential->id;
        $this->credentialTypeId = (string) $credential->credential_type_id;
        $this->number = $credential->number ?? '';
        $this->issuedOn = $credential->issued_on?->toDateString() ?? '';
        $this->expiresOn = $credential->expires_on?->toDateString() ?? '';
        $this->notes = $credential->notes ?? '';
        $this->verified = $credential->verified_at !== null;

        Flux::modal($this->modalName())->show();
    }

    public function startRenew(int $credentialId): void
    {
        $credential = $this->findCredential($credentialId);
        $this->resetForm();
        $this->mode = 'renew';
        $this->editingId = $credential->id;
        $this->credentialTypeId = (string) $credential->credential_type_id;
        $this->issuedOn = today()->toDateString();
        $this->fillDefaultExpiry();

        Flux::modal($this->modalName())->show();
    }

    public function updatedIssuedOn(): void
    {
        if ($this->expiresOn === '') {
            $this->fillDefaultExpiry();
        }
    }

    public function updatedCredentialTypeId(): void
    {
        if ($this->expiresOn === '') {
            $this->fillDefaultExpiry();
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'credentialTypeId' => ['required', Rule::in($this->availableTypes->modelKeys())],
            'number' => ['nullable', 'string', 'max:100'],
            'issuedOn' => ['nullable', 'date', 'before_or_equal:today'],
            'expiresOn' => ['nullable', 'date', Rule::when(filled($this->issuedOn), 'after_or_equal:issuedOn')],
            'notes' => ['nullable', 'string', 'max:5000'],
            'verified' => ['boolean'],
            'document' => ['nullable', ...self::DOCUMENT_RULES],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'credentialTypeId.required' => __('Choose what kind of credential this is.'),
            'credentialTypeId.in' => __('Choose a credential from the list.'),
            'expiresOn.after_or_equal' => __('The expiration date can\'t be before the issue date.'),
            'document.mimes' => __('Upload a PDF or a photo (JPG, PNG, WebP or HEIC).'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'issuedOn' => __('issue date'),
            'expiresOn' => __('expiration date'),
        ];
    }

    public function save(): void
    {
        $this->authorizeManage();
        $validated = $this->validate();
        $user = Auth::user();

        $attributes = [
            'credential_type_id' => (int) $validated['credentialTypeId'],
            'number' => filled($validated['number']) ? trim($validated['number']) : null,
            'issued_on' => filled($validated['issuedOn']) ? $validated['issuedOn'] : null,
            'expires_on' => filled($validated['expiresOn']) ? $validated['expiresOn'] : null,
            'notes' => filled($validated['notes']) ? $validated['notes'] : null,
        ];

        $credential = DB::transaction(function () use ($attributes, $user): Credential {
            if ($this->mode === 'edit') {
                $credential = $this->findCredential($this->editingId);
                $wasVerified = $credential->verified_at !== null;
                $credential->fill($attributes);
                $credential->forceFill($this->verificationAttributes($wasVerified, $credential, $user))->save();

                return $credential;
            }

            if ($this->mode === 'renew') {
                $previous = $this->findCredential($this->editingId);
                $renewed = $previous->renew([...$attributes, 'credential_type_id' => $previous->credential_type_id]);
                $renewed->forceFill($this->verificationAttributes(false, $renewed, $user))->save();

                return $renewed;
            }

            $credential = Credential::create([
                ...$attributes,
                'holder_type' => $this->holderType,
                'holder_id' => $this->holderId,
            ]);
            $credential->forceFill($this->verificationAttributes(false, $credential, $user))->save();

            return $credential;
        });

        if ($this->document) {
            $this->storeDocument($credential, $this->document);
        }

        Flux::modal($this->modalName())->close();
        Flux::toast(variant: 'success', text: match ($this->mode) {
            'renew' => __(':name renewed.', ['name' => $credential->type->name]),
            'edit' => __(':name updated.', ['name' => $credential->type->name]),
            default => __(':name added.', ['name' => $credential->type->name]),
        });

        $this->resetForm();
        $this->clearCaches();
    }

    public function delete(int $credentialId): void
    {
        $credential = $this->findCredential($credentialId, includeHistory: true);

        DB::transaction(function () use ($credential): void {
            $credential->documents->each->delete();
            $credential->delete();
        });

        Flux::modal('delete-credential-'.$credentialId)->close();
        Flux::toast(text: __('Credential deleted.'));
        $this->clearCaches();
    }

    public function deleteDocument(int $documentId): void
    {
        $document = CredentialDocument::query()->findOrFail($documentId);
        $this->findCredential($document->credential_id, includeHistory: true);

        $document->delete();

        Flux::toast(text: __('Document removed.'));
        $this->clearCaches();
    }

    public function modalName(): string
    {
        return 'credential-form-'.$this->holderType.'-'.$this->holderId;
    }

    private function storeDocument(Credential $credential, TemporaryUploadedFile $file): void
    {
        $path = $file->storeAs(
            'credentials/'.$credential->organization_id,
            Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension()),
            CredentialDocument::DISK,
        );

        $credential->documents()->create([
            'organization_id' => $credential->organization_id,
            'uploaded_by' => Auth::id(),
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);
    }

    /**
     * @return array{verified_by: ?int, verified_at: ?Carbon}
     */
    private function verificationAttributes(bool $wasVerified, Credential $credential, User $user): array
    {
        if (! $this->verified) {
            return ['verified_by' => null, 'verified_at' => null];
        }

        if ($wasVerified) {
            return ['verified_by' => $credential->verified_by, 'verified_at' => $credential->verified_at];
        }

        return ['verified_by' => $user->id, 'verified_at' => now()];
    }

    private function fillDefaultExpiry(): void
    {
        $type = $this->availableTypes->firstWhere('id', (int) $this->credentialTypeId);
        $issuedOn = $this->issuedOn !== '' ? Carbon::parse($this->issuedOn) : null;

        $this->expiresOn = $type?->defaultExpiryFrom($issuedOn)?->toDateString() ?? '';
    }

    /**
     * @return Builder<Credential>
     */
    private function baseQuery(): Builder
    {
        return Credential::query()
            ->where('holder_type', $this->holderType)
            ->where('holder_id', $this->holderId);
    }

    private function findCredential(?int $credentialId, bool $includeHistory = false): Credential
    {
        $this->authorizeManage();

        return $this->baseQuery()
            ->when(! $includeHistory, fn ($query) => $query->current())
            ->findOrFail($credentialId);
    }

    private function authorizeManage(): void
    {
        Gate::authorize('manageFor', [Credential::class, $this->holder]);
    }

    private function resetForm(): void
    {
        $this->reset('credentialTypeId', 'number', 'issuedOn', 'expiresOn', 'notes', 'verified', 'document', 'editingId');
        $this->mode = 'create';
        $this->resetValidation();
    }

    private function clearCaches(): void
    {
        unset($this->credentials, $this->history);
    }

    public function render(): View
    {
        return view('livewire.credentials.holder-credentials');
    }
}
