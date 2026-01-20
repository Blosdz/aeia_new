<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Calendar, DollarSign } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface History {
  event: string;
  previous_amount: number;
  new_amount: number;
  reason: string;
  recorded_at: string;
}

interface Commission {
  id: number;
  subscription_code: string;
  subscription_id: number;
  amount: number;
  percentage: number;
  status: string;
  calculated_at: string;
  paid_at: string;
  histories: History[];
}

const props = defineProps<{
  commissions: any; // Paginated collection
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard Staff', href: route('staff.dashboard') },
  { title: 'Comisiones', href: route('staff.commissions') },
];

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getStatusBadge = (status: string) => {
  const statusMap = {
    pending: { label: 'Pendiente', variant: 'secondary' as const },
    calculated: { label: 'Calculada', variant: 'outline' as const },
    paid: { label: 'Pagada', variant: 'default' as const },
  };
  return statusMap[status as keyof typeof statusMap] || { label: status, variant: 'secondary' as const };
};
</script>

<template>
  <Head title="Mis Comisiones" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/staff/dashboard">
          <Button variant="ghost" size="icon">
            <ArrowLeft class="h-5 w-5" />
          </Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold">Mis Comisiones</h1>
          <p class="text-muted-foreground mt-1">Historial detallado de todas tus comisiones</p>
        </div>
      </div>

      <!-- Comisiones Listado -->
      <div class="space-y-4">
        <div v-for="commission in props.commissions.data" :key="commission.id" class="border rounded-lg overflow-hidden">
          <!-- Header de la comisión -->
          <div class="p-4 bg-muted/50 border-b flex items-center justify-between">
            <div class="flex-1">
              <p class="font-semibold text-lg">{{ commission.subscription_code }}</p>
              <p class="text-xs text-muted-foreground">ID de Suscripción: {{ commission.subscription_id }}</p>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(commission.amount) }}</p>
              <p class="text-xs text-muted-foreground">{{ commission.percentage }}% de comisión</p>
            </div>
            <Badge :variant="getStatusBadge(commission.status).variant" class="ml-4">
              {{ getStatusBadge(commission.status).label }}
            </Badge>
          </div>

          <!-- Detalles -->
          <div class="p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <p class="text-xs text-muted-foreground mb-1 uppercase tracking-wide">Calculada</p>
                <p class="font-semibold flex items-center gap-2">
                  <Calendar class="h-4 w-4" />
                  {{ commission.calculated_at ? formatDate(commission.calculated_at) : 'Pendiente' }}
                </p>
              </div>
              <div v-if="commission.paid_at">
                <p class="text-xs text-muted-foreground mb-1 uppercase tracking-wide">Pagada</p>
                <p class="font-semibold flex items-center gap-2">
                  <DollarSign class="h-4 w-4" />
                  {{ formatDate(commission.paid_at) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground mb-1 uppercase tracking-wide">Estado Pago</p>
                <Badge :variant="getStatusBadge(commission.status).variant">
                  {{ getStatusBadge(commission.status).label }}
                </Badge>
              </div>
            </div>

            <!-- Historial de cambios -->
            <div v-if="commission.histories.length > 0" class="border-t pt-4">
              <p class="font-semibold mb-3 text-sm">Historial de Cambios</p>
              <div class="space-y-2 max-h-40 overflow-y-auto">
                <div v-for="(history, idx) in commission.histories" :key="idx" class="text-xs p-2 bg-muted/50 rounded-sm">
                  <div class="flex justify-between items-start">
                    <div>
                      <p class="font-semibold capitalize">{{ history.event }}</p>
                      <p class="text-muted-foreground">{{ history.reason }}</p>
                    </div>
                    <span class="font-mono">
                      {{ formatCurrency(history.previous_amount) }} → {{ formatCurrency(history.new_amount) }}
                    </span>
                  </div>
                  <p class="text-muted-foreground mt-1">{{ formatDate(history.recorded_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginación -->
      <div v-if="props.commissions.links" class="flex justify-center gap-2">
        <Link
          v-for="link in props.commissions.links"
          :key="link.label"
          :href="link.url"
          :only="['commissions']"
          class="px-3 py-1 border rounded-md text-sm"
          :class="{ 'bg-primary text-white border-primary': link.active, 'hover:bg-muted': !link.active }"
          v-html="link.label"
        />
      </div>
    </div>
  </AppLayout>
</template>
