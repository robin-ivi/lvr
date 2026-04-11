<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PlainLayout from '@/layouts/PlainLayout.vue';

type RazorpayOrder = {
    id: string;
    amount: number;
    currency: string;
};

type RazorpayFailurePayload = {
    error?: {
        code?: string;
        description?: string;
        source?: string;
        step?: string;
        reason?: string;
        metadata?: {
            order_id?: string;
            payment_id?: string;
        };
    };
};

declare global {
    interface Window {
        Razorpay?: new (options: Record<string, unknown>) => {
            open: () => void;
            on: (event: string, callback: (payload: RazorpayFailurePayload) => void) => void;
        };
    }
}

const props = defineProps<{
    razorpayKey: string | null;
    currency: string;
    merchantName: string;
    description: string;
}>();

defineOptions({
    layout: PlainLayout,
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    purpose: '',
    amount: '',
});

const processing = ref(false);
const successMessage = ref('');
const paymentError = ref('');
const paymentResolved = ref(false);

const amountLabel = computed(() => {
    const amount = Number(form.amount || 0);

    if (!amount) {
        return 'Enter an amount in INR to continue.';
    }

    return `You will be charged INR ${amount.toFixed(2)}.`;
});

function getCsrfToken(): string {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    return token ?? '';
}

function resetMessages(): void {
    successMessage.value = '';
    paymentError.value = '';
    paymentResolved.value = false;
}

function formatFailureMessage(payload: RazorpayFailurePayload): string {
    const description = payload.error?.description;
    const reason = payload.error?.reason;
    const code = payload.error?.code;

    return (
        description ??
        reason ??
        (code ? `Payment failed with code ${code}.` : null) ??
        'Payment failed in Razorpay checkout. If you are using test keys, use Razorpay test card details only.'
    );
}

async function loadCheckoutScript(): Promise<void> {
    if (window.Razorpay) {
        return;
    }

    await new Promise<void>((resolve, reject) => {
        const existingScript = document.querySelector(
            'script[data-razorpay-checkout="true"]',
        );

        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(), {
                once: true,
            });
            existingScript.addEventListener(
                'error',
                () => reject(new Error('Unable to load Razorpay checkout script.')),
                { once: true },
            );

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.async = true;
        script.dataset.razorpayCheckout = 'true';
        script.onload = () => resolve();
        script.onerror = () =>
            reject(new Error('Unable to load Razorpay checkout script.'));
        document.body.appendChild(script);
    });
}

async function createOrder(): Promise<RazorpayOrder> {
    const response = await fetch('/payments/order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(form.data()),
    });

    const payload = await response.json();

    if (!response.ok) {
        form.setError(payload.errors ?? {});
        paymentError.value =
            payload.errors?.payment?.[0] ??
            'We could not start the payment. Please review the form and try again.';
        throw new Error(paymentError.value);
    }

    return payload.order as RazorpayOrder;
}

async function verifyPayment(paymentResponse: Record<string, string>): Promise<void> {
    const response = await fetch('/payments/verify', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
            ...form.data(),
            ...paymentResponse,
        }),
    });

    const payload = await response.json();

    if (!response.ok) {
        form.setError(payload.errors ?? {});
        paymentError.value =
            payload.errors?.payment?.[0] ??
            'Payment verification failed. No form data was stored.';
        throw new Error(paymentError.value);
    }

    paymentResolved.value = true;
    paymentError.value = '';
    successMessage.value = payload.message;
    form.reset();
}

async function logFailedPayment(payload: RazorpayFailurePayload): Promise<void> {
    await fetch('/payments/failure', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
            ...form.data(),
            currency: props.currency,
            ...payload,
        }),
    });
}

async function openCheckout(): Promise<void> {
    resetMessages();
    form.clearErrors();

    if (!props.razorpayKey) {
        paymentError.value =
            'Razorpay is not configured yet. Add your Razorpay keys to the .env file.';
        return;
    }

    processing.value = true;

    try {
        await loadCheckoutScript();
        const order = await createOrder();

        if (!window.Razorpay) {
            throw new Error('Razorpay checkout did not initialize.');
        }

        const razorpay = new window.Razorpay({
            key: props.razorpayKey,
            amount: order.amount,
            currency: order.currency,
            name: props.merchantName,
            description: props.description,
            order_id: order.id,
            prefill: {
                name: form.name,
                email: form.email,
                contact: form.phone,
            },
            notes: {
                purpose: form.purpose,
            },
            theme: {
                color: '#0f766e',
            },
            handler: async (response: Record<string, string>) => {
                try {
                    await verifyPayment(response);
                } finally {
                    processing.value = false;
                }
            },
            modal: {
                ondismiss: () => {
                    processing.value = false;
                },
            },
        });

        razorpay.on('payment.failed', (payload: RazorpayFailurePayload) => {
            if (paymentResolved.value) {
                return;
            }

            successMessage.value = '';
            paymentError.value = formatFailureMessage(payload);
            processing.value = false;
            void logFailedPayment(payload);
        });

        razorpay.open();
    } catch (error) {
        processing.value = false;

        if (error instanceof Error && !paymentError.value) {
            paymentError.value = error.message;
        }
    }
}
</script>

<template>
    <Head title="Payment Form" />

    <div class="min-h-screen bg-[radial-gradient(circle_at_top,#ccfbf1_0%,#f8fafc_38%,#ecfeff_100%)] px-4 py-8 text-slate-900">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 lg:flex-row">
            <section class="flex-1 rounded-[32px] border border-white/70 bg-white/80 p-8 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.45)] backdrop-blur">
                <p class="text-sm font-semibold uppercase tracking-[0.35em] text-teal-700">Razorpay payment</p>
                <h1 class="mt-4 max-w-xl text-4xl font-semibold tracking-tight text-slate-950">
                    Collect payment and store form details in Laravel.
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    Fill out the form, complete the Razorpay checkout, and the verified payment data will be saved in your database.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-teal-100 bg-teal-50 p-4">
                        <p class="text-sm font-medium text-teal-900">Verification first</p>
                        <p class="mt-2 text-sm leading-6 text-teal-800">
                            Data is stored only after the Razorpay signature matches on the server.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-900">Ready route</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">
                            Open this form at <span class="font-mono">/payments</span>.
                        </p>
                    </div>
                </div>
            </section>

            <section class="w-full rounded-[32px] border border-slate-200 bg-slate-950 p-6 text-white shadow-[0_30px_80px_-40px_rgba(15,23,42,0.75)] lg:max-w-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-teal-300">Checkout form</p>
                        <h2 class="mt-2 text-2xl font-semibold">Pay with Razorpay</h2>
                    </div>
                    <Link href="/" class="rounded-full border border-white/15 px-4 py-2 text-sm text-slate-200 transition hover:border-teal-300 hover:text-white">
                        Home
                    </Link>
                </div>

                <form class="mt-8 space-y-5" @submit.prevent="openCheckout">
                    <div>
                        <label for="name" class="mb-2 block text-sm font-medium text-slate-200">Full name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400"
                            placeholder="Enter customer name"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-rose-300">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-200">Email</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400"
                                placeholder="name@example.com"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-rose-300">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="phone" class="mb-2 block text-sm font-medium text-slate-200">Phone</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400"
                                placeholder="+91 9876543210"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-300">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <div>
                        <label for="purpose" class="mb-2 block text-sm font-medium text-slate-200">Purpose</label>
                        <input
                            id="purpose"
                            v-model="form.purpose"
                            type="text"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400"
                            placeholder="Course fee, booking, donation, service charge"
                        />
                        <p v-if="form.errors.purpose" class="mt-1 text-sm text-rose-300">{{ form.errors.purpose }}</p>
                    </div>

                    <div>
                        <label for="amount" class="mb-2 block text-sm font-medium text-slate-200">Amount (INR)</label>
                        <input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            min="1"
                            step="0.01"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none transition placeholder:text-slate-400 focus:border-teal-400"
                            placeholder="499.00"
                        />
                        <p class="mt-2 text-sm text-slate-400">{{ amountLabel }}</p>
                        <p v-if="form.errors.amount" class="mt-1 text-sm text-rose-300">{{ form.errors.amount }}</p>
                    </div>

                    <div v-if="paymentError" class="rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                        {{ paymentError }}
                    </div>

                    <div v-if="successMessage" class="rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                        {{ successMessage }}
                    </div>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="w-full rounded-2xl bg-teal-400 px-4 py-3 text-base font-semibold text-slate-950 transition hover:bg-teal-300 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ processing ? 'Processing...' : 'Pay Now' }}
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>
