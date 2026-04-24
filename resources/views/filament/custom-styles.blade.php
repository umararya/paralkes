{{--
    resources/views/filament/custom-styles.blade.php
    Diinjeksi ke <head> semua halaman Filament via ->extraGloballyRenderedView()
--}}
<style>
    /* ================================================================
       PARALKES+ — Custom Filament CSS
       Tema  : Hitam-Putih / Zinc Premium
       Target: Input, Card, Modal, Table, Button, Sidebar, Login Page
       ================================================================ */

    /* ── 1. CSS Variables override ─────────────────────────────────── */
    :root {
        --radius-sm  : 0.5rem;   /* 8px  */
        --radius-md  : 0.75rem;  /* 12px */
        --radius-lg  : 1rem;     /* 16px */
        --radius-xl  : 1.25rem;  /* 20px */
    }

    /* ── 2. Form Input — rounded-xl ────────────────────────────────── */
    .fi-input,
    .fi-select-input,
    .fi-textarea,
    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="number"],
    input[type="search"],
    select,
    textarea {
        border-radius: var(--radius-xl) !important;
    }

    /* ── 3. Card / Section ─────────────────────────────────────────── */
    .fi-section,
    .fi-card,
    .fi-wi-stats-overview-stat,   /* widget stats card */
    .fi-ta-ctn,                   /* table container  */
    .fi-fo-component-ctn {
        border-radius: var(--radius-xl) !important;
    }

    /* ── 4. Modal ───────────────────────────────────────────────────── */
    .fi-modal-window {
        border-radius: var(--radius-xl) !important;
    }

    /* ── 5. Dropdown / Select Panel ────────────────────────────────── */
    .fi-dropdown-panel,
    .choices__list--dropdown,
    .fi-select-option-list {
        border-radius: var(--radius-lg) !important;
    }

    /* ── 6. Button — sedikit lebih rounded dari default ────────────── */
    .fi-btn {
        border-radius: var(--radius-lg) !important;
    }

    /* ── 7. Badge ───────────────────────────────────────────────────── */
    .fi-badge {
        border-radius: 999px !important; /* pill */
    }

    /* ── 8. Sidebar (light mode) ────────────────────────────────────── */
    .fi-sidebar {
        border-right: 1px solid rgb(228 228 231); /* zinc-200 */
    }

    /* ── 9. Login page — card sign-in lebih rounded & subtle shadow ── */
    .fi-simple-main {
        border-radius: var(--radius-xl) !important;
        box-shadow:
            0 0 0 1px rgb(39 39 42 / 0.06),
            0 8px 32px -4px rgb(39 39 42 / 0.12) !important;
    }

    /* ── 10. Dark Mode — sidebar & topbar solid zinc-950 ───────────── */
    .dark .fi-sidebar,
    .dark .fi-topbar {
        background-color: rgb(9 9 11) !important; /* zinc-950 */
    }

    .dark .fi-sidebar {
        border-right: 1px solid rgb(39 39 42); /* zinc-800 */
    }

    /* Input dark mode border */
    .dark .fi-input,
    .dark input[type="password"],
    .dark input[type="email"],
    .dark input[type="text"] {
        background-color: rgb(24 24 27) !important; /* zinc-900 */
        border-color: rgb(63 63 70) !important;     /* zinc-700 */
    }

    /* ── 11. Fokus ring — lebih tebal & zinc ───────────────────────── */
    .fi-input:focus,
    input:focus,
    select:focus,
    textarea:focus {
        outline: 2px solid rgb(113 113 122) !important; /* zinc-500 */
        outline-offset: 2px;
    }

    /* ── 12. Scrollbar tipis dan elegan (webkit) ────────────────────── */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb {
        background: rgb(161 161 170); /* zinc-400 */
        border-radius: 9999px;
    }
    .dark ::-webkit-scrollbar-thumb { background: rgb(63 63 70); } /* zinc-700 */
</style>