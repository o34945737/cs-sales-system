<script setup lang="ts">
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, XCircle } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const page = usePage<SharedData>();
const visible = ref(false);
const message = ref('');
const tone = ref<'success' | 'error'>('success');

let timer: ReturnType<typeof setTimeout> | null = null;

const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);

const showToast = (nextMessage: string, nextTone: 'success' | 'error') => {
    message.value = nextMessage;
    tone.value = nextTone;
    visible.value = true;

    if (timer) {
        clearTimeout(timer);
    }

    timer = setTimeout(() => {
        visible.value = false;
    }, 4200);
};

watch(
    () => success.value,
    (value) => {
        if (value) {
            showToast(value, 'success');
        }
    },
    { immediate: true },
);

watch(
    () => error.value,
    (value) => {
        if (value) {
            showToast(value, 'error');
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (timer) {
        clearTimeout(timer);
    }
});

const toneClasses = computed(() =>
    tone.value === 'success'
        ? 'border-emerald-100 bg-white text-slate-700'
        : 'border-rose-100 bg-white text-slate-700',
);
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div v-if="visible && message" class="pointer-events-none fixed right-4 top-4 z-[90] w-full max-w-sm">
            <div class="pointer-events-auto overflow-hidden rounded-[24px] border shadow-[0_18px_44px_rgba(15,23,42,0.14)]" :class="toneClasses">
                <div class="flex items-start gap-3 px-4 py-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl"
                        :class="tone === 'success' ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500'"
                    >
                        <CheckCircle2 v-if="tone === 'success'" class="h-5 w-5" />
                        <XCircle v-else class="h-5 w-5" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-[var(--app-ink)]">{{ tone === 'success' ? 'Berhasil' : 'Perlu perhatian' }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ message }}</p>
                    </div>
                </div>

                <div class="h-1 w-full" :class="tone === 'success' ? 'bg-emerald-400' : 'bg-rose-400'" />
            </div>
        </div>
    </transition>
</template>
