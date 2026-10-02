<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen overflow-x-clip {{ auth()->user()->canAccessOperationsWorkspace() ? 'bg-gray-50 dark:bg-zinc-950' : 'bg-zinc-50 dark:bg-zinc-800' }}" @if (auth()->user()->canAccessOperationsWorkspace()) data-admin-workspace @endif>
        @if (auth()->user()->isCustomer())
            <flux:sidebar sticky :collapsible="true" data-app-desktop-sidebar class="max-lg:hidden !w-64 border-e border-gray-200 bg-white p-0">
                <x-customer-desktop-sidebar />
            </flux:sidebar>
        @elseif (auth()->user()->canAccessOperationsWorkspace())
            <flux:sidebar sticky :collapsible="true" data-app-desktop-sidebar data-admin-sidebar class="max-lg:hidden !h-dvh !w-72 !gap-0 !overflow-hidden !border-e !border-gray-200 !bg-white !p-0 shadow-sm data-flux-sidebar-collapsed-desktop:!w-14 dark:!border-zinc-800 dark:!bg-zinc-900">
                <flux:sidebar.header class="!min-h-20 border-b border-gray-100 px-4 dark:border-zinc-800 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:px-2">
                    <a href="{{ route(auth()->user()->operationsRouteName('dashboard')) }}" wire:navigate class="flex min-w-0 items-center gap-3" aria-label="{{ auth()->user()->isStaff() ? __('FixTrack staff dashboard') : __('FixTrack administrator dashboard') }}">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-blue-400 shadow-sm dark:bg-slate-800">
                            <flux:icon name="wrench-screwdriver" class="size-5" />
                        </span>
                        <span class="min-w-0 in-data-flux-sidebar-collapsed-desktop:hidden">
                            <span class="block truncate text-xl font-bold tracking-tight text-gray-900 dark:text-white">FixTrack</span>
                            <span class="block truncate text-xs font-medium text-gray-500 dark:text-zinc-400">{{ auth()->user()->isStaff() ? __('Staff Workspace') : __('Administrator Console') }}</span>
                        </span>
                    </a>
                    <flux:sidebar.collapse class="text-gray-400 hover:text-gray-600 dark:text-zinc-400" />
                </flux:sidebar.header>

                <x-app-sidebar-navigation />

                <div class="mt-auto border-t border-gray-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 in-data-flux-sidebar-collapsed-desktop:px-2" data-admin-sidebar-footer>
                    <div class="flex items-center justify-between gap-1 in-data-flux-sidebar-collapsed-desktop:justify-center">
                        <flux:dropdown position="top" align="start">
                            <button type="button" class="flex min-w-0 flex-1 items-center gap-3 rounded-md text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500" data-test="sidebar-menu-button" aria-label="{{ __('Account menu for :name', ['name' => auth()->user()->name]) }}">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-gray-200 text-sm font-semibold text-gray-700 dark:bg-zinc-700 dark:text-zinc-100">{{ auth()->user()->initials() }}</span>
                                <span class="max-w-32 truncate text-base font-semibold text-gray-900 dark:text-white in-data-flux-sidebar-collapsed-desktop:hidden">{{ auth()->user()->name }}</span>
                            </button>
                            <flux:menu class="w-56">
                                <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>{{ __('Profile') }}</flux:menu.item>
                                <flux:menu.separator />
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">{{ __('Log out') }}</flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>

                        <div class="flex shrink-0 items-center gap-1 in-data-flux-sidebar-collapsed-desktop:hidden">
                            <a href="{{ route(auth()->user()->operationsRouteName('module'), ['module' => 'support-disputes']) }}" wire:navigate class="flex size-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 dark:hover:bg-zinc-800 dark:hover:text-white" title="{{ __('Help & Support') }}" aria-label="{{ __('Help & Support') }}"><flux:icon name="question-mark-circle" class="size-5" /></a>
                            <a href="{{ auth()->user()->isStaff() ? route('profile.edit') : route('admin.module', ['module' => 'platform-settings']) }}" wire:navigate class="flex size-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 dark:hover:bg-zinc-800 dark:hover:text-white" title="{{ __('Settings') }}" aria-label="{{ __('Settings') }}"><flux:icon name="cog-6-tooth" class="size-5" /></a>
                        </div>
                    </div>
                </div>
            </flux:sidebar>
        @else
            <flux:sidebar sticky :collapsible="true" data-app-desktop-sidebar class="max-lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.header>
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
                </flux:sidebar.header>

                <flux:sidebar.nav>
                    <x-app-sidebar-navigation />
                </flux:sidebar.nav>

                <flux:spacer />

                <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            </flux:sidebar>
        @endif

        @if (! auth()->user()->isCustomer() && ! auth()->user()->isTechnician())
            <!-- Mobile User Menu -->
            <flux:header class="sticky top-0 z-30 min-h-16 border-b border-zinc-200 bg-white/95 px-3 backdrop-blur lg:hidden dark:border-zinc-700 dark:bg-zinc-900/95" data-app-mobile-header>
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

                <flux:spacer />

                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <flux:avatar
                                        :name="auth()->user()->name"
                                        :initials="auth()->user()->initials()"
                                    />

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                        <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:header>
        @endif

        @if (session('google_status'))
            <div
                x-data="{ visible: true }"
                x-show="visible"
                x-transition.opacity
                class="fixed top-4 left-1/2 z-[100] flex w-[calc(100%-2rem)] max-w-xl -translate-x-1/2 items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-xl dark:border-emerald-500/30 dark:bg-emerald-950 dark:text-emerald-100"
                role="status"
                aria-live="polite"
                data-test="google-auth-status"
            >
                <flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                <span class="min-w-0 flex-1">{{ session('google_status') }}</span>
                <button type="button" x-on:click="visible = false" class="flex size-6 shrink-0 items-center justify-center rounded-md text-emerald-700 transition hover:bg-emerald-100 hover:text-emerald-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:text-emerald-300 dark:hover:bg-emerald-900 dark:hover:text-white" aria-label="{{ __('Dismiss message') }}">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
        @endif

        {{ $slot }}

        <x-app-sidebar-navigation mobile />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
