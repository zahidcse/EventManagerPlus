<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
@include('partials.site-favicon')
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "error-container": "#ffdad6",
                    "surface-container-lowest": "#ffffff",
                    "tertiary": "#611e00",
                    "tertiary-fixed-dim": "#ffb59a",
                    "surface-container": "#edeef0",
                    "secondary-container": "#d0e1fb",
                    "on-secondary": "#ffffff",
                    "surface-container-highest": "#e1e2e4",
                    "on-surface": "#191c1e",
                    "outline-variant": "#c4c5d5",
                    "on-primary-fixed-variant": "#173bab",
                    "surface-container-high": "#e7e8ea",
                    "on-secondary-fixed-variant": "#38485d",
                    "tertiary-container": "#872d00",
                    "background": "#f8f9fb",
                    "primary-fixed": "#dde1ff",
                    "on-primary": "#ffffff",
                    "secondary": "#505f76",
                    "on-surface-variant": "#444653",
                    "on-error-container": "#93000a",
                    "surface-dim": "#d9dadc",
                    "outline": "#757684",
                    "inverse-on-surface": "#f0f1f3",
                    "secondary-fixed": "#d3e4fe",
                    "surface-tint": "#3755c3",
                    "on-secondary-fixed": "#0b1c30",
                    "surface-container-low": "#f3f4f6",
                    "error": "#ba1a1a",
                    "on-tertiary-fixed-variant": "#802a00",
                    "on-background": "#191c1e",
                    "secondary-fixed-dim": "#b7c8e1",
                    "tertiary-fixed": "#ffdbce",
                    "surface": "#f8f9fb",
                    "primary-container": "#1e40af",
                    "on-error": "#ffffff",
                    "surface-variant": "#e1e2e4",
                    "on-primary-container": "#a8b8ff",
                    "surface-bright": "#f8f9fb",
                    "primary-fixed-dim": "#b8c4ff",
                    "primary": "#00288e",
                    "inverse-surface": "#2e3132",
                    "on-primary-fixed": "#001453",
                    "on-tertiary-fixed": "#380d00",
                    "on-secondary-container": "#54647a",
                    "on-tertiary-container": "#ffa583",
                    "inverse-primary": "#b8c4ff",
                    "on-tertiary": "#ffffff"
            },
            "borderRadius": {
                    "DEFAULT": "0.125rem",
                    "lg": "0.25rem",
                    "xl": "0.5rem",
                    "full": "0.75rem"
            },
            "spacing": {
                    "sidebar-width": "300px",
                    "grid-gutter": "1.5rem",
                    "container-padding": "2rem",
                    "stack-gap": "1.5rem",
                    "component-gap": "0.75rem"
            },
            "fontFamily": {
                    "body-lg": ["Inter"],
                    "body-md": ["Inter"],
                    "headline-lg": ["Inter"],
                    "display-lg": ["Inter"],
                    "label-md": ["Inter"],
                    "headline-lg-mobile": ["Inter"],
                    "headline-md": ["Inter"]
            },
            "fontSize": {
                    "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                    "headline-lg": ["28px", {"lineHeight": "36px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                    "display-lg": ["36px", {"lineHeight": "44px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                    "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "500"}],
                    "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}]
            }
          },
        },
      }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            line-height: 1;
            letter-spacing: normal;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
            user-select: none;
        }
        /* Icon inside a colored tile — keep glyph separate from the box for true centering */
        .admin-icon-tile {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            line-height: 0;
        }
        .admin-icon-tile > .material-symbols-outlined {
            font-size: 20px;
            width: 20px;
            height: 20px;
            overflow: hidden;
        }
        .admin-icon-tile--sm > .material-symbols-outlined {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .admin-icon-tile--lg > .material-symbols-outlined {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }
        body {
            background-color: #f8f9fb;
            font-family: 'Inter', sans-serif;
        }
        /* Left admin nav: slim, rounded scrollbar that fits inverse surface */
        .admin-sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.28) transparent;
        }
        .admin-sidebar-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .admin-sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
            margin: 10px 0 12px;
        }
        .admin-sidebar-scroll::-webkit-scrollbar-thumb {
            background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.14),
                rgba(255, 255, 255, 0.22)
            );
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        .admin-sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.24),
                rgba(255, 255, 255, 0.38)
            );
            background-clip: padding-box;
        }
        .admin-sidebar-scroll::-webkit-scrollbar-thumb:active {
            background: rgba(168, 184, 255, 0.45);
            background-clip: padding-box;
        }
        /* Admin left navigation */
        .admin-sidebar {
            background: linear-gradient(180deg, #252829 0%, #1e2123 48%, #1a1c1e 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
        }
        .admin-sidebar__brand {
            padding: 1.5rem 1.25rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            flex-shrink: 0;
        }
        .admin-sidebar__logo {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2), inset 0 0 0 1px rgba(255, 255, 255, 0.08);
        }
        .admin-sidebar__role {
            display: inline-block;
            margin-top: 0.35rem;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(196, 197, 213, 0.85);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .admin-sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 0.25rem 0;
        }
        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: rgba(196, 197, 213, 0.92);
            transition: color 0.2s ease, background 0.2s ease, transform 0.15s ease;
        }
        .admin-nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }
        .admin-nav-link:active {
            transform: scale(0.98);
        }
        .admin-nav-link--active {
            color: #fff;
            background: linear-gradient(90deg, rgba(55, 85, 195, 0.35) 0%, rgba(255, 255, 255, 0.08) 100%);
            box-shadow: inset 3px 0 0 #6b8cff;
        }
        .admin-nav-link--active .material-symbols-outlined,
        .admin-nav-group[open] > .admin-nav-group__summary .material-symbols-outlined:first-child {
            font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
        }
        .admin-nav-link .material-symbols-outlined {
            font-size: 22px;
            opacity: 0.88;
        }
        .admin-nav-link--active .material-symbols-outlined {
            opacity: 1;
            color: #a8b8ff;
        }
        .admin-nav-group {
            border-radius: 0.5rem;
            transition: background 0.2s ease;
        }
        .admin-nav-group--open {
            background: rgba(255, 255, 255, 0.04);
        }
        .admin-nav-group__summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: rgba(196, 197, 213, 0.92);
            user-select: none;
            transition: color 0.2s ease, background 0.2s ease;
        }
        .admin-nav-group__summary::-webkit-details-marker {
            display: none;
        }
        .admin-nav-group__summary:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }
        .admin-nav-group--open > .admin-nav-group__summary {
            color: #fff;
        }
        .admin-nav-group__chevron {
            font-size: 18px !important;
            opacity: 0.65;
            transition: transform 0.25s ease, opacity 0.2s ease;
        }
        .admin-nav-group[open] .admin-nav-group__chevron {
            transform: rotate(180deg);
            opacity: 1;
        }
        .admin-nav-sub {
            margin: 0.25rem 0 0.5rem 0.75rem;
            padding: 0.35rem 0 0.35rem 0.875rem;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .admin-nav-sub-link {
            display: block;
            padding: 0.45rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            line-height: 1.25rem;
            color: rgba(196, 197, 213, 0.8);
            transition: color 0.2s ease, background 0.2s ease, padding-left 0.2s ease;
        }
        .admin-nav-sub-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
            padding-left: 0.875rem;
        }
        .admin-nav-sub-link--active {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: inset 2px 0 0 rgba(168, 184, 255, 0.9);
        }
        .admin-nav-sub-link--seat-active {
            color: #fecaca;
            background: linear-gradient(90deg, rgba(185, 28, 28, 0.35) 0%, rgba(127, 29, 29, 0.2) 100%);
            box-shadow: inset 2px 0 0 #f87171;
        }
        .admin-nav-sub-link--seat:hover {
            color: #fca5a5;
            background: rgba(185, 28, 28, 0.2);
        }
        .admin-nav-sub-link--seat {
            color: rgba(248, 113, 113, 0.85);
        }
        .admin-sidebar__footer {
            flex-shrink: 0;
            padding: 1rem 1.25rem 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            background: linear-gradient(0deg, rgba(0, 0, 0, 0.15) 0%, transparent 100%);
        }
        .admin-sidebar-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 55%, #1d4ed8 100%);
            box-shadow: 0 4px 14px rgba(30, 64, 175, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.15);
            transition: transform 0.15s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }
        .admin-sidebar-cta:hover {
            filter: brightness(1.08);
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
        .admin-sidebar-cta:active {
            transform: scale(0.98);
        }
        .admin-sidebar-scroll {
            padding: 1rem 0.75rem;
        }
        .admin-user-dropdown-caret {
            transition: transform 0.2s ease;
        }
        .admin-user-dropdown[open] .admin-user-dropdown-caret {
            transform: rotate(180deg);
        }
        /* Native time inputs: leave room for value + clock picker (avoids AM/PM clipping) */
        input[type="time"].event-time-input,
        input[type="time"].admin-time-input {
            min-width: 10.5rem;
            width: 10.5rem;
            max-width: 100%;
        }
        input[type="time"].event-time-input::-webkit-calendar-picker-indicator,
        input[type="time"].admin-time-input::-webkit-calendar-picker-indicator {
            margin-left: 0.35rem;
            cursor: pointer;
        }
        /* Room below page content so lists/forms are not flush with the viewport edge */
        .admin-content-column {
            padding-bottom: 3rem;
        }
        .admin-content-column > main {
            padding-bottom: 3rem;
        }
        /* Pages with a fixed bottom action bar need extra scroll space */
        .admin-has-fixed-footer {
            padding-bottom: 6.5rem !important;
        }
        .admin-list-add-btn {
            margin-top: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px 5px 5px;
            border-radius: 9999px;
            border: 1px dashed rgba(0, 40, 142, 0.28);
            background: rgba(0, 40, 142, 0.04);
            color: #00288e;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.01em;
            line-height: 1.2;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
        }
        .admin-list-add-btn:hover {
            background: rgba(0, 40, 142, 0.09);
            border-color: rgba(0, 40, 142, 0.45);
            box-shadow: 0 2px 8px rgba(0, 40, 142, 0.1);
        }
        .admin-list-add-btn:active {
            transform: scale(0.98);
        }
        .admin-list-add-btn:focus-visible {
            outline: 2px solid rgba(0, 40, 142, 0.45);
            outline-offset: 2px;
        }
        .admin-list-add-btn__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(0, 40, 142, 0.12);
            font-size: 16px;
            font-variation-settings: 'wght' 600;
            line-height: 1;
        }
        html.dark .admin-list-add-btn {
            border-color: rgba(184, 196, 255, 0.35);
            background: rgba(184, 196, 255, 0.06);
            color: #b8c4ff;
        }
        html.dark .admin-list-add-btn:hover {
            background: rgba(184, 196, 255, 0.12);
            border-color: rgba(184, 196, 255, 0.55);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
        }
        html.dark .admin-list-add-btn__icon {
            background: rgba(184, 196, 255, 0.18);
        }
        html.dark .admin-list-add-btn:focus-visible {
            outline-color: rgba(184, 196, 255, 0.5);
        }
    </style>