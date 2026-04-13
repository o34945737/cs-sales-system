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
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-rose-200 bg-rose-50 text-rose-700',
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
            <div class="pointer-events-auto rounded-2xl border px-4 py-3 shadow-[0_16px_40px_rgba(15,23,42,0.14)] backdrop-blur" :class="toneClasses">
                <div class="flex items-start gap-3">
                    <CheckCircle2 v-if="tone === 'success'" class="mt-0.5 h-5 w-5" />
                    <XCircle v-else class="mt-0.5 h-5 w-5" />
                    <div class="min-w-0">
                        <p class="text-sm font-semibold">{{ tone === 'success' ? 'Berhasil' : 'Perlu perhatian' }}</p>
                        <p class="mt-1 text-sm">{{ message }}</p>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
