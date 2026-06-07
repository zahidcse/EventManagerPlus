<?php

namespace App\Http\Requests\Admin\Event;

use App\Support\Edition;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wizard_panel' => ['nullable', 'string', 'in:content,advanced'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:2000'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string', 'max:20000'],
            'fee_handling' => ['nullable', 'in:pass_to_buyer,absorb'],
            'max_tickets_per_customer' => ['nullable', 'integer', 'min:1', 'max:99'],
            'ticket_pdf_fields' => ['nullable', 'array'],
            'ticket_pdf_fields.company_logo' => ['nullable', 'boolean'],
            'ticket_pdf_fields.event_name' => ['nullable', 'boolean'],
            'ticket_pdf_fields.organizer_name' => ['nullable', 'boolean'],
            'ticket_pdf_fields.company_name' => ['nullable', 'boolean'],
            'ticket_pdf_fields.event_datetime' => ['nullable', 'boolean'],
            'ticket_pdf_fields.event_location' => ['nullable', 'boolean'],
            'ticket_pdf_fields.location_type' => ['nullable', 'boolean'],
            'ticket_pdf_fields.attendee_name' => ['nullable', 'boolean'],
            'ticket_pdf_fields.attendee_email' => ['nullable', 'boolean'],
            'ticket_pdf_fields.attendee_phone' => ['nullable', 'boolean'],
            'ticket_pdf_fields.ticket_type' => ['nullable', 'boolean'],
            'ticket_pdf_fields.seat_number' => ['nullable', 'boolean'],
            'ticket_pdf_fields.booking_id' => ['nullable', 'boolean'],
            'ticket_pdf_fields.session_date' => ['nullable', 'boolean'],
            'ticket_pdf_fields.tier_price' => ['nullable', 'boolean'],
            'ticket_pdf_fields.order_status' => ['nullable', 'boolean'],
            'ticket_pdf_fields.payment_reference' => ['nullable', 'boolean'],
            'ticket_pdf_fields.notes' => ['nullable', 'boolean'],
            'ticket_pdf_fields.checkin_qr' => ['nullable', 'boolean'],
            'ticket_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'clear_ticket_logo' => ['nullable', 'boolean'],
            'attendee_settings' => ['nullable', 'array'],
            'attendee_settings.enabled' => ['nullable', 'boolean'],
            'attendee_settings.fields' => ['nullable', 'array'],
            'attendee_settings.fields.name' => ['nullable', 'boolean'],
            'attendee_settings.fields.email' => ['nullable', 'boolean'],
            'attendee_settings.fields.phone' => ['nullable', 'boolean'],
            'attendee_settings.fields.gender' => ['nullable', 'boolean'],
            'attendee_settings.fields.driving_license' => ['nullable', 'boolean'],
            'attendee_settings.fields.nid' => ['nullable', 'boolean'],
            'attendee_settings.fields.location' => ['nullable', 'boolean'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:500'],
            'faqs.*.answer' => ['nullable', 'string', 'max:100000'],
            'timeline' => ['nullable', 'array'],
            'timeline.*.time_label' => ['nullable', 'string', 'max:100'],
            'timeline.*.title' => ['nullable', 'string', 'max:255'],
            'wizard_action' => ['nullable', 'string', 'in:draft,continue,publish'],
            'seat_plan_enabled' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (Edition::isFree()) {
            $this->merge(['seat_plan_enabled' => false]);
        }
    }
}
