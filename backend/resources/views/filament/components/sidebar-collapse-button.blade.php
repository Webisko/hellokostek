<div class="fi-custom-sidebar-toggle-wrp">
    <div class="fi-sidebar-item" :class="{ 'justify-center': !$store.sidebar.isOpen }">
        <!-- Collapse Button -->
        <button
            x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
            class="fi-custom-sidebar-btn"
            :class="{ 'w-full': !$store.sidebar.isOpen, 'w-1/2': $store.sidebar.isOpen }"
            :title="!$store.sidebar.isOpen ? 'Rozwiń menu' : 'Zwiń menu'"
            aria-label="Rozwiń lub zwiń menu boczne"
            :aria-expanded="$store.sidebar.isOpen ? 'true' : 'false'"
        >
            <!-- Chevron left when open -->
            <svg
                x-show="$store.sidebar.isOpen"
                class="fi-custom-sidebar-icon"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2.5"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
            <!-- Chevron right when closed -->
            <svg
                x-show="!$store.sidebar.isOpen"
                x-cloak
                class="fi-custom-sidebar-icon"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2.5"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7m-8-14l7 7-7 7" />
            </svg>

            <span x-show="$store.sidebar.isOpen" class="fi-custom-sidebar-label">
                Zwiń
            </span>
        </button>

        <!-- Sorting/Layout Button (completely removed from DOM when collapsed) -->
        <template x-if="$store.sidebar.isOpen">
            <button
                x-on:click="$store.sidebarSorting?.toggle()"
                class="fi-custom-sidebar-btn w-1/2"
                :class="{ 'sorting-active': $store.sidebarSorting?.isSorting }"
                :title="$store.sidebarSorting?.isSorting ? 'Zapisz kolejność' : 'Sortuj menu'"
                :aria-label="$store.sidebarSorting?.isSorting ? 'Zapisz kolejność sortowania menu' : 'Sortuj menu boczne'"
                :aria-pressed="$store.sidebarSorting?.isSorting ? 'true' : 'false'"
            >
                <!-- Save checkmark when sorting active -->
                <svg
                    x-show="$store.sidebarSorting?.isSorting"
                    class="fi-custom-sidebar-icon text-emerald-500"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <!-- Layout grid icon when inactive -->
                <svg
                    x-show="!$store.sidebarSorting?.isSorting"
                    class="fi-custom-sidebar-icon"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                </svg>

                <span class="fi-custom-sidebar-label" x-text="$store.sidebarSorting?.isSorting ? 'Zapisz' : 'Układ'"></span>
            </button>
        </template>
    </div>
</div>
