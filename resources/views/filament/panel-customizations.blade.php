<style>
    {{--
        Panel layout customizations.

        The sidebar ships transparent on desktop (the gray-50 body shows
        through), so it gets its own violet-tinted shade here to stand out
        from the full-width content canvas. Written as higher-specificity
        overrides (double class selectors) so they beat Filament's compiled
        utility rules regardless of stylesheet order; light-mode interaction
        states are guarded with html:not(.dark) so the dark palette keeps
        Filament's defaults.
    --}}

    .fi-sidebar.fi-sidebar {
        background-color: #f5f3ff; {{-- violet-50 --}}
    }

    .dark .fi-sidebar.fi-sidebar {
        background-color: #151024; {{-- violet-tinted near-black --}}
    }

    @media (min-width: 1024px) {
        .fi-sidebar.fi-sidebar {
            border-inline-end: 1px solid #ddd6fe; {{-- violet-200 --}}
        }

        .dark .fi-sidebar.fi-sidebar {
            border-inline-end-color: #2e2452;
        }
    }

    html:not(.dark) .fi-sidebar.fi-sidebar .fi-sidebar-item-has-url > .fi-sidebar-item-btn:hover,
    html:not(.dark) .fi-sidebar.fi-sidebar .fi-sidebar-item-has-url > .fi-sidebar-item-btn:focus-visible,
    html:not(.dark) .fi-sidebar.fi-sidebar .fi-sidebar-group-dropdown-trigger-btn:hover,
    html:not(.dark) .fi-sidebar.fi-sidebar .fi-sidebar-group-dropdown-trigger-btn:focus-visible {
        background-color: #ede9fe; {{-- violet-100 --}}
    }

    html:not(.dark) .fi-sidebar.fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn,
    html:not(.dark) .fi-sidebar.fi-sidebar .fi-sidebar-item.fi-sidebar-item-has-active-child-items > .fi-sidebar-item-btn {
        background-color: #ffffff; {{-- active items pop against the shade --}}
    }
</style>
