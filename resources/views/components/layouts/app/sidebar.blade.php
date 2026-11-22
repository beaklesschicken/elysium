<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @livewireStyles
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @if(Session::has('message'))
            <div x-init="popToast()"></div>
        @endif
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="mr-5 flex items-center space-x-2" wire:navigate>
                <x-app-logo />
            </a>
            
            @php
                $currentUser = auth()->user();
                $currentOrganization = $currentUser->currentOrganization;
                $organizations = $currentUser->organizations()->orderBy('name')->get();
            @endphp

            @if ($currentOrganization)
                <flux:dropdown position="bottom" align="start">
                    <flux:button variant="ghost" icon="building-office" icon-trailing="chevrons-up-down" class="w-full justify-start! truncate">
                        {{ $currentOrganization->name }}
                    </flux:button>

                    <flux:menu class="w-[240px]">
                        @foreach ($organizations as $organization)
                            <form method="POST" action="{{ route('organizations.switch', $organization) }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" :icon="$organization->id === $currentOrganization->id ? 'check' : null" class="w-full">
                                    {{ $organization->name }}
                                </flux:menu.item>
                            </form>
                        @endforeach
                        <flux:menu.separator />
                        <flux:menu.item icon="plus" :href="route('organizations.create')" wire:navigate>{{ __('New organization') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    <flux:navlist.item icon="building-storefront" :href="route('units.index')" :current="request()->routeIs('units.*')" wire:navigate>{{ __('Units') }}</flux:navlist.item>
                    <flux:navlist.item icon="users" :href="route('employees.index')" :current="request()->routeIs('employees.*')" wire:navigate>{{ __('Employees') }}</flux:navlist.item>
                    <flux:navlist.item icon="identification" :href="route('credentials.index')" :current="request()->routeIs('credentials.index')" wire:navigate>{{ __('Credentials') }}</flux:navlist.item>
                    <flux:navlist.item icon="building-office" :href="route('organization.permits')" :current="request()->routeIs('organization.permits')" wire:navigate>{{ __('Organization permits') }}</flux:navlist.item>
                    <flux:navlist.item icon="book-open" :href="route('credentials.types')" :current="request()->routeIs('credentials.types')" wire:navigate>{{ __('Catalog') }}</flux:navlist.item>
                </flux:navlist.group>

                @if ($currentOrganization && $currentUser->can('manage', $currentOrganization))
                    <flux:navlist.group :heading="__('Organization')" class="grid">
                        <flux:navlist.item icon="user-group" :href="route('organization.members')" :current="request()->routeIs('organization.members')" wire:navigate>{{ __('People & access') }}</flux:navlist.item>
                        <flux:navlist.item icon="cog-6-tooth" :href="route('organization.edit')" :current="request()->routeIs('organization.edit')" wire:navigate>{{ __('Details') }}</flux:navlist.item>
                    </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast />
        @endpersist

        @fluxScripts
        <script>
            window.toast = function(message, options = {}){
                let description = '';
                let type = 'default';
                let position = 'top-center';
                let html = '';
                if(typeof options.description != 'undefined') description = options.description;
                if(typeof options.type != 'undefined') type = options.type;
                if(typeof options.position != 'undefined') position = options.position;
                if(typeof options.html != 'undefined') html = options.html;
                
                window.dispatchEvent(new CustomEvent('toast-show', { detail : { type: type, message: message, description: description, position : position, html: html }}));
            }

            window.customToastHTML = `
                <div class='relative flex items-start justify-center p-4'>
                    <img src='https://cdn.devdojo.com/images/august2023/headshot-new.jpeg' class='w-10 h-10 mr-2 rounded-full'>
                    <div class='flex flex-col'>
                        <p class='text-sm font-medium text-gray-800'>New Friend Request</p>
                        <p class='mt-1 text-xs leading-none text-gray-800'>Friend request from John Doe.</p>
                        <div class='flex mt-3'>
                            <button type='button' @click='burnToast(toast.id)' class='inline-flex items-center px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'>Accept</button>
                            <button type='button' @click='burnToast(toast.id)' class='inline-flex items-center px-2 py-1 ml-3 text-xs font-semibold text-gray-900 bg-white rounded shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50'>Decline</button>
                        </div>
                    </div>
                </div>
            `
        </script>
           
    </body>
</html>
