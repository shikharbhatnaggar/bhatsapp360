@extends('layouts.base')
@section('title', 'Privacy Policy — '.config('app.name'))

@section('body')
@php
    // TODO: replace these five values, then delete this comment.
    $product = config('app.name');
    $company = '[LEGAL ENTITY NAME]';
    $address = '[REGISTERED ADDRESS, CITY, STATE, PIN, INDIA]';
    $email = '[privacy@yourdomain.com]';
    $grievanceOfficer = '[GRIEVANCE OFFICER NAME]';
    $effective = '[DD Month YYYY]';
@endphp

<div class="mx-auto max-w-3xl px-6 py-14">
    <header class="border-b border-ink-200 pb-8">
        <a href="{{ url('/') }}" class="inline-block">
            @include('partials.brand', ['size' => 'h-10'])
        </a>
        <h1 class="mt-8 text-3xl tracking-tight">Privacy Policy</h1>
        <p class="mt-2 text-sm text-ink-500">Effective {{ $effective }} · operated by {{ $company }}</p>
    </header>

    <div class="mt-10 space-y-10 text-[15px] leading-relaxed text-ink-700">

        <section>
            <h2 class="text-lg text-ink-900">Who we are</h2>
            <p class="mt-3">
                {{ $product }} is a messaging console operated by {{ $company }}, {{ $address }}. It lets businesses
                send and receive WhatsApp messages through the WhatsApp Business Platform operated by Meta
                Platforms, Inc.
            </p>
            <p class="mt-3">
                We handle two kinds of personal data, and the distinction matters throughout this policy.
                For our <strong class="font-semibold text-ink-900">customers</strong> — the businesses who hold
                accounts with us — we are the data controller. For the
                <strong class="font-semibold text-ink-900">contacts</strong> those businesses message, we are a
                data processor: the business decides who is contacted and why, and we act on their instructions.
                If you received a WhatsApp message and want it to stop, contact the business that sent it, or
                reply STOP.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">What we collect</h2>

            <h3 class="mt-5 text-base text-ink-900">From account holders</h3>
            <ul class="mt-2 list-disc space-y-1.5 pl-5">
                <li>Name, work email address, hashed password, and role within the workspace.</li>
                <li>Business name, country and billing currency.</li>
                <li>WhatsApp Business Account ID, phone number ID, access token and app secret. Tokens and
                    secrets are encrypted at rest and are never displayed back in full.</li>
                <li>An activity log of actions taken in the product, including the IP address the action came from.</li>
            </ul>

            <h3 class="mt-5 text-base text-ink-900">About contacts, on our customers' behalf</h3>
            <ul class="mt-2 list-disc space-y-1.5 pl-5">
                <li>Name, WhatsApp phone number, and optionally email address and tags, as uploaded or entered
                    by the business.</li>
                <li>The content of messages sent to them and received from them, including message text, media
                    references and interactive replies.</li>
                <li>Delivery metadata returned by WhatsApp: message identifiers, and sent, delivered, read or
                    failed receipts with their timestamps.</li>
                <li>Whether the contact is recorded as having opted in to receive messages.</li>
            </ul>
            <p class="mt-3">
                A contact record is also created automatically when someone messages a business's WhatsApp
                number for the first time, using the phone number and WhatsApp profile name that Meta sends us.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">Why we use it</h2>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li>To deliver the service: sending messages, receiving replies, and showing delivery status.</li>
                <li>To submit message templates to Meta for review and to reflect their decisions.</li>
                <li>To calculate what a send costs and to bill our customers accurately.</li>
                <li>To maintain an audit trail, so a business can see who did what and when.</li>
                <li>To keep the service secure, diagnose faults, and prevent abuse.</li>
            </ul>
            <p class="mt-3">
                We do not sell personal data. We do not use message content to train machine learning models,
                and we do not use it for advertising.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">Who we share it with</h2>
            <p class="mt-3">
                <strong class="font-semibold text-ink-900">Meta Platforms, Inc.</strong> Message content,
                recipient phone numbers and template content are transmitted to Meta in order to deliver
                messages over WhatsApp. Meta's handling is governed by its own terms and privacy policy.
            </p>
            <p class="mt-3">
                <strong class="font-semibold text-ink-900">Infrastructure providers.</strong> Our hosting and
                database providers store this data on our behalf under contract.
            </p>
            <p class="mt-3">
                We also disclose data where required by law, and in connection with a merger or acquisition,
                in which case we will notify affected customers beforehand.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">Where it is stored, and for how long</h2>
            <p class="mt-3">
                Data is stored on servers operated by our hosting provider. Transmission to Meta involves
                transfers outside India and, where applicable, outside the European Economic Area.
            </p>
            <p class="mt-3">
                We keep contact records and message history for as long as the business's account is active.
                On account closure we delete or anonymise personal data within 90 days, except where we must
                retain records to meet legal, tax or accounting obligations. Activity logs are kept for
                [12] months.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">Your rights</h2>
            <p class="mt-3">
                Depending on where you live, you may have the right to access the personal data we hold about
                you, correct it, have it deleted, object to or restrict how it is used, receive a copy in a
                portable format, and withdraw consent at any time.
            </p>
            <p class="mt-3">
                Under India's Digital Personal Data Protection Act, 2023, you may also nominate another person
                to exercise these rights on your behalf, and you may complain to the Data Protection Board of
                India.
            </p>
            <p class="mt-3">
                To exercise any of these, email {{ $email }}. If your data was uploaded by a business using
                {{ $product }}, we will forward your request to that business, since they decide what happens to it.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">Opting out of messages</h2>
            <p class="mt-3">
                Reply STOP to any message to opt out. Businesses using {{ $product }} are contractually required to
                obtain opt-in before sending marketing messages and to honour opt-out requests promptly, in
                line with WhatsApp's Business Messaging Policy.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">Security</h2>
            <p class="mt-3">
                Access tokens and app secrets are encrypted at rest. Passwords are hashed. Access to the
                product requires authentication, and each business's data is isolated from every other
                business's. Incoming webhook requests from Meta are verified against a cryptographic
                signature. No system is perfectly secure, and we cannot guarantee absolute security.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">Children</h2>
            <p class="mt-3">
                {{ $product }} is a business tool and is not directed at children. We do not knowingly collect data
                from anyone under 18. If you believe a child's data has reached us, contact us and we will
                delete it.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">Changes</h2>
            <p class="mt-3">
                We will post any changes on this page and update the effective date. Material changes will be
                notified to account holders by email.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">Contact</h2>
            <p class="mt-3">
                {{ $company }}<br>
                {{ $address }}<br>
                Email: {{ $email }}<br>
                Grievance Officer: {{ $grievanceOfficer }}, {{ $email }}
            </p>
            <p class="mt-3 text-sm text-ink-500">
                Under Indian law the Grievance Officer must acknowledge a complaint within 24 hours and
                resolve it within 15 days.
            </p>
        </section>
    </div>

    <footer class="mt-14 flex flex-wrap items-center justify-between gap-3 border-t border-ink-200 pt-6 text-sm text-ink-500">
        <p>&copy; {{ now()->year }} {{ $company }}. All rights reserved.</p>
        <a href="{{ url('/') }}" class="underline underline-offset-2">Back to {{ $product }}</a>
    </footer>
</div>
@endsection
