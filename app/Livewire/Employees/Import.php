<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use App\Models\Unit;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Import employees')]
class Import extends Component
{
    use WithFileUploads;

    public const MAX_ROWS = 1000;

    public const COLUMNS = ['name', 'email', 'phone', 'start_date', 'birth_date', 'unit'];

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public string $defaultUnit = '';

    /** @var list<string> */
    public array $rowErrors = [];

    public ?int $importedCount = null;

    public function mount(): void
    {
        $this->authorize('create', Employee::class);
    }

    /**
     * @return Collection<int, Unit>
     */
    #[Computed]
    public function assignableUnits(): Collection
    {
        return Unit::query()->visibleTo(Auth::user())->orderBy('name')->get(['id', 'name']);
    }

    public function import(): void
    {
        $this->authorize('create', Employee::class);
        $this->reset('rowErrors', 'importedCount');

        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
            'defaultUnit' => ['nullable', Rule::in($this->assignableUnits->modelKeys())],
        ]);

        $rows = $this->readRows($this->file->getRealPath());

        if ($rows === null) {
            return;
        }

        $unitsByName = $this->assignableUnits->keyBy(fn (Unit $unit): string => Str::lower(trim($unit->name)));
        $mustPickUnit = ! Auth::user()->isOrganizationAdmin();
        $prepared = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:150'],
                'email' => ['nullable', 'email:rfc', 'max:150'],
                'phone' => ['nullable', 'string', 'max:30'],
                'start_date' => ['nullable', 'date'],
                'birth_date' => ['nullable', 'date', 'before:today'],
                'unit' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $this->rowErrors[] = __('Row :line: :message', ['line' => $line, 'message' => $validator->errors()->first()]);

                continue;
            }

            $unitId = $this->defaultUnit !== '' ? (int) $this->defaultUnit : null;

            if (filled($row['unit'] ?? null)) {
                $unit = $unitsByName->get(Str::lower(trim($row['unit'])));

                if ($unit === null) {
                    $this->rowErrors[] = __('Row :line: no unit named ":unit".', ['line' => $line, 'unit' => $row['unit']]);

                    continue;
                }

                $unitId = $unit->id;
            }

            if ($mustPickUnit && $unitId === null) {
                $this->rowErrors[] = __('Row :line: choose a unit for this employee.', ['line' => $line]);

                continue;
            }

            $prepared[] = [
                'attributes' => [
                    'name' => trim($row['name']),
                    'email' => filled($row['email'] ?? null) ? trim($row['email']) : null,
                    'phone' => filled($row['phone'] ?? null) ? trim($row['phone']) : null,
                    'start_date' => filled($row['start_date'] ?? null) ? Carbon::parse($row['start_date'])->toDateString() : null,
                    'birth_date' => filled($row['birth_date'] ?? null) ? Carbon::parse($row['birth_date'])->toDateString() : null,
                ],
                'unit_id' => $unitId,
            ];
        }

        if ($this->rowErrors !== []) {
            return;
        }

        $organization = Auth::user()->currentOrganization;

        if (! $organization->canAddEmployees(count($prepared))) {
            $this->rowErrors[] = __('The :plan plan allows :limit active employees. Subscribe to import more.', [
                'plan' => $organization->plan()->label(),
                'limit' => $organization->plan()->employeeLimit(),
            ]);

            return;
        }

        DB::transaction(function () use ($prepared): void {
            foreach ($prepared as $item) {
                $employee = Employee::create($item['attributes']);

                if ($item['unit_id'] !== null) {
                    $employee->units()->attach($item['unit_id']);
                }
            }
        });

        $this->importedCount = count($prepared);
        $this->reset('file');

        Flux::toast(variant: 'success', text: trans_choice('Imported :count employee.|Imported :count employees.', $this->importedCount));
    }

    /**
     * Reads the CSV into rows keyed by the known column names.
     *
     * @return list<array<string, string|null>>|null
     */
    private function readRows(string $path): ?array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '\\');

        if ($header === false) {
            fclose($handle);
            $this->addError('file', __('The file is empty.'));

            return null;
        }

        $header = array_map(
            fn (?string $column): string => Str::snake(Str::lower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $column)))),
            $header
        );

        if (! in_array('name', $header, true)) {
            fclose($handle);
            $this->addError('file', __('The first row must be a header that includes a "name" column.'));

            return null;
        }

        $rows = [];

        while (($values = fgetcsv($handle, escape: '\\')) !== false) {
            if ($values === [null] || collect($values)->every(fn (?string $value): bool => blank($value))) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                $this->addError('file', __('Import up to :max employees at a time.', ['max' => self::MAX_ROWS]));

                return null;
            }

            $row = [];
            foreach (self::COLUMNS as $column) {
                $position = array_search($column, $header, true);
                $row[$column] = $position === false ? null : (isset($values[$position]) ? trim($values[$position]) : null);
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    public function render(): View
    {
        return view('livewire.employees.import');
    }
}
