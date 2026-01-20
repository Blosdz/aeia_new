<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

// reactive copy so we can auto-dismiss
const flash = ref((page as any).props.flash ?? {});

// whenever page changes, update flash
watch(
  () => (page as any).props.flash,
  (next: any) => {
    flash.value = next ?? {};
    // auto-clear after a short delay on new messages
    if (next && (next.success || next.error || next.warning || next.info)) {
      setTimeout(() => {
        flash.value = {};
      }, 6000);
    }
  },
  { immediate: true }
);

const type = computed(() => {
    if (flash.value.success) return 'success';
    if (flash.value.error) return 'error';
    if (flash.value.warning) return 'warning';
    if (flash.value.info) return 'info';
    return null;
});

const message = computed(() => {
    return flash.value[type.value ?? ''] ?? null;
});
</script>

<template>
  <div v-if="type && message" class="fixed right-4 top-4 z-50 max-w-sm">
    <div
      :class="{
        'bg-green-50 border-green-200 text-green-800': type === 'success',
        'bg-red-50 border-red-200 text-red-800': type === 'error',
        'bg-yellow-50 border-yellow-200 text-yellow-800': type === 'warning',
        'bg-blue-50 border-blue-200 text-blue-800': type === 'info',
      }
      "
      class="rounded-md border p-3 shadow"
    >
      <div class="flex items-start gap-2">
        <div class="flex-1 text-sm leading-snug">
          <div class="font-medium" v-if="type === 'success'">Éxito</div>
          <div class="font-medium" v-else-if="type === 'error'">Error</div>
          <div class="font-medium" v-else-if="type === 'warning'">Atención</div>
          <div class="font-medium" v-else-if="type === 'info'">Info</div>
          <div class="mt-1">{{ message }}</div>
        </div>
  <button @click="flash.value = {}" class="text-xs opacity-70">Cerrar</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* small adjustments if needed */
</style>
