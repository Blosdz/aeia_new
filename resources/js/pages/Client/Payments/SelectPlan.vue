<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowRight, ChevronLeft, ChevronRight, DollarSign } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface Plan {
  id: number;
  name: string;
  amount_min: number;
  amount_max: number;
  membership?: number | null;
  img_url: string | null;
  category: string;
}

const props = defineProps<{
  plans: Plan[];
  userMembershipStatus?: Record<number, boolean>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Pagos', href: route('clients.payments') },
  { title: 'Seleccionar Plan', href: route('clients.payments.select') },
];

// Variables del carrusel
const currentPage = ref(0);
const isAutoRotating = ref(true);
const screenWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);
let autoRotateInterval: NodeJS.Timeout | null = null;

// Determinar cuántos planes por página (siempre 3 en carrusel)
const itemsPerPage = computed(() => {
  return 3;  // Siempre mostrar 3 cards en el carrusel
});

// Planes visibles en la página actual
const visiblePlans = computed(() => {
  const start = currentPage.value * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return props.plans.slice(start, end);
});

// Total de páginas
const totalPages = computed(() => {
  return Math.ceil(props.plans.length / itemsPerPage.value);
});

// Verificar navegación
const hasNextPage = computed(() => currentPage.value < totalPages.value - 1);
const hasPrevPage = computed(() => currentPage.value > 0);

// Inicializar
onMounted(() => {
  const handleResize = () => {
    screenWidth.value = window.innerWidth;
  };
  window.addEventListener('resize', handleResize);
  startAutoRotate();
  
  return () => {
    window.removeEventListener('resize', handleResize);
    stopAutoRotate();
  };
});

// Limpiar
onUnmounted(() => {
  stopAutoRotate();
});

// Navegación
const nextPage = () => {
  if (hasNextPage.value) {
    currentPage.value++;
    isAutoRotating.value = false;
  }
};

const prevPage = () => {
  if (hasPrevPage.value) {
    currentPage.value--;
    isAutoRotating.value = false;
  }
};

const goToPage = (page: number) => {
  currentPage.value = page;
  isAutoRotating.value = false;
};

// Auto-rotación
const startAutoRotate = () => {
  if (autoRotateInterval) clearInterval(autoRotateInterval);
  
  autoRotateInterval = setInterval(() => {
    if (isAutoRotating.value && totalPages.value > 1) {
      if (currentPage.value < totalPages.value - 1) {
        currentPage.value++;
      } else {
        currentPage.value = 0;
      }
    }
  }, 6000);
};

const stopAutoRotate = () => {
  if (autoRotateInterval) {
    clearInterval(autoRotateInterval);
    autoRotateInterval = null;
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
  }).format(amount);
};

const formatCurrencyDecimal = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount);
};
</script>

<template>
  <Head title="Seleccionar Plan de Inversión" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-8 p-6">
      <!-- Header -->
      <div class="text-center space-y-2">
        <h1 class="text-4xl font-bold">Planes de Inversión</h1>
        <p class="text-muted-foreground text-lg">Desliza para explorar nuestros planes disponibles</p>
      </div>

      <!-- Estado vacío -->
      <div v-if="!props.plans || props.plans.length === 0" class="text-center py-20">
        <p class="text-muted-foreground text-lg">No hay planes disponibles en este momento</p>
      </div>

      <!-- Planes: carrusel por defecto -->
      <div v-else class="space-y-8">
        <!-- Carrusel (por defecto) -->
        <div class="block">
          <div class="relative group">
            <div class="relative">
              <div class="overflow-hidden">
                <transition-group 
                  name="carousel-fade" 
                  tag="div" 
                  class="flex gap-6 justify-center px-4 py-8"
                >
                  <Link
                    v-for="plan in visiblePlans"
                    :key="`plan-${currentPage}-${plan.id}`"
                    :href="route('clients.payments.select.detail', plan.id)"
                    class="flex-shrink-0 w-full sm:w-96"
                  >
                    <div class="rounded-xl overflow-hidden border border-slate-700 bg-slate-800 text-white shadow-lg hover:shadow-xl transition-shadow duration-300 h-96">
                      <div class="p-8 h-full flex flex-col justify-between">
                        <div>
                          <div class="flex items-start justify-between mb-6">
                            <div>
                              <h3 class="text-2xl font-bold">{{ plan.name }}</h3>
                              <p class="text-sm text-slate-300 mt-2">Plan de inversión</p>
                            </div>
                            <div class="text-right">
                              <div class="text-3xl font-bold text-teal-300">{{ formatCurrency(plan.amount_min) }}</div>
                              <div class="text-xs text-slate-400">/ desde</div>
                            </div>
                          </div>

                          <div class="border-t border-slate-700 pt-4 text-slate-300 text-sm space-y-3">
                            <div class="flex justify-between">
                              <span>Min</span>
                              <span class="font-medium">{{ formatCurrency(plan.amount_min) }}</span>
                            </div>
                            <div class="flex justify-between">
                              <span>Max</span>
                              <span class="font-medium">{{ formatCurrency(plan.amount_max) }}</span>
                            </div>
                            <div v-if="plan.membership" class="flex justify-between">
                              <span>Membresía</span>
                              <span class="font-medium">{{ plan.membership ? formatCurrencyDecimal(plan.membership) : '—' }}</span>
                            </div>
                          </div>
                        </div>

                        <div class="mt-6 space-y-3">
                          <Button class="w-full bg-teal-300 text-black font-semibold rounded-md py-3 hover:bg-teal-400">Elegir Plan</Button>
                          <div class="bg-slate-900 border border-slate-700 p-4 text-xs text-slate-400 rounded-md text-center">
                            <span class="uppercase font-semibold text-slate-200">{{ plan.category }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </Link>
                </transition-group>
              </div>

              <button
                v-if="hasPrevPage"
                @click="prevPage"
                class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-6 z-10 group/nav"
              >
                <div class="h-12 w-12 rounded-full bg-gradient-to-r from-blue-600 to-purple-600 flex items-center justify-center text-white shadow-lg hover:shadow-xl hover:scale-110 transition-all duration-300 opacity-0 group-hover:opacity-100">
                  <ChevronLeft class="h-6 w-6" />
                </div>
              </button>

              <button
                v-if="hasNextPage"
                @click="nextPage"
                class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-6 z-10 group/nav"
              >
                <div class="h-12 w-12 rounded-full bg-gradient-to-r from-blue-600 to-purple-600 flex items-center justify-center text-white shadow-lg hover:shadow-xl hover:scale-110 transition-all duration-300 opacity-0 group-hover:opacity-100">
                  <ChevronRight class="h-6 w-6" />
                </div>
              </button>
            </div>
          </div>
          <!-- Puntos indicador -->
          <div class="flex justify-center gap-2 mt-4">
            <button
              v-for="(_, idx) in totalPages"
              :key="`dot-${idx}`"
              @click="goToPage(idx)"
              :class="{
                'w-3 h-3 rounded-full': true,
                'bg-white': idx === currentPage,
                'bg-gray-500': idx !== currentPage,
              }"
            ></button>
          </div>
        </div>

        <!-- Grid (oculto) -->
        <div class="hidden">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="plan in props.plans"
            :key="plan.id"
            class="rounded-xl overflow-hidden border border-slate-700 bg-slate-800 text-white shadow-lg"
          >
            <div class="p-6">
              <div class="flex items-start justify-between">
                <div>
                  <h3 class="text-lg font-semibold">{{ plan.name }}</h3>
                  <p class="text-sm text-slate-300 mt-1">Plan de inversión</p>
                </div>
                <div class="text-right">
                  <div class="text-2xl font-bold">{{ formatCurrency(plan.amount_min) }}</div>
                  <div class="text-xs text-slate-400">/ desde</div>
                </div>
              </div>

              <div class="mt-6 mb-4 border-t border-slate-700 pt-4 text-slate-300 text-sm space-y-2">
                <div class="flex justify-between">
                  <span>Min</span>
                  <span class="font-medium">{{ formatCurrency(plan.amount_min) }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Max</span>
                  <span class="font-medium">{{ formatCurrency(plan.amount_max) }}</span>
                </div>
                <div v-if="plan.membership" class="flex justify-between">
                  <span>Membresía</span>
                  <span class="font-medium">{{ plan.membership ? formatCurrencyDecimal(plan.membership) : '—' }}</span>
                </div>
              </div>

              <div class="mt-4">
                <Link :href="route('clients.payments.select.detail', plan.id)">
                  <Button class="w-full bg-teal-300 text-black font-semibold rounded-md py-2">Select Plan</Button>
                </Link>
              </div>
            </div>
            <div class="bg-slate-900 border-t border-slate-700 p-3 text-xs text-slate-400">
              <div class="flex items-center justify-between">
                <span>Learn more</span>
                <span class="uppercase font-semibold text-slate-200">{{ plan.category }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Animaciones del carrusel */
.carousel-fade-enter-active,
.carousel-fade-leave-active {
  transition: all 0.6s cubic-bezier(0.4, 0.0, 0.2, 1);
}

.carousel-fade-enter-from {
  opacity: 0;
  transform: translateX(40px);
}

.carousel-fade-leave-to {
  opacity: 0;
  transform: translateX(-40px);
}

/* Hover effect smooth */
button:hover:not(:disabled) {
  transition: all 0.3s ease-out;
}

/* Scroll smooth */
.overflow-hidden {
  scroll-behavior: smooth;
}
</style>
