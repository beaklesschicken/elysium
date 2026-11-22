<?php

namespace App\Livewire\Credentials;

use App\Enums\CredentialCategory;
use App\Enums\CredentialHolderType;
use App\Models\Credential;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Every current credential the user can see, focused on what needs attention.
 */
#[Title('Credentials')]
class Index extends Component
{
    use WithPagination;

    /** @var array<string, string> */
    public const WINDOWS = [
        'attention' => 'Expired or expiring in 90 days',
        'expired' => 'Expired',
        'expiring_30' => 'Expiring in 30 days',
        'expiring_90' => 'Expiring in 90 days',
        'all' => 'All current',
    ];

    #[Url(except: 'attention')]
    public string $window = 'attention';

    #[Url(except: '')]
    public string $holder = '';

    #[Url(except: '')]
    public string $unit = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->authorize('viewAny', Credential::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['window', 'holder', 'unit', 'category', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function toggleSort(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function showWindow(string $window): void
    {
        $this->window = array_key_exists($window, self::WINDOWS) ? $window : 'attention';
        $this->resetPage();
    }

    /**
     * @return array{expired: int, expiring_30: int, expiring_90: int}
     */
    #[Computed]
    public function counts(): array
    {
        $base = fn () => Credential::query()->visibleTo(Auth::user())->current();

        return [
            'expired' => $base()->expired()->count(),
            'expiring_30' => $base()->expiringWithin(30)->count(),
            'expiring_90' => $base()->expiringWithin(90)->count(),
        ];
    }

    /**
     * @return Builder<Credential>
     */
    private function query(): Builder
    {
        $unitId = (int) $this->unit;
        $term = '%'.trim($this->search).'%';

        return Credential::query()
            ->visibleTo(Auth::user())
            ->current()
            ->with(['type', 'holder', 'documents:id,credential_id'])
            ->when($this->window === 'expired', fn (Builder $query) => $query->expired())
            ->when($this->window === 'expiring_30', fn (Builder $query) => $query->expiringWithin(30))
            ->when($this->window === 'expiring_90', fn (Builder $query) => $query->expiringWithin(90))
            ->when($this->window === 'attention', fn (Builder $query) => $query->whereNotNull('expires_on')->whereDate('expires_on', '<=', today()->addDays(90)))
            ->when(CredentialHolderType::tryFrom($this->holder), fn (Builder $query, CredentialHolderType $holderType) => $query->where('holder_type', $holderType->value))
            ->when(CredentialCategory::tryFrom($this->category), fn (Builder $query, CredentialCategory $category) => $query->whereHas('type', fn (Builder $types) => $types->where('category', $category)))
            ->when($unitId > 0, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where(fn (Builder $units) => $units->where('holder_type', 'unit')->where('holder_id', $unitId))
                ->orWhereHasMorph('holder', [Employee::class], fn (Builder $employees) => $employees->whereHas('units', fn (Builder $units) => $units->whereKey($unitId)))))
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('number', 'like', $term)
                ->orWhereHas('type', fn (Builder $types) => $types->where('name', 'like', $term))
                ->orWhereHasMorph('holder', [Employee::class, Unit::class, Organization::class], fn (Builder $holders) => $holders->where('name', 'like', $term))))
            ->orderByRaw('expires_on is null')
            ->orderBy('expires_on', $this->sortDirection === 'desc' ? 'desc' : 'asc')
            ->orderBy('id');
    }

    public function render(): View
    {
        return view('livewire.credentials.index', [
            'credentials' => $this->query()->paginate(25),
            'units' => Unit::query()->visibleTo(Auth::user())->orderBy('name')->get(['id', 'name']),
            'holderTypes' => CredentialHolderType::cases(),
            'categories' => CredentialCategory::cases(),
            'windows' => self::WINDOWS,
        ]);
    }
}
