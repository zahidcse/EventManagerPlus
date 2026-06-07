  .attendee-ticket-fields,
  .attendee-ticket-fields label,
  .attendee-ticket-card,
  .attendee-ticket-grid,
  .attendee-ticket-inline,
  .seat-plan-attendee-card,
  .seat-plan-attendees {
    overflow: visible;
  }
  .ep-layout .option:has(.attendee-custom-select.is-open),
  .attendee-ticket-card:has(.attendee-custom-select.is-open),
  .seat-plan-attendee-card:has(.attendee-custom-select.is-open),
  #seat-plan-picker-modal .seat-plan-attendee-card:has(.attendee-custom-select.is-open),
  #seat-plan-attendee-edit-modal .seat-plan-attendee-card:has(.attendee-custom-select.is-open) {
    position: relative;
    z-index: 200;
    overflow: visible;
  }
  #seat-plan-picker-step-attendees,
  #seat-plan-picker-attendees {
    overflow: visible;
  }
  .attendee-custom-select { position: relative; width: 100%; }
  .attendee-custom-select.is-open { z-index: 210; }
  .attendee-custom-select-native {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
  }
  .attendee-custom-select .attendee-custom-select-trigger {
    width: 100%; box-sizing: border-box; border: 1px solid rgba(148, 163, 184, .55);
    border-radius: 8px; background: transparent; color: inherit; padding: 9px 36px 9px 10px;
    font: inherit; text-align: left; cursor: pointer; position: relative; outline: none;
  }
  .attendee-custom-select .attendee-custom-select-trigger.is-placeholder {
    border: 1px solid rgba(148, 163, 184, .55);
    color: rgba(148, 163, 184, .85);
  }
  .attendee-custom-select .attendee-custom-select-trigger::after {
    content: ''; position: absolute; right: 14px; top: 50%; width: 7px; height: 7px;
    margin-top: -5px; border-right: 2px solid currentColor; border-bottom: 2px solid currentColor;
    transform: rotate(45deg); opacity: .75; pointer-events: none;
  }
  .attendee-custom-select.is-open .attendee-custom-select-trigger,
  .attendee-custom-select .attendee-custom-select-trigger:focus-visible {
    border: 1px solid #d946ef;
    box-shadow: 0 0 0 3px rgba(217, 70, 239, .18);
  }
  .attendee-custom-select.is-open .attendee-custom-select-trigger {
    border: 1px solid #d946ef;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    border-bottom-color: #d946ef;
    box-shadow: none;
  }
  .attendee-custom-select-menu {
    position: absolute; z-index: 220; left: 0; right: 0; top: calc(100% - 1px);
    margin: 0; padding: 4px; list-style: none;
    border: 1px solid #d946ef;
    border-top: 1px solid rgba(148, 163, 184, .35);
    border-radius: 0 0 8px 8px;
    background: var(--ep-surface, var(--card, #241d33));
    box-shadow: 0 0 0 3px rgba(217, 70, 239, .18), 0 12px 32px rgba(15, 23, 42, .35);
    max-height: 200px; overflow-y: auto;
  }
  .attendee-custom-select-menu.is-portal {
    position: fixed;
    right: auto;
    z-index: 1400;
  }
  .attendee-custom-select-menu.is-portal.is-drop-up {
    border-top: 1px solid #d946ef;
    border-bottom: 1px solid rgba(148, 163, 184, .35);
    box-shadow: 0 0 0 3px rgba(217, 70, 239, .18), 0 -8px 24px rgba(15, 23, 42, .35);
  }
  #seat-plan-attendee-edit-modal .attendee-custom-select-menu,
  #seat-plan-attendee-edit-modal .attendee-custom-select-menu.is-portal {
    background: var(--card, #241d33);
    color: var(--fg, #f7f5fb);
    border-color: #d946ef;
    box-shadow: 0 0 0 3px rgba(217, 70, 239, .18), 0 12px 32px rgba(10, 5, 25, .45);
  }
  #seat-plan-attendee-edit-modal .attendee-custom-select-option:hover,
  #seat-plan-attendee-edit-modal .attendee-custom-select-option.is-selected {
    background: rgba(217, 70, 239, .22);
  }
  .attendee-custom-select-menu[hidden] { display: none !important; }
  .attendee-custom-select-option {
    padding: 8px 10px; border-radius: 6px; cursor: pointer; color: inherit;
  }
  .attendee-custom-select-option:hover,
  .attendee-custom-select-option.is-selected {
    background: rgba(217, 70, 239, .18);
  }
