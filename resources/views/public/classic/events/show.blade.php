@extends('public.classic.layouts.app')

@section('title', $event->title.' - '.$siteName)
@section('meta_description')
{{ \Illuminate\Support\Str::limit(trim(strip_tags($event->description ?: ('Book tickets to '.$event->title))), 155) }}
@endsection

@push('head')
@if($event->cover_image_path)
<meta property="og:image" content="{{ asset('uploads/'.$event->cover_image_path) }}" />
@endif
@unless(\App\Support\PublicFrontendTheme::isClassicLight())
<style>
  .attendee-ticket-block { margin-top: 0; padding-top: 0; border-top: 0; }
  .attendee-ticket-inline { flex: 0 0 100%; width: 100%; min-width: 100%; margin-top: 2px; padding-top: 16px; border-top: 1px solid rgba(148, 163, 184, .25); }
  .attendee-ticket-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 10px; width: 100%; }
  .attendee-ticket-grid.attendee-ticket-grid--inline { margin-top: 0; width: 100%; }
  .attendee-ticket-card { min-width: 0; border: 0; border-radius: 12px; padding: 14px; background: rgba(255, 255, 255, .045); box-shadow: 0 10px 28px rgba(15, 23, 42, .08); }
  .attendee-ticket-title { font-weight: 800; font-size: 14px; margin: 0 0 4px; }
  .attendee-ticket-subtitle { font-size: 12px; color: rgba(148, 163, 184, .95); margin: 0 0 12px; }
  .attendee-ticket-fields { display: grid; gap: 10px; }
  .attendee-ticket-fields label { display: grid; gap: 5px; min-width: 0; font-size: 12px; font-weight: 700; color: rgba(148, 163, 184, .95); }
  .attendee-ticket-fields input,
  .attendee-ticket-fields select { width: 100%; box-sizing: border-box; border: 1px solid rgba(148, 163, 184, .55); border-radius: 8px; background: transparent; color: inherit; padding: 9px 10px; font: inherit; outline: none; }
  .attendee-ticket-fields input:focus,
  .attendee-ticket-fields select:focus { border-color: #d946ef; box-shadow: 0 0 0 3px rgba(217, 70, 239, .18); }
@include('public.partials.attendee-custom-select-styles')
  @media (max-width: 760px) {
    .attendee-ticket-grid { grid-template-columns: 1fr; }
  }
  .seat-plan-stage {
    margin: 0 auto 24px; max-width: 70%; text-align: center; padding: 10px 12px;
    background: linear-gradient(180deg, rgba(30, 41, 59, .12), rgba(15, 23, 42, .04));
    border: 1px solid rgba(148, 163, 184, .35);
    border-top: 1px solid rgba(148, 163, 184, .35);
    border-radius: 15px; letter-spacing: .28em;
    font-weight: 700; font-size: 12px; color: #7c3aed;
  }
  .seat-plan-wrap { overflow-x: auto; padding: 8px 0 16px; width: 100%; }
  .seat-plan-grid {
    display: table !important;
    border-collapse: separate;
    border-spacing: 6px;
    margin: 0 auto;
    width: auto;
  }
  .seat-plan-cell { display: table-cell; vertical-align: middle; text-align: center; padding: 0; }
  .seat-plan-row-label { color: rgba(148, 163, 184, .9); font-size: 11px; padding: 0 6px; min-width: 20px; text-align: right; }
  .seat-plan-seat {
    width: 34px; height: 34px; border-radius: 6px 6px 9px 9px; border: 1px solid rgba(148, 163, 184, .45);
    background: rgba(30, 41, 59, .08); color: #1a1300; font-size: 10px; font-weight: 700; cursor: pointer; padding: 0;
  }
  .seat-plan-seat.has-ticket-color.is-available:not(.is-selected):not(:disabled) {
    background: var(--seat-ticket-color);
    border-color: var(--seat-ticket-color);
    color: #1a1300;
  }
  .seat-plan-seat.is-booked, .seat-plan-seat.is-reserved, .seat-plan-seat.is-blocked {
    opacity: .65; cursor: not-allowed; background: #94a3b8 !important; border-color: #64748b !important; color: #f8fafc !important;
  }
  .seat-plan-seat.is-booked { background: #b91c1c !important; border-color: #991b1b !important; }
  .seat-plan-seat.is-reserved { background: #a16207 !important; border-color: #854d0e !important; }
  .seat-plan-seat.is-selected {
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px #7c3aed;
  }
  .seat-plan-seat.has-ticket-color.is-selected.is-available {
    background: var(--seat-ticket-color);
    border-color: var(--seat-ticket-color);
    color: #1a1300 !important;
  }
  .seat-plan-seat:disabled { cursor: not-allowed; }
  .seat-plan-aisle, .seat-plan-empty { display: inline-block; width: 34px; height: 34px; }
  .seat-plan-legend {
    display: flex; flex-wrap: nowrap; gap: 10px 14px; justify-content: center; align-items: center;
    margin: 8px 0 18px; font-size: 12px; color: rgba(148, 163, 184, .95);
    overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch;
  }
  .seat-plan-legend-heading {
    flex: 0 0 auto; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    color: rgba(148, 163, 184, .8); white-space: nowrap;
  }
  .seat-plan-legend-item--status {
    border-left: 1px solid rgba(148, 163, 184, .35); padding-left: 12px; margin-left: 2px;
  }
  .seat-plan-legend-item { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; white-space: nowrap; }
  .seat-plan-legend-label { display: inline-flex; align-items: center; gap: 5px; line-height: 1.25; white-space: nowrap; }
  .seat-plan-legend-name { font-weight: 700; color: inherit; }
  .seat-plan-legend-price { font-size: 11px; color: rgba(148, 163, 184, .95); font-weight: 600; }
  .seat-plan-price-was { text-decoration: line-through; margin-right: 4px; opacity: .85; font-weight: 500; }
  .seat-plan-swatch { display: inline-block; width: 14px; height: 14px; border-radius: 3px; flex-shrink: 0; border: 1px solid rgba(148, 163, 184, .4); }
  .seat-plan-swatch.is-selected { background: #7c3aed; border-color: #6d28d9; box-shadow: 0 0 0 1px #fff, 0 0 0 2px #7c3aed; }
  .seat-plan-swatch.is-booked { background: #b91c1c; border-color: #991b1b; }
  .seat-plan-swatch.is-reserved { background: #a16207; border-color: #854d0e; }
  .seat-plan-swatch.is-blocked { background: #64748b; border-color: #475569; }
  .seat-plan-addons-heading { margin: 22px 0 8px; font-size: 16px; font-weight: 700; }
  .seat-plan-addon-options { margin-top: 0; }
  .seat-plan-attendees-section { margin-top: 22px; }
  .seat-plan-attendees-heading { margin: 0 0 12px; font-size: 16px; font-weight: 700; }
  .seat-plan-attendees {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 0;
  }
  .seat-plan-attendee-card { border: 1px solid rgba(148, 163, 184, .35); border-radius: 12px; padding: 14px; background: rgba(255, 255, 255, .04); }
  .seat-plan-attendee-card .attendee-ticket-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  @media (max-width: 760px) {
    .seat-plan-attendees { grid-template-columns: 1fr; }
    .seat-plan-attendee-card .attendee-ticket-fields { grid-template-columns: 1fr; }
  }
  .seat-plan-modal { position: fixed; inset: 0; z-index: 1200; display: flex; align-items: center; justify-content: center; padding: 16px; }
  .seat-plan-modal[hidden] { display: none !important; }
  .seat-plan-modal-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .55); }
  .seat-plan-modal-panel {
    position: relative; z-index: 1; width: min(100%, 420px); border-radius: 12px; padding: 20px;
    background: #fff; color: #0f172a; border: 1px solid rgba(148, 163, 184, .4);
    box-shadow: 0 20px 50px rgba(15, 23, 42, .25);
  }
  .seat-plan-modal-title { margin: 0 0 8px; font-size: 18px; font-weight: 700; }
  .seat-plan-modal-text { margin: 0 0 16px; font-size: 14px; color: #475569; line-height: 1.45; }
  .seat-plan-modal-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; }
  .seat-plan-modal-btn {
    border-radius: 8px; padding: 9px 14px; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid transparent;
  }
  .seat-plan-modal-btn--cancel { background: transparent; border-color: rgba(148, 163, 184, .55); color: inherit; }
  .seat-plan-modal-btn--confirm { background: #7c3aed; border-color: #6d28d9; color: #fff; }
  #seat-plan-booking .seat-plan-ticket-list--grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px;
    margin: 16px 0 0;
  }
  #seat-plan-booking .seat-plan-ticket-pick {
    width: 100%;
    text-align: left;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    padding: 16px 18px;
    border-radius: 14px;
    border: 1px solid rgba(148, 163, 184, .35);
    border-top: 3px solid var(--ticket-color, #d946ef);
    background: rgba(255, 255, 255, .04);
    cursor: pointer;
    transition: border-color .2s, background .2s, box-shadow .2s, transform .15s;
  }
  #seat-plan-booking .seat-plan-ticket-pick:hover:not(:disabled) {
    border-color: var(--ticket-color, #d946ef);
    background: rgba(217, 70, 239, .08);
    box-shadow: 0 6px 20px rgba(0, 0, 0, .22);
    transform: translateY(-2px);
  }
  #seat-plan-booking .seat-plan-ticket-pick.is-active {
    border-color: var(--ticket-color, #d946ef);
    background: rgba(217, 70, 239, .14);
    box-shadow: 0 0 0 1px var(--ticket-color, #d946ef);
  }
  #seat-plan-booking .seat-plan-ticket-pick.is-disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
  }
  .seat-plan-ticket-cta {
    margin-top: 6px;
    font-size: 13px;
    font-weight: 700;
    color: #e879f9;
    letter-spacing: .02em;
  }
  .seat-plan-open-map-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 100%;
    margin: 14px 0 0; padding: 12px 18px; border-radius: 12px; border: 1px solid rgba(148, 163, 184, .4);
    background: transparent;
    color: inherit; font-size: 14px; font-weight: 600; cursor: pointer;
    transition: border-color .2s, background .2s;
  }
  .seat-plan-open-map-btn:hover { border-color: #d946ef; background: rgba(217, 70, 239, .1); }
  .seat-plan-ticket-pick-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 100%;
    min-width: 0;
  }
  .seat-plan-ticket-name {
    font-size: 15px;
    font-weight: 700;
    line-height: 1.25;
  }
  .seat-plan-ticket-desc {
    font-size: 12px;
    line-height: 1.35;
    color: rgba(148, 163, 184, .95);
  }
  .seat-plan-ticket-rem { opacity: .9; }
  .seat-plan-ticket-price {
    font-size: 16px;
    font-weight: 700;
    color: #e879f9;
    margin-top: 4px;
  }
  .seat-plan-ticket-empty { grid-column: 1 / -1; margin: 0; }
  @media (max-width: 520px) {
    #seat-plan-booking .seat-plan-ticket-list--grid { grid-template-columns: 1fr; }
  }
  body.seat-plan-picker-open { overflow: hidden; }
  .seat-plan-picker-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(217, 70, 239, .55) rgba(148, 163, 184, .12);
    scrollbar-gutter: stable;
  }
  .seat-plan-picker-scroll::-webkit-scrollbar {
    width: 10px;
    height: 10px;
  }
  .seat-plan-picker-scroll::-webkit-scrollbar-track {
    background: rgba(148, 163, 184, .1);
    border-radius: 999px;
    margin: 4px;
  }
  .seat-plan-picker-scroll::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, rgba(217, 70, 239, .75), rgba(124, 58, 237, .75));
    border-radius: 999px;
    border: 2px solid transparent;
    background-clip: padding-box;
  }
  .seat-plan-picker-scroll::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #e879f9, #a855f7);
    background-clip: padding-box;
  }
  .seat-plan-picker-scroll::-webkit-scrollbar-corner {
    background: transparent;
  }
  .seat-plan-picker-modal {
    z-index: 1300;
    align-items: flex-start;
    padding: 24px 16px;
    overflow-y: auto;
  }
  .seat-plan-picker-panel {
    position: relative;
    z-index: 1;
    width: min(100%, 1280px);
    max-height: min(92vh, 900px);
    min-height: 0;
    display: flex;
    flex-direction: column;
    border-radius: 16px;
    overflow: hidden;
    background: var(--card);
    color: var(--fg);
    border: 1px solid var(--border);
    box-shadow: 0 24px 60px rgba(10, 5, 25, .55);
  }
  .seat-plan-picker-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px 0;
    flex-shrink: 0;
  }
  .seat-plan-picker-step {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  .seat-plan-picker-header-main {
    min-width: 0;
    flex: 1 1 auto;
  }
  .seat-plan-picker-session-date {
    margin: 0 0 6px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .02em;
    color: #e879f9;
    text-transform: none;
  }
  .seat-plan-picker-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--fg);
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    flex-shrink: 0;
  }
  .seat-plan-picker-subtitle {
    margin: 8px 20px 0;
    padding-left: 12px;
    border-left: 4px solid #d946ef;
    font-size: 14px;
    flex-shrink: 0;
  }
  .seat-plan-picker-body {
    padding: 8px 20px 16px;
    overflow: auto;
    flex: 1 1 auto;
    min-height: 0;
    -webkit-overflow-scrolling: touch;
  }
  .seat-plan-picker-body .seat-plan-wrap {
    overflow-x: auto;
    padding: 8px 4px 16px;
    margin: 0 -4px;
  }
  .seat-plan-picker-body .seat-plan-wrap,
  .seat-plan-picker-body .seat-plan-legend {
    scrollbar-width: thin;
    scrollbar-color: rgba(217, 70, 239, .45) rgba(148, 163, 184, .1);
  }
  .seat-plan-picker-body .seat-plan-wrap::-webkit-scrollbar,
  .seat-plan-picker-body .seat-plan-legend::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  .seat-plan-picker-body .seat-plan-wrap::-webkit-scrollbar-thumb,
  .seat-plan-picker-body .seat-plan-legend::-webkit-scrollbar-thumb {
    background: rgba(217, 70, 239, .5);
    border-radius: 999px;
  }
  .seat-plan-picker-step[hidden] { display: none !important; }
  .seat-plan-picker-attendees-intro {
    margin: 8px 20px 0;
    font-size: 14px;
    color: var(--muted);
    flex-shrink: 0;
  }
  .seat-plan-picker-body--attendees {
    padding-top: 12px;
    overflow: auto;
    min-height: 0;
  }
  .seat-plan-picker-body--attendees .seat-plan-attendees {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }
  @media (max-width: 760px) {
    .seat-plan-picker-body--attendees .seat-plan-attendees { grid-template-columns: 1fr; }
  }
  .seat-plan-picker-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px 18px;
    border-top: 1px solid var(--border);
    background: rgba(0, 0, 0, .15);
    flex-shrink: 0;
  }
  .seat-plan-picker-footer[hidden] { display: none !important; }
  .seat-plan-modal-btn--confirm:disabled {
    opacity: .45;
    cursor: not-allowed;
  }
  .seat-plan-picker-modal .seat-plan-modal-btn--confirm {
    background: #d946ef;
    border-color: #c026d3;
    color: #fff;
  }
  .seat-plan-ticket-dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 2px rgba(255,255,255,.25); }
  .seat-plan-post-selection { margin-top: 24px; padding-top: 20px; border-top: 1px solid rgba(148, 163, 184, .25); }
  .seat-plan-seat.is-tier-muted { opacity: .28; cursor: not-allowed; filter: grayscale(0.6); }
  #seat-plan-attendees input, #seat-plan-attendees select, #seat-plan-attendees textarea,
  #seat-plan-picker-attendees input, #seat-plan-picker-attendees select, #seat-plan-picker-attendees textarea { pointer-events: auto; }
  .order-seat-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 1px rgba(255,255,255,.35); }
  .order-line-seat {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex-wrap: wrap;
  }
  .order-seat-body { display: grid; gap: 2px; min-width: 0; flex: 1 1 auto; }
  .order-seat-actions {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-left: auto;
    flex-shrink: 0;
  }
  .order-seat-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    padding: 0;
    border-radius: 8px;
    border: 1px solid rgba(148, 163, 184, .4);
    background: rgba(255, 255, 255, .06);
    color: inherit;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
    transition: border-color .15s, background .15s, color .15s;
  }
  .order-seat-action-btn:hover {
    border-color: #d946ef;
    background: rgba(217, 70, 239, .12);
    color: #e879f9;
  }
  .order-seat-action-btn--remove:hover {
    border-color: #f87171;
    background: rgba(248, 113, 113, .12);
    color: #fca5a5;
  }
  .order-seat-action-btn svg {
    width: 14px;
    height: 14px;
    display: block;
  }
  .seat-plan-attendee-edit-modal {
    z-index: 1350;
    align-items: flex-start;
    padding: 24px 16px;
    overflow-y: auto;
  }
  .seat-plan-attendee-edit-modal .seat-plan-modal-backdrop {
    background: rgba(10, 5, 25, .72);
  }
  .seat-plan-attendee-edit-panel {
    position: relative;
    z-index: 1;
    width: min(100%, 520px);
    max-height: min(88vh, 720px);
    display: flex;
    flex-direction: column;
    border-radius: 16px;
    overflow: hidden;
    background: var(--card);
    color: var(--fg);
    border: 1px solid var(--border);
    box-shadow: 0 24px 60px rgba(10, 5, 25, .55);
  }
  .seat-plan-attendee-edit-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px 0;
  }
  .seat-plan-attendee-edit-header-main {
    min-width: 0;
    flex: 1 1 auto;
  }
  .seat-plan-attendee-edit-modal .seat-plan-modal-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: var(--fg);
  }
  .seat-plan-attendee-edit-subtitle {
    margin: 8px 0 0;
    padding-left: 12px;
    border-left: 4px solid #d946ef;
    font-size: 14px;
    color: var(--muted);
    line-height: 1.45;
  }
  .seat-plan-attendee-edit-body {
    padding: 16px 20px;
    overflow-y: auto;
    flex: 1 1 auto;
    min-height: 0;
    -webkit-overflow-scrolling: touch;
  }
  .seat-plan-attendee-edit-mount .seat-plan-attendee-card {
    margin: 0;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px;
    background: rgba(255, 255, 255, .04);
  }
  .seat-plan-attendee-edit-mount .attendee-ticket-title {
    color: var(--fg);
    font-weight: 800;
  }
  .seat-plan-attendee-edit-mount .attendee-ticket-subtitle {
    color: var(--muted);
  }
  .seat-plan-attendee-edit-mount .attendee-ticket-fields label {
    color: var(--muted);
  }
  .seat-plan-attendee-edit-mount input,
  .seat-plan-attendee-edit-mount .attendee-custom-select .attendee-custom-select-trigger {
    background: rgba(15, 23, 42, .28);
    border-color: var(--border);
    color: var(--fg);
  }
  .seat-plan-attendee-edit-mount input:focus,
  .seat-plan-attendee-edit-mount .attendee-custom-select.is-open .attendee-custom-select-trigger,
  .seat-plan-attendee-edit-mount .attendee-custom-select .attendee-custom-select-trigger:focus-visible {
    border-color: #d946ef;
    box-shadow: 0 0 0 3px rgba(217, 70, 239, .18);
  }
  .seat-plan-attendee-edit-mount .attendee-ticket-fields {
    grid-template-columns: 1fr;
  }
  .seat-plan-attendee-edit-footer {
    margin-top: 0;
  }
  .seat-plan-attendee-edit-modal .seat-plan-modal-btn--cancel {
    background: transparent;
    border-color: var(--border);
    color: var(--fg);
  }
  .seat-plan-attendee-edit-modal .seat-plan-modal-btn--cancel:hover {
    border-color: #d946ef;
    background: rgba(217, 70, 239, .1);
    color: #e879f9;
  }
  .seat-plan-attendee-edit-modal .seat-plan-modal-btn--confirm {
    background: #d946ef;
    border-color: #c026d3;
    color: #fff;
  }
  .seat-plan-attendee-edit-modal .seat-plan-modal-btn--confirm:hover {
    background: #e879f9;
    border-color: #d946ef;
  }
  .order-seat-primary { font-weight: 700; font-size: 13px; }
  .order-seat-attendee { font-size: 12px; color: var(--muted); }
  .order-seat-type { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
</style>
@else
<link rel="stylesheet" href="{{ asset('themes/classic-light/booking.css') }}" />
@endunless
@endpush

@php
  $priceFromJs = $priceFrom !== null ? (float) $priceFrom : 0;
@endphp

@section('content')
  @if(session('booked'))
    <div class="alert-success" role="status">
      {{ session('booked') }}
      @if(session('booked_account_ready'))
        <span class="booked-account-link-wrap"> Go to <a href="{{ route('account.index') }}">My account</a> for your bookings.</span>
      @endif
    </div>
  @endif

  @if(session('book_error'))
    <div class="alert-error" role="alert">{{ session('book_error') }}</div>
  @endif
  @if(request('payment') === 'cancelled')
    <div class="alert-error" role="alert">Payment was cancelled.</div>
  @endif

  @if($errors->any())
    <div class="alert-error" role="alert">
      <strong>Please fix the following:</strong>
      <ul style="margin:8px 0 0 18px;padding:0">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
      </ul>
    </div>
  @endif

  @php
    $hero = $galleryUrls->first() ?: 'https://images.unsplash.com/photo-1506157786151-b8491531f063?w=1600&q=80';
    $priceFromDisp = $priceFrom !== null ? ($priceFrom == floor($priceFrom) ? number_format($priceFrom, 0) : number_format($priceFrom, 2)) : '0';
    $sidebarWhen = $event->sidebarWhenLines();
    $mapUrl = $event->mapEmbedUrl();
    $addrOrLoc = $event->fullVenueAddressLine() !== '' ? $event->fullVenueAddressLine() : $event->locationLabel();
    $pmStripe = !empty($showStripePayments);
    $pmPayPal = !empty($showPayPalPayments);
    $pmRazorpay = !empty($showRazorpayPayments);
    $pmSslcommerz = !empty($showSslCommerzPayments);
  @endphp

  @include('public.partials.event-detail-hero', [
    'event' => $event,
    'galleryUrls' => $galleryUrls,
    'hero' => $hero,
    'sidebarWhen' => $sidebarWhen,
    'addrOrLoc' => $addrOrLoc,
  ])
  <form method="post" action="{{ route('events.book', $event) }}" id="booking-form">
  @csrf
  <div class="layout layout--event-detail">
    @include('public.partials.event-detail-about', ['event' => $event, 'variantClass' => 'layout-about--mobile'])

    <div class="layout-main">
      @include('public.partials.event-detail-about', ['event' => $event, 'variantClass' => 'layout-about--desktop'])

      <div class="layout-event-timeline">
        @include('public.partials.event-detail-timeline', ['event' => $event])
      </div>

      <div class="layout-booking-main">
      @include('public.classic.partials.event-booking-session-date')

      @php
        $usesPerDayCart = ($event->schedule_type ?? 'single') !== 'single' && count($event->bookableOccurrenceDateStrings()) > 0;
        $hasSeatPlanBooking = !empty($useSeatPlanBooking) && !empty($seatPlanViewData);
      @endphp
      @if($hasSeatPlanBooking)
        @php
          $seatPlanMultiDay = ($event->schedule_type ?? 'single') !== 'single' && count($event->bookableOccurrenceDateStrings()) > 0;
          $seatPlanActiveDate = old('occurrence_date', $event->bookableOccurrenceDateStrings()[0] ?? '');
          $seatPlanActiveLabel = $seatPlanActiveDate !== '' ? \Illuminate\Support\Carbon::parse($seatPlanActiveDate)->format('D, M j, Y') : '';
        @endphp
        <div
          class="booking-cart-root booking-cart--seat-plan {{ $seatPlanMultiDay ? 'booking-cart--seat-plan-multi' : '' }}"
          id="booking-cart-seat-plan"
          @if($seatPlanMultiDay) data-active-session-date="{{ $seatPlanActiveDate }}" data-active-session-label="{{ $seatPlanActiveLabel }}" @endif
        >
          @include('public.classic.partials.event-booking-seat-plan', ['event' => $event, 'seatPlanViewData' => $seatPlanViewData, 'attendeeSettings' => $attendeeSettings])
        </div>
      @else
      <div class="booking-cart-root {{ $usesPerDayCart ? 'booking-cart--multi' : 'booking-cart--single' }}">
        @if($usesPerDayCart)
          @php
            $bookableDays = $event->bookableOccurrenceDateStrings();
          @endphp
          <div class="session-date-tabs booking-day-tabs" role="tablist" aria-label="Choose event day">
            @foreach($bookableDays as $i => $d)
              @php $dayCarbon = \Illuminate\Support\Carbon::parse($d); @endphp
              <button
                type="button"
                class="session-date-tab booking-day-tab {{ $i === 0 ? 'is-active' : '' }}"
                role="tab"
                id="booking-day-tab-{{ $loop->index }}"
                aria-controls="booking-day-panel-{{ $loop->index }}"
                aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                tabindex="{{ $i === 0 ? '0' : '-1' }}"
                data-day-target="booking-day-panel-{{ $loop->index }}"
                data-session-date="{{ $d }}"
              >
                <span class="session-date-tab-inner">{{ $dayCarbon->format('D, M j') }}</span>
              </button>
            @endforeach
          </div>
          @foreach($bookableDays as $i => $d)
            @php $dayCarbon = \Illuminate\Support\Carbon::parse($d); @endphp
            <section
              class="block per-session-cart booking-day-panel {{ $i === 0 ? 'is-active' : '' }}"
              id="booking-day-panel-{{ $loop->index }}"
              role="tabpanel"
              aria-labelledby="booking-day-tab-{{ $loop->index }}"
              @if($i !== 0) hidden @endif
              data-session-date="{{ $d }}"
              data-session-label="{{ $dayCarbon->format('D, M j, Y') }}"
            >
              <h2>{{ $dayCarbon->format('l, M j, Y') }}</h2>
              <p class="body-text ep-per-day-hint" style="margin-top:0">Tickets and add-ons below apply to this day only.</p>
              <h3 class="ep-subhead-tickets" style="margin:12px 0 8px;font-size:16px;font-weight:700">Tickets</h3>
              @include('public.classic.partials.event-booking-ticket-options', ['event' => $event, 'perDayDate' => $d])
            </section>
          @endforeach
        @else
          <section class="block">
            <h2>Choose your tickets</h2>
            @if($event->additionalServices->isNotEmpty())
              <p class="body-text ep-event-addons-intro">Optional extras appear below the ticket tiers.</p>
            @endif
            @include('public.classic.partials.event-booking-ticket-options', ['event' => $event, 'perDayDate' => null])
          </section>
        @endif
      </div>
      @endif
      </div>

      <div class="layout-event-details">
      @if($event->organizer)
      <section class="block">
        <h2>Organizer</h2>
        <div class="organizer">
          @php
            $orgImg = $event->organizer->photo_path ? asset('uploads/'.$event->organizer->photo_path) : 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=400&q=80';
          @endphp
          <img src="{{ $orgImg }}" alt="{{ $event->organizer->name }}" />
          <div>
            <div class="name">{{ $event->organizer->name }}</div>
            <div class="desc">{{ $event->organizer->bio ?: ($event->organizer->company_name ?: '') }}</div>
          </div>
        </div>
      </section>
      @endif

      @if($event->speakers->isNotEmpty())
      <section class="block">
        <h2>Speakers &amp; performers</h2>
        <div class="organizer-list">
          @foreach($event->speakers as $speaker)
            <div class="organizer">
              <img src="{{ $speaker->photoUrl() ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&q=80' }}" alt="{{ $speaker->name }}" />
              <div>
                <div class="name">{{ $speaker->name }}</div>
                <div class="desc">{{ $speaker->bio ?: ($speaker->headline ?: 'Speaker') }}</div>
              </div>
            </div>
          @endforeach
        </div>
      </section>
      @endif

      @include('public.partials.event-detail-gallery', ['galleryUrls' => $galleryUrls])

      @if($event->faqs->isNotEmpty())
      <section class="block faq">
        <h2>Frequently asked questions</h2>
        @foreach($event->faqs as $faq)
          <details><summary>{{ $faq->question }}</summary><div class="body-text">{!! \App\Support\RichTextSanitizer::html($faq->answer) !!}</div></details>
        @endforeach
      </section>
      @endif

      <section class="block">
        <h2>Location</h2>
        @if($mapUrl)
          <p class="body-text" style="font-size:14px;">{{ $event->fullVenueAddressLine() }}</p>
          <div class="map-frame">
            <iframe src="{{ $mapUrl }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map"></iframe>
          </div>
        @else
          <p class="body-text" style="font-size:14px;">{{ $addrOrLoc }}</p>
          @if($event->location_type === 'virtual' && $event->meeting_url)
            <p class="body-text"><a href="{{ $event->meeting_url }}" target="_blank" rel="noopener">Online access link</a></p>
          @endif
        @endif
      </section>
      </div>
    </div>

    <aside class="sidebar">
      <div>
        <div class="label" id="orderLabel">Tickets from</div>
        <div class="price-big" id="totalPrice">${{ $priceFromDisp }}</div>
        <div id="itemsSummary" class="order-items-summary" style="display:none">
          <div class="label" style="margin-top:12px">Selected items</div>
          <ul id="itemsList" class="order-items-list"></ul>
        </div>
      </div>

      @include('public.classic.partials.event-booking-payment-sidebar')

      @include('public.classic.partials.booking-auth-notice')

      <div class="book-fields">
        <label for="attendee_name">Your name</label>
        <input id="attendee_name" name="attendee_name" required value="{{ $bookingDefaults['attendee_name'] }}" autocomplete="name" />

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="{{ $bookingDefaults['email'] }}" autocomplete="email" />

      @include('public.classic.partials.booking-guest-signup')

        <label for="phone">Phone (optional)</label>
        <input id="phone" name="phone" value="{{ $bookingDefaults['phone'] }}" autocomplete="tel" />

      </div>

      <button type="submit" class="btn" id="bookBtn" disabled>Select tickets to book</button>
    </aside>
  </div>
  </form>
@endsection

@push('scripts')
<script>
@include('public.partials.attendee-custom-select-script')
const PRICE_FROM = {{ json_encode($priceFromJs) }};
const PAYMENT_STRIPE = @json($pmStripe);
const PAYMENT_PAYPAL = @json($pmPayPal);
const PAYMENT_RAZORPAY = @json($pmRazorpay);
const PAYMENT_SSLCOMMERZ = @json($pmSslcommerz);
const PAYMENT_OFFLINE_CASH = @json(!empty($showCashOfflinePayments));
const PAYMENT_OFFLINE_BANK = @json(!empty($showBankOfflinePayments));
const ATTENDEE_SETTINGS = @json($attendeeSettings ?? \App\Models\Event::defaultAttendeeSettings());
const ATTENDEE_FIELD_DEFINITIONS = @json($attendeeFieldDefinitions ?? \App\Models\Event::attendeeFieldDefinitions());
const ATTENDEE_OLD_ENTRIES = @json(old('attendee_entries', []));
@php
  $hasSeatPlanBooking = !empty($useSeatPlanBooking) && !empty($seatPlanViewData);
@endphp
const USE_SEAT_PLAN = @json($hasSeatPlanBooking);
const SEAT_PLAN_MULTI_DAY = @json(($seatPlanViewData ?? [])['uses_per_day_seat_inventory'] ?? false);
const SEAT_PLAN_BOOKED_BY_DATE = @json(($seatPlanViewData ?? [])['booked_seats_by_date'] ?? []);
const OLD_SEAT_IDS = @json(collect(old('seat_ids', []))->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all());

function getSidebarPaymentMethod() {
  var r = document.querySelector('aside input[name="payment_method"]:checked');
  if (r) return String(r.value);
  var h = document.querySelector('aside input[type="hidden"][name="payment_method"]');
  return h ? String(h.value) : '';
}

function syncOfflinePaymentFields() {
  var box = document.getElementById('offlinePaymentFields');
  if (!box) return;
  var instr = document.getElementById('bankTransferInstructions');
  var pm = getSidebarPaymentMethod();
  var offline = pm === 'cash' || pm === 'bank_transfer';
  box.style.display = offline ? 'block' : 'none';
  if (instr) {
    var showInstr = offline && PAYMENT_OFFLINE_BANK && pm === 'bank_transfer';
    instr.style.display = showInstr ? 'block' : 'none';
  }
}

function moneyFmt(n) {
  var r = Math.round(n * 100) / 100;
  return r % 1 === 0 ? r.toFixed(0) : r.toFixed(2);
}

function attendeeFieldConfigByKey(fieldKey) {
  return (ATTENDEE_FIELD_DEFINITIONS && ATTENDEE_FIELD_DEFINITIONS[fieldKey]) ? ATTENDEE_FIELD_DEFINITIONS[fieldKey] : null;
}

function attendeeEnabledFieldKeys() {
  if (!ATTENDEE_SETTINGS || !ATTENDEE_SETTINGS.enabled || !ATTENDEE_SETTINGS.fields) return [];
  var out = [];
  Object.keys(ATTENDEE_SETTINGS.fields).forEach(function (k) {
    if (ATTENDEE_SETTINGS.fields[k]) out.push(k);
  });
  return out;
}

function collectCurrentAttendeeEntries() {
  var out = [];
  document.querySelectorAll('input[name^="attendee_entries["], select[name^="attendee_entries["]').forEach(function (el) {
    var match = String(el.name || '').match(/^attendee_entries\[(\d+)\]\[([a-z_]+)\]$/);
    if (!match) return;
    var idx = parseInt(match[1], 10);
    var key = match[2];
    if (!out[idx]) out[idx] = {};
    out[idx][key] = String(el.value || '');
  });
  return out;
}

function syncAttendeePerTicketFields(ticketAssignments) {
  var currentRows = collectCurrentAttendeeEntries();
  var fallbackRows = Array.isArray(ATTENDEE_OLD_ENTRIES) ? ATTENDEE_OLD_ENTRIES : [];

  document.querySelectorAll('.attendee-ticket-inline[data-attendee-inline]').forEach(function (mount) {
    mount.style.display = 'none';
    var grid = mount.querySelector('.attendee-ticket-grid');
    if (grid) grid.innerHTML = '';
  });

  var enabledFields = attendeeEnabledFieldKeys();
  if (!ATTENDEE_SETTINGS || !ATTENDEE_SETTINGS.enabled || enabledFields.length === 0 || !ticketAssignments.length) {
    return;
  }

  for (var i = 0; i < ticketAssignments.length; i++) {
    var seed = (currentRows[i] && typeof currentRows[i] === 'object') ? currentRows[i] : ((fallbackRows[i] && typeof fallbackRows[i] === 'object') ? fallbackRows[i] : {});
    var assignment = ticketAssignments[i] || {};
    var optionEl = assignment.optionEl || null;
    if (!optionEl) continue;
    var mount = optionEl.querySelector('.attendee-ticket-inline[data-attendee-inline]');
    if (!mount) continue;
    var rowsWrap = mount.querySelector('.attendee-ticket-grid');
    if (!rowsWrap) continue;
    mount.style.display = 'block';

    var card = document.createElement('div');
    card.className = 'attendee-ticket-card';

    var title = document.createElement('p');
    title.className = 'attendee-ticket-title';
    title.textContent = 'Attendee #' + (i + 1);
    card.appendChild(title);

    var subtitle = document.createElement('p');
    subtitle.className = 'attendee-ticket-subtitle';
    var subtitleParts = [];
    if (assignment.dayLabel) subtitleParts.push(assignment.dayLabel);
    if (assignment.ticketName) subtitleParts.push(assignment.ticketName);
    subtitle.textContent = subtitleParts.length ? subtitleParts.join(' - ') : 'Ticket seat details';
    card.appendChild(subtitle);

    var fieldWrap = document.createElement('div');
    fieldWrap.className = 'attendee-ticket-fields';

    enabledFields.forEach(function (fieldKey) {
      var cfg = attendeeFieldConfigByKey(fieldKey);
      if (!cfg) return;

      var label = document.createElement('label');
      label.textContent = cfg.label;

      if (cfg.type === 'select') {
        var selectedValue = seed[fieldKey] ? String(seed[fieldKey]) : '';
        label.appendChild(createAttendeeSelectField(cfg, 'attendee_entries[' + i + '][' + fieldKey + ']', selectedValue, true));
      } else {
        var input = document.createElement('input');
        input.type = cfg.type;
        input.name = 'attendee_entries[' + i + '][' + fieldKey + ']';
        input.value = seed[fieldKey] ? String(seed[fieldKey]) : '';
        input.required = true;
        if (cfg.autocomplete) input.autocomplete = cfg.autocomplete;
        label.appendChild(input);
      }

      fieldWrap.appendChild(label);
    });

    card.appendChild(fieldWrap);
    rowsWrap.appendChild(card);
  }
}

function getSessionDayCount() {
  var root = document.querySelector('.booking-cart-root.booking-cart--multi');
  if (!root) return 1;
  var panels = root.querySelectorAll('.booking-day-panel');
  return panels.length || 1;
}

function getSelectedSessionLabels() {
  var root = document.querySelector('.booking-cart-root.booking-cart--multi');
  if (!root) return [];
  var labels = [];
  root.querySelectorAll('.booking-day-panel').forEach(function (panel) {
    var hasQty = false;
    panel.querySelectorAll('.count').forEach(function (countEl) {
      if ((parseInt(countEl.textContent, 10) || 0) > 0) hasQty = true;
    });
    if (hasQty) {
      labels.push(panel.getAttribute('data-session-label') || '');
    }
  });
  return labels.filter(Boolean);
}

function initBookingDayTabs() {
  var root = document.querySelector('.booking-cart-root.booking-cart--multi');
  if (!root) return;

  var tabs = Array.prototype.slice.call(root.querySelectorAll('.booking-day-tab[data-day-target]'));
  if (!tabs.length) return;

  function activateTab(targetId, focusTab) {
    tabs.forEach(function (tab) {
      var isActive = tab.getAttribute('data-day-target') === targetId;
      var panelId = tab.getAttribute('data-day-target');
      var panel = root.querySelector('#' + panelId);
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex', isActive ? '0' : '-1');
      if (panel) {
        panel.classList.toggle('is-active', isActive);
        if (isActive) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', 'hidden');
        }
      }
      if (isActive && focusTab) {
        tab.focus();
      }
    });
    recalc();
  }

  tabs.forEach(function (tab, idx) {
    tab.addEventListener('click', function () {
      activateTab(tab.getAttribute('data-day-target'), false);
    });
    tab.addEventListener('keydown', function (e) {
      if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(e.key)) return;
      e.preventDefault();
      var nextIdx = idx;
      if (e.key === 'ArrowRight') nextIdx = (idx + 1) % tabs.length;
      if (e.key === 'ArrowLeft') nextIdx = (idx - 1 + tabs.length) % tabs.length;
      if (e.key === 'Home') nextIdx = 0;
      if (e.key === 'End') nextIdx = tabs.length - 1;
      var nextTab = tabs[nextIdx];
      if (!nextTab) return;
      activateTab(nextTab.getAttribute('data-day-target'), true);
    });
  });

  var initial = tabs.find(function (tab) { return tab.classList.contains('is-active'); }) || tabs[0];
  if (initial) {
    activateTab(initial.getAttribute('data-day-target'), false);
  }
}

function setHero(btn) {
  var img = document.getElementById('heroImg');
  if (!img || !btn.dataset.src) return;
  img.src = btn.dataset.src;
  document.querySelectorAll('.gallery button').forEach(function(b) { b.classList.remove('active'); });
  btn.classList.add('active');
}

function changeQty(btn, delta) {
  var wrap = btn.closest('.qty');
  if (!wrap) return;
  var hidden = wrap.querySelector('.qty-hidden');
  var countEl = wrap.querySelector('.count');
  var minusBtn = wrap.querySelector('button.minus') || wrap.querySelector('button:not(.plus)');
  var plusBtn = wrap.querySelector('button.plus');
  var max = hidden && hidden.dataset.max ? parseInt(hidden.dataset.max, 10) : 100;
  var n = Math.max(0, parseInt(countEl.textContent, 10) + delta);
  if (n > max) n = max;
  countEl.textContent = n;
  if (hidden) hidden.value = n;
  if (minusBtn) minusBtn.disabled = n === 0;
  if (plusBtn) plusBtn.disabled = n >= max;
  recalc();
}

@if(!empty($hasSeatPlanBooking))
@include('public.classic.partials.event-booking-seat-plan-scripts')
@else
function initSeatPlanBooking() {}
function isClassicSeatPlanFlow() { return false; }
@endif

function recalc() {
  var total = 0, itemCount = 0;
  var listRows = [];
  var ticketAssignments = [];
  var root = document.querySelector('.booking-cart-root');
  if (!root) return;

  if (USE_SEAT_PLAN && root.classList.contains('booking-cart--seat-plan')) {
    if (isClassicSeatPlanFlow()) {
      recalcClassicSeatPlanBranch(root);
      return;
    }
  }

  function sumOptions(scopeEl, dayPrefix) {
    scopeEl.querySelectorAll('.option[data-kind="ticket"], .option[data-kind="addon"]').forEach(function(opt) {
      var countEl = opt.querySelector('.count');
      if (!countEl) return;
      var qty = parseInt(countEl.textContent, 10);
      var price = parseFloat(opt.dataset.price || '0');
      var kindRaw = opt.getAttribute('data-kind');
      total += qty * price;
      itemCount += qty;
      if (qty > 0) {
        var label = opt.dataset.name || 'Item';
        var kind = kindRaw === 'addon' ? 'Add-on' : 'Ticket';
        var line = (dayPrefix ? dayPrefix + ' - ' : '') + kind + ': ' + label + ' x' + qty;
        listRows.push({ line: line });
      }
    });
  }

  function collectTicketAssignments(scopeEl, dayPrefix) {
    var out = [];
    if (!scopeEl) return out;
    scopeEl.querySelectorAll('.option[data-kind="ticket"]').forEach(function (opt) {
      var countEl = opt.querySelector('.count');
      if (!countEl) return;
      var qty = parseInt(countEl.textContent, 10) || 0;
      if (qty <= 0) return;
      var label = opt.dataset.name || 'Ticket';
      for (var i = 0; i < qty; i++) {
        out.push({
          dayLabel: dayPrefix || '',
          ticketName: label,
          optionEl: opt
        });
      }
    });
    return out;
  }

  if (root.classList.contains('booking-cart--multi')) {
    root.querySelectorAll('.per-session-cart').forEach(function(dayEl) {
      var label = dayEl.dataset.sessionLabel || '';
      sumOptions(dayEl, label);
    });
    var activeDayEl = root.querySelector('.per-session-cart.is-active') || root.querySelector('.per-session-cart:not([hidden])') || root.querySelector('.per-session-cart');
    var activeDayLabel = activeDayEl ? (activeDayEl.dataset.sessionLabel || '') : '';
    ticketAssignments = collectTicketAssignments(activeDayEl, activeDayLabel);
  } else {
    sumOptions(root, '');
    ticketAssignments = collectTicketAssignments(root, '');
  }
  var totalEl = document.getElementById('totalPrice');
  var labelEl = document.getElementById('orderLabel');
  var summaryEl = document.getElementById('itemsSummary');
  var itemsListEl = document.getElementById('itemsList');
  var btn = document.getElementById('bookBtn');
  var payBlock = document.getElementById('paymentMethodsBlock');
  if (!totalEl || !labelEl || !btn) return;
  if (payBlock) {
    var canPayAny = PAYMENT_STRIPE || PAYMENT_PAYPAL || PAYMENT_RAZORPAY || PAYMENT_SSLCOMMERZ || PAYMENT_OFFLINE_CASH || PAYMENT_OFFLINE_BANK;
    payBlock.style.display = (itemCount > 0 && total > 0 && canPayAny) ? 'block' : 'none';
  }
  syncAttendeePerTicketFields(ticketAssignments);
  syncOfflinePaymentFields();
  if (itemCount > 0) {
    totalEl.textContent = '$' + moneyFmt(total);
    labelEl.textContent = 'Your order';
    if (summaryEl && itemsListEl) {
      summaryEl.style.display = 'block';
      itemsListEl.innerHTML = '';
      listRows.forEach(function(row) {
        var li = document.createElement('li');
        li.textContent = row.line;
        itemsListEl.appendChild(li);
      });
    }
    btn.disabled = false;
    btn.textContent = 'Book now - $' + moneyFmt(total);
  } else {
    totalEl.textContent = '$' + moneyFmt(PRICE_FROM);
    labelEl.textContent = 'Tickets from';
    if (summaryEl) summaryEl.style.display = 'none';
    if (itemsListEl) itemsListEl.innerHTML = '';
    btn.disabled = true;
    btn.textContent = 'Select tickets to book';
  }
  syncBookGuestSignup();
}

function syncBookGuestSignup() {
  var wrap = document.getElementById('bookGuestSignup');
  if (!wrap) return;
  var pm = getSidebarPaymentMethod();
  var onlineGate = !!(PAYMENT_STRIPE || PAYMENT_PAYPAL || PAYMENT_RAZORPAY || PAYMENT_SSLCOMMERZ) && (pm === 'stripe' || pm === 'paypal' || pm === 'razorpay' || pm === 'sslcommerz');
  var totalEl = document.getElementById('totalPrice');
  var labelEl = document.getElementById('orderLabel');
  var chk = document.getElementById('create_account_checkbox');
  var hint = document.getElementById('bookGuestSignupPayHint');
  if (!chk || !totalEl || !labelEl) return;
  var txt = totalEl.textContent.trim();
  var total = parseFloat(txt.replace(/[^0-9.]/g, '')) || 0;
  var hasSelection = labelEl.textContent.trim() === 'Your order';
  var paidOnlineCart = onlineGate && hasSelection && total > 0;
  chk.disabled = false;
  wrap.classList.toggle('book-guest-signup--paid-online', paidOnlineCart);
  if (paidOnlineCart) {
    if (hint) hint.hidden = false;
  } else {
    if (hint) hint.hidden = true;
  }
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-share-event]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = window.location.href;
      var titleEl = document.querySelector('.hero--detail h1');
      var title = titleEl ? titleEl.textContent.trim() : document.title;
      if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(function () {});
        return;
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).catch(function () {});
      }
    });
  });
  initSeatPlanBooking();
  initBookingDayTabs();
  recalc();
  document.querySelectorAll('aside input[name="payment_method"]').forEach(function (el) {
    el.addEventListener('change', function () {
      syncOfflinePaymentFields();
      syncBookGuestSignup();
    });
  });
});
</script>
@endpush





