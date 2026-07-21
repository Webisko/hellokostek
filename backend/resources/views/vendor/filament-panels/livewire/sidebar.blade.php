<div>
    @php
        $navigation = filament()->getNavigation();
        $isRtl = __('filament-panels::layout.direction') === 'rtl';
        $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
        $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
        $hasNavigation = filament()->hasNavigation();
        $hasTopbar = filament()->hasTopbar();
    @endphp

    {{-- format-ignore-start --}}
    <aside
        x-data="{
            init() {
                // Initialize Alpine Store if not already defined
                if (!Alpine.store('sidebarSorting')) {
                    Alpine.store('sidebarSorting', {
                        isSorting: false,
                        toggle() {
                            this.isSorting = !this.isSorting;
                            if (this.isSorting) {
                                document.dispatchEvent(new CustomEvent('sidebar-sorting-start'));
                            } else {
                                document.dispatchEvent(new CustomEvent('sidebar-sorting-save'));
                            }
                        }
                    });
                }
            }
        }"
        @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
            x-cloak
        @else
            x-cloak="-lg"
        @endif
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen, 'fi-sidebar-sorting': $store.sidebarSorting?.isSorting }"
        class="fi-sidebar fi-main-sidebar"
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_START) }}

        <div class="fi-sidebar-header-ctn">
            <header
                class="fi-sidebar-header"
            >
                @if ((! $hasTopbar) && $isSidebarCollapsibleOnDesktop)
                    <x-filament::icon-button
                        color="gray"
                        :icon="$isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronLeft : \Filament\Support\Icons\Heroicon::OutlinedChevronRight"
                        {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON` for RTL. --}}
                        :icon-alias="
                            $isRtl
                            ? [
                                \Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL,
                                \Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON,
                             ]
                            : \Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON
                        "
                        icon-size="lg"
                        :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                        x-cloak
                        x-data="{}"
                        x-on:click="$store.sidebar.open()"
                        x-show="! $store.sidebar.isOpen"
                        class="fi-sidebar-open-collapse-sidebar-btn"
                    />
                @endif

                @if ((! $hasTopbar) && ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop))
                    <x-filament::icon-button
                        color="gray"
                        :icon="$isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronRight : \Filament\Support\Icons\Heroicon::OutlinedChevronLeft"
                        {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON` for RTL. --}}
                        :icon-alias="
                            $isRtl
                            ? [
                                \Filament\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL,
                                \Filament\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON,
                             ]
                            : \Filament\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON
                        "
                        icon-size="lg"
                        :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                        x-cloak
                        x-data="{}"
                        x-on:click="$store.sidebar.close()"
                        x-show="$store.sidebar.isOpen"
                        class="fi-sidebar-close-collapse-sidebar-btn"
                    />
                @endif

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_BEFORE) }}

                <div
                    @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                        x-show="$store.sidebar.isOpen"
                    @endif
                    class="fi-sidebar-header-logo-ctn"
                >
                    @if ($homeUrl = filament()->getHomeUrl())
                        <a {{ \Filament\Support\generate_href_html($homeUrl) }}>
                            <x-filament-panels::logo />
                        </a>
                    @else
                        <x-filament-panels::logo />
                    @endif
                </div>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_AFTER) }}
            </header>
        </div>

        @if (filament()->hasTenancy() && filament()->hasTenantMenu())
            <x-filament-panels::tenant-menu />
        @endif

        @if (filament()->isGlobalSearchEnabled() && filament()->getGlobalSearchPosition() === \Filament\Enums\GlobalSearchPosition::Sidebar)
            <div
                @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                    x-show="$store.sidebar.isOpen"
                @endif
            >
                @livewire(Filament\Livewire\GlobalSearch::class)
            </div>
        @endif

        <nav class="fi-sidebar-nav">
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_START) }}

            <ul class="fi-sidebar-nav-groups">
                @foreach ($navigation as $group)
                    @php
                        $isGroupActive = $group->isActive();
                        $isGroupCollapsible = $group->isCollapsible();
                        $groupIcon = $group->getIcon();
                        $groupItems = $group->getItems();
                        $groupLabel = $group->getLabel();
                        $groupExtraSidebarAttributeBag = $group->getExtraSidebarAttributeBag();
                    @endphp

                    <x-filament-panels::sidebar.group
                        :active="$isGroupActive"
                        :collapsible="$isGroupCollapsible"
                        :icon="$groupIcon"
                        :items="$groupItems"
                        :label="$groupLabel"
                        :attributes="\Filament\Support\prepare_inherited_attributes($groupExtraSidebarAttributeBag)"
                    />
                @endforeach
            </ul>

            <script>
                var collapsedGroups = JSON.parse(
                    localStorage.getItem('collapsedGroups'),
                )

                if (collapsedGroups === null || collapsedGroups === 'null') {
                    localStorage.setItem(
                        'collapsedGroups',
                        JSON.stringify(@js(
                        collect($navigation)
                            ->filter(fn (\Filament\Navigation\NavigationGroup $group): bool => $group->isCollapsed())
                            ->map(fn (\Filament\Navigation\NavigationGroup $group): string => $group->getLabel())
                            ->values()
                            ->all()
                    )),
                    )
                }

                collapsedGroups = JSON.parse(
                    localStorage.getItem('collapsedGroups'),
                )

                document
                    .querySelectorAll('.fi-sidebar-group')
                    .forEach((group) => {
                        if (
                            !collapsedGroups.includes(group.dataset.groupLabel)
                        ) {
                            return
                        }

                        // Alpine.js loads too slow, so attempt to hide a
                        // collapsed sidebar group earlier.
                        group.querySelector(
                            '.fi-sidebar-group-items',
                        ).style.display = 'none'
                        group.classList.add('fi-collapsed')
                    })
            </script>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_END) }}
        </nav>

        @php
            $isAuthenticated = filament()->auth()->check();
            $hasDatabaseNotificationsInSidebar = filament()->hasDatabaseNotifications() && filament()->getDatabaseNotificationsPosition() === \Filament\Enums\DatabaseNotificationsPosition::Sidebar;
            $hasUserMenuInSidebar = filament()->hasUserMenu() && filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Sidebar;
            $shouldRenderFooter = $isAuthenticated && ($hasDatabaseNotificationsInSidebar || $hasUserMenuInSidebar);
        @endphp

        @if ($shouldRenderFooter)
            <div class="fi-sidebar-footer">
                @if ($hasDatabaseNotificationsInSidebar)
                    @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if ($hasUserMenuInSidebar)
                    <x-filament-panels::user-menu />
                @endif
            </div>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER) }}
    </aside>
    {{-- format-ignore-end --}}

    <x-filament-actions::modals />

    <!-- Sidebar sorting script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let draggedGroup = null;
            let draggedItem = null;
            let preventedListeners = [];

            function preventClick(e) {
                if (Alpine.store('sidebarSorting') && Alpine.store('sidebarSorting').isSorting) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }

            document.addEventListener('sidebar-sorting-start', () => {
                // 1. Save and remove href attributes to completely block navigation in sorting mode
                document.querySelectorAll('.fi-sidebar-item-btn').forEach(btn => {
                    if (btn.hasAttribute('href')) {
                        btn.setAttribute('data-href-backup', btn.getAttribute('href'));
                        btn.removeAttribute('href');
                    }
                    btn.addEventListener('click', preventClick, true);
                    preventedListeners.push(btn);
                });

                document.querySelectorAll('.fi-sidebar-group-btn').forEach(btn => {
                    btn.addEventListener('click', preventClick, true);
                    preventedListeners.push(btn);
                });

                // 2. Add drag handles
                document.querySelectorAll('.fi-sidebar-group-btn').forEach(btn => {
                    if (!btn.querySelector('.sidebar-drag-handle')) {
                        const handle = document.createElement('span');
                        handle.className = 'sidebar-drag-handle';
                        handle.innerText = '⋮⋮';
                        handle.style.marginRight = '8px';
                        handle.style.cursor = 'grab';
                        handle.style.fontWeight = 'bold';
                        handle.style.opacity = '0.7';
                        handle.style.flexShrink = '0';
                        btn.insertBefore(handle, btn.firstChild);
                    }
                });

                document.querySelectorAll('.fi-sidebar-item-btn').forEach(btn => {
                    if (!btn.querySelector('.sidebar-drag-handle')) {
                        const handle = document.createElement('span');
                        handle.className = 'sidebar-drag-handle';
                        handle.innerText = '⋮⋮';
                        handle.style.marginRight = '8px';
                        handle.style.cursor = 'grab';
                        handle.style.fontWeight = 'bold';
                        handle.style.opacity = '0.7';
                        handle.style.flexShrink = '0';
                        btn.insertBefore(handle, btn.firstChild);
                    }
                });

                // 3. Configure draggable attributes to enable nested drag & drop of items
                document.querySelectorAll('.fi-sidebar-group').forEach(group => {
                    group.setAttribute('draggable', 'true');
                });
                document.querySelectorAll('.fi-sidebar-item').forEach(item => {
                    item.setAttribute('draggable', 'true');
                });

                // 4. Make groups drop targets & drag events
                document.querySelectorAll('.fi-sidebar-group').forEach(group => {
                    group.addEventListener('dragstart', (e) => {
                        if (!Alpine.store('sidebarSorting').isSorting) return;
                        
                        // Check if we are dragging a nested item
                        const item = e.target.closest('.fi-sidebar-item');
                        if (item) {
                            draggedItem = item;
                            draggedGroup = null;
                            e.dataTransfer.setData('text/plain', 'item');
                        } else {
                            // We are dragging the group itself
                            draggedGroup = group;
                            draggedItem = null;
                            e.dataTransfer.setData('text/plain', 'group');
                        }
                        e.stopPropagation();
                    });

                    group.addEventListener('dragover', (e) => {
                        if (!Alpine.store('sidebarSorting').isSorting) return;
                        e.preventDefault();
                        if (draggedGroup && draggedGroup !== group) {
                            group.classList.add('sidebar-drag-over');
                        } else if (draggedItem) {
                            const itemsList = group.querySelector('.fi-sidebar-group-items');
                            if (itemsList) {
                                itemsList.classList.add('sidebar-drag-over-items');
                            }
                        }
                    });

                    group.addEventListener('dragleave', (e) => {
                        group.classList.remove('sidebar-drag-over');
                        const itemsList = group.querySelector('.fi-sidebar-group-items');
                        if (itemsList) {
                            itemsList.classList.remove('sidebar-drag-over-items');
                        }
                    });

                    group.addEventListener('drop', (e) => {
                        if (!Alpine.store('sidebarSorting').isSorting) return;
                        e.preventDefault();
                        group.classList.remove('sidebar-drag-over');
                        const itemsList = group.querySelector('.fi-sidebar-group-items');
                        if (itemsList) {
                            itemsList.classList.remove('sidebar-drag-over-items');
                        }

                        if (draggedGroup && draggedGroup !== group) {
                            const parent = group.parentNode;
                            const children = Array.from(parent.children);
                            const draggedIndex = children.indexOf(draggedGroup);
                            const targetIndex = children.indexOf(group);
                            if (draggedIndex < targetIndex) {
                                parent.insertBefore(draggedGroup, group.nextSibling);
                            } else {
                                parent.insertBefore(draggedGroup, group);
                            }
                        } else if (draggedItem) {
                            const targetList = group.querySelector('.fi-sidebar-group-items');
                            if (targetList && !targetList.contains(draggedItem)) {
                                targetList.appendChild(draggedItem);
                            }
                        }
                    });
                });

                // 5. Make items drop targets & drag events
                document.querySelectorAll('.fi-sidebar-item').forEach(item => {
                    item.addEventListener('dragstart', (e) => {
                        if (!Alpine.store('sidebarSorting').isSorting) {
                            e.preventDefault();
                            return;
                        }
                        draggedItem = item;
                        draggedGroup = null;
                        e.dataTransfer.setData('text/plain', 'item');
                        e.stopPropagation();
                    });

                    item.addEventListener('dragover', (e) => {
                        if (!Alpine.store('sidebarSorting').isSorting) return;
                        if (!draggedItem) return;
                        e.preventDefault();
                        e.stopPropagation();
                        item.classList.add('item-drag-over');
                    });

                    item.addEventListener('dragleave', (e) => {
                        item.classList.remove('item-drag-over');
                    });

                    item.addEventListener('drop', (e) => {
                        if (!Alpine.store('sidebarSorting').isSorting) return;
                        if (!draggedItem) return;
                        e.preventDefault();
                        e.stopPropagation();
                        item.classList.remove('item-drag-over');

                        if (draggedItem !== item) {
                            const parent = item.parentNode;
                            const targetIndex = Array.from(parent.children).indexOf(item);
                            const draggedIndex = Array.from(parent.children).indexOf(draggedItem);
                            if (draggedIndex >= 0 && draggedIndex < targetIndex) {
                                parent.insertBefore(draggedItem, item.nextSibling);
                            } else {
                                parent.insertBefore(draggedItem, item);
                            }
                        }
                    });
                });
            });

            document.addEventListener('sidebar-sorting-save', () => {
                const groups = [];
                document.querySelectorAll('.fi-sidebar-group').forEach(group => {
                    const labelEl = group.querySelector('.fi-sidebar-group-label');
                    if (labelEl) {
                        groups.push(labelEl.textContent.trim());
                    }
                });

                const resources = [];
                document.querySelectorAll('.fi-sidebar-group').forEach(group => {
                    const groupLabelEl = group.querySelector('.fi-sidebar-group-label');
                    const groupLabel = groupLabelEl ? groupLabelEl.textContent.trim() : '';

                    group.querySelectorAll('.fi-sidebar-item').forEach(item => {
                        const labelEl = item.querySelector('.fi-sidebar-item-label');
                        if (labelEl) {
                            resources.push({
                                label: labelEl.textContent.trim(),
                                group: groupLabel
                            });
                        }
                    });
                });

                fetch('/admin/sidebar/save-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ groups, resources })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Błąd zapisu kolejności.');
                        window.location.reload();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Błąd połączenia.');
                    window.location.reload();
                });
            });
        });
    </script>
</div>
