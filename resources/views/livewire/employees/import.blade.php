<div class="flex w-full max-w-2xl flex-col gap-6">
    <div>
        <flux:link :href="route('employees.index')" wire:navigate class="text-sm">&larr; {{ __('Employees') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2">{{ __('Import employees') }}</flux:heading>
        <flux:subheading>{{ __('Upload a CSV file exported from your spreadsheet or payroll system.') }}</flux:subheading>
    </div>

    <flux:callout icon="information-circle" variant="secondary">
        <flux:callout.heading>{{ __('File format') }}</flux:callout.heading>
        <flux:callout.text>
            {{ __('The first row is a header. Only "name" is required. Other columns:') }}
            <code class="text-xs">email, phone, start_date, birth_date, unit</code>.
            {{ __('"unit" must match one of your unit names. Nothing is imported if any row has a problem.') }}
        </flux:callout.text>
    </flux:callout>

    @if ($importedCount !== null)
        <flux:callout icon="check-circle" color="green">
            <flux:callout.heading>{{ trans_choice('Imported :count employee.|Imported :count employees.', $importedCount) }}</flux:callout.heading>
            <flux:callout.text><flux:link :href="route('employees.index')" wire:navigate>{{ __('View employees') }}</flux:link></flux:callout.text>
        </flux:callout>
    @endif

    @if ($rowErrors)
        <flux:callout icon="exclamation-triangle" color="red">
            <flux:callout.heading>{{ __('Nothing was imported. Fix these rows and upload again:') }}</flux:callout.heading>
            <flux:callout.text>
                <ul class="list-disc ps-5">
                    @foreach (array_slice($rowErrors, 0, 50) as $rowError)
                        <li>{{ $rowError }}</li>
                    @endforeach
                </ul>
                @if (count($rowErrors) > 50)
                    <p class="mt-2">{{ __('…and :count more.', ['count' => count($rowErrors) - 50]) }}</p>
                @endif
            </flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="import" class="flex flex-col gap-6">
        <flux:input type="file" wire:model="file" :label="__('CSV file')" accept=".csv,text/csv" />

        @if ($this->assignableUnits->isNotEmpty())
            <flux:select wire:model="defaultUnit" :label="__('Put employees without a unit column on')" :placeholder="__('No unit')">
                @foreach ($this->assignableUnits as $unitOption)
                    <flux:select.option wire:key="default-unit-{{ $unitOption->id }}" :value="$unitOption->id">{{ $unitOption->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <div>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="file,import">{{ __('Import') }}</flux:button>
        </div>
    </form>
</div>
