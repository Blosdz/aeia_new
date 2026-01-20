<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ArrowRight } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Plan {
  id: number;
  name: string;
  amount_min: number;
  amount_max: number;
  img_url: string | null;
  category: string;
}

const props = defineProps<{
  coverages: Plan[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Coberturas', href: route('clients.coverage') },
  { title: 'Seleccionar Cobertura', href: route('clients.coverage.select') },
];

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
  }).format(amount);
};
</script>

<template>
  <Head title="Seleccionar Cobertura" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <div>
        <h1 class="text-3xl font-bold">Planes de Cobertura Disponibles</h1>
        <p class="text-muted-foreground mt-1">Protege tus inversiones con nuestros planes de cobertura</p>
      </div>

      <div v-if="!props.coverages || props.coverages.length === 0" class="text-center py-12">
        <p class="text-muted-foreground">No hay planes de cobertura disponibles en este momento</p>
      </div>

      <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <Card
          v-for="coverage in props.coverages"
          :key="coverage.id"
          class="hover:shadow-lg transition-shadow cursor-pointer"
        >
          <CardHeader>
            <div v-if="coverage.img_url" class="mb-4 -mx-6 -mt-6">
              <img :src="coverage.img_url" :alt="coverage.name" class="w-full h-40 object-cover rounded-t-lg" />
            </div>
            <CardTitle>{{ coverage.name }}</CardTitle>
            <CardDescription>Plan de protección</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="bg-purple-50 dark:bg-purple-950 p-4 rounded-lg">
              <p class="text-sm text-muted-foreground">Rango de cobertura</p>
              <p class="text-2xl font-bold">
                {{ formatCurrency(coverage.amount_min) }} - {{ formatCurrency(coverage.amount_max) }}
              </p>
            </div>

            <div class="space-y-2 text-sm">
              <p class="flex items-center gap-2">
                <span class="text-green-600">✓</span>
                Protección completa
              </p>
              <p class="flex items-center gap-2">
                <span class="text-green-600">✓</span>
                Cobertura 24/7
              </p>
              <p class="flex items-center gap-2">
                <span class="text-green-600">✓</span>
                Atención prioritaria
              </p>
            </div>

            <Link :href="route('clients.coverage.select.detail', coverage.id)" class="block">
              <Button class="w-full">
                Continuar
                <ArrowRight class="h-4 w-4 ml-2" />
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
