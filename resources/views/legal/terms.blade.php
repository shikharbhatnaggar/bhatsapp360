@extends('layouts.base')
@section('title', 'Terms of Service — '.config('app.name'))

@section('body')
@php
    // TODO: replace these values, then delete this comment.
    $product = config('app.name');
    $company = 'Shiventech Consulting';
    $address = 'Purushottam Vihar, Golek Ka Mandir, Gwalior, MP, 474005, INDIA';
    $email = 'support@shiventech.co.in';
    $jurisdiction = 'Gwalior, MP, India';
    $effective = '14 September 2026';
@endphp

<div class="mx-auto max-w-3xl px-6 py-14">
    <header class="border-b border-ink-200 pb-8">
        <a href="{{ url('/') }}" class="inline-block">
            @include('partials.brand', ['size' => 'h-10'])
        </a>
        <h1 class="mt-8 text-3xl tracking-tight">Terms of Service</h1>
        <p class="mt-2 text-sm text-ink-500">Effective {{ $effective }} · {{ $company }}</p>
    </header>

    <div class="mt-10 space-y-10 text-[15px] leading-relaxed text-ink-700">

        <section>
            <h2 class="text-lg text-ink-900">1. Agreement</h2>
            <p class="mt-3">
                These terms form a contract between you, or the business you represent (“you”), and
                {{ $company }}, {{ $address }} (“we”, “us”). By creating an account or using {{ $product }} you
                accept them. If you are agreeing on behalf of a business, you confirm you are authorised to
                bind it.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">2. What {{ $product }} does</h2>
            <p class="mt-3">
                {{ $product }} is a console for the WhatsApp Business Platform. It lets you connect your own
                WhatsApp Business Account, build and submit message templates for Meta's review, send messages
                to contacts you supply, track delivery, and read replies.
            </p>
            <p class="mt-3">
                We are not Meta, and we are not affiliated with or endorsed by Meta Platforms, Inc. Message
                delivery, template approval, quality ratings, messaging limits and account status are all
                determined by Meta, not by us. Your use of WhatsApp is additionally governed by the
                <span class="text-ink-900">WhatsApp Business Terms of Service</span> and the
                <span class="text-ink-900">WhatsApp Business Messaging Policy</span>, and you agree to comply
                with both.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">3. Your account</h2>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li>You must be at least 18 and using {{ $product }} for business purposes.</li>
                <li>You are responsible for the accuracy of your account details and for everything done under
                    your credentials. Tell us promptly if you suspect unauthorised access.</li>
                <li>You are responsible for the WhatsApp Business Account, phone number and access tokens you
                    connect, and for keeping them valid.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">4. Your contacts and consent</h2>
            <p class="mt-3">
                You decide who is messaged. You confirm that, for every contact you upload or message, you
                have obtained opt-in consent as required by WhatsApp's policies and by applicable law, that
                you have the right to share their details with us, and that you will honour opt-out requests
                promptly.
            </p>
            <p class="mt-3">
                We process contact data on your instructions, as described in our
                <a href="{{ route('privacy') }}" class="text-jade-700 underline underline-offset-2">Privacy Policy</a>.
                Sending unsolicited messages is the single fastest way to have your WhatsApp number rate-limited
                or banned by Meta, and that outcome is not something we can reverse.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">5. Acceptable use</h2>
            <p class="mt-3">You will not use {{ $product }} to:</p>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li>send messages to people who have not opted in, or who have opted out;</li>
                <li>send unlawful, deceptive, harassing, hateful or misleading content;</li>
                <li>impersonate another person or business;</li>
                <li>promote goods or services prohibited by WhatsApp's Commerce Policy;</li>
                <li>circumvent messaging limits, rate limits or template review;</li>
                <li>attempt to access another customer's workspace or data, or to disrupt the service.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">6. Fees and billing</h2>
            <p class="mt-3">
                Messages are charged per message according to the rate card shown in the product at the time
                of sending, which varies by message category and recipient country. Costs shown before a send
                are estimates. WhatsApp bills on delivery, so the final amount may differ from the estimate,
                and failed messages are not charged.
            </p>
            <p class="mt-3">
                Meta may charge you separately for conversations under its own pricing. Amounts shown in
                {{ $product }} are for your reference and are not a Meta invoice. Fees are exclusive of taxes, which
                you are responsible for. Amounts already incurred are non-refundable except where required by
                law.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">7. Availability</h2>
            <p class="mt-3">
                We aim to keep {{ $product }} available but do not promise uninterrupted service. Maintenance,
                third-party outages and changes to Meta's APIs can all interrupt it. We may modify or
                discontinue features, and will give reasonable notice of material changes where we can.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">8. Suspension and termination</h2>
            <p class="mt-3">
                You may close your account at any time. We may suspend or terminate your access if you breach
                these terms, if your use puts our platform or other customers at risk, if fees go unpaid, or
                if Meta requires it. On termination we handle your data as set out in the Privacy Policy.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">9. Intellectual property</h2>
            <p class="mt-3">
                We own {{ $product }} and everything in it apart from your content. You own the content you upload
                and send, and you grant us the licence needed to store, process and transmit it in order to
                run the service.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">10. Disclaimers</h2>
            <p class="mt-3">
                {{ $product }} is provided “as is”. To the extent permitted by law we disclaim all warranties,
                express or implied, including fitness for a particular purpose. We do not warrant that any
                message will be delivered, that any template will be approved, or that your WhatsApp account
                will remain in good standing — those decisions rest with Meta.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">11. Limitation of liability</h2>
            <p class="mt-3">
                To the extent permitted by law, neither party is liable for indirect, incidental, special or
                consequential loss, or for lost profits, revenue, data or goodwill. Our total liability arising
                out of these terms is limited to the fees you paid us in the [3] months before the event giving
                rise to the claim.
            </p>
            <p class="mt-3">
                Nothing here limits liability that cannot lawfully be limited, including for fraud or for
                death or personal injury caused by negligence.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">12. Indemnity</h2>
            <p class="mt-3">
                You will indemnify us against claims, losses and reasonable legal costs arising from your
                content, your contacts, your breach of these terms, or your breach of WhatsApp's policies or
                applicable law.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">13. Changes</h2>
            <p class="mt-3">
                We may update these terms. We will post the revised version here and update the effective
                date, and will notify you by email of material changes. Continuing to use {{ $product }} after
                changes take effect means you accept them.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">14. Governing law</h2>
            <p class="mt-3">
                These terms are governed by the laws of India. The courts at {{ $jurisdiction }} have exclusive
                jurisdiction over any dispute.
            </p>
        </section>

        <section>
            <h2 class="text-lg text-ink-900">15. Contact</h2>
            <p class="mt-3">
                {{ $company }}<br>
                {{ $address }}<br>
                Email: {{ $email }}
            </p>
        </section>
    </div>

    <footer class="mt-14 flex flex-wrap items-center justify-between gap-3 border-t border-ink-200 pt-6 text-sm text-ink-500">
        <p>&copy; {{ now()->year }} {{ $company }}. All rights reserved.</p>
        <div class="flex gap-5">
            <a href="{{ url('/') }}" class="underline underline-offset-2">Back to {{ $product }}</a>
            <a href="{{ route('privacy') }}" class="underline underline-offset-2">Privacy Policy</a>
        </div>
    </footer>
</div>
@endsection
