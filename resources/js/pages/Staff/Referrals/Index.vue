<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Mail, Calendar, DollarSign, ShoppingCart } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Referral {
  id: number;
  name: string;
  email: string;
  created_at: string;
  payment_count: number;
  subscription_count: number;
  total_invested: number;
  advisor_commission: number;
  status: string;
}

const props = defineProps<{
  referrals: any; // Paginated collection
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard Staff', href: route('staff.dashboard') },
  { title: 'Referidos', href: route('staff.referrals') },
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
  });
};

const getStatusBadge = (status: string) => {
  const statusMap = {
    Activo: { label: 'Activo', variant: 'default' as const },
    Pendiente: { label: 'Pendiente', variant: 'secondary' as const },
  };
  return statusMap[status as keyof typeof statusMap] || { label: status, variant: 'secondary' as const };
};
</script>

<template>
  <Head title="Mis Referidos" />

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
          <h1 class="text-3xl font-bold">Mis Referidos</h1>
          <p class="text-muted-foreground mt-1">Gestión de usuarios referidos y sus comisiones</p>
        </div>
      </div>

      <!-- Referidos Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card v-for="referral in props.referrals.data" :key="referral.id" class="hover:shadow-lg transition-shadow">
          <!-- Header Card -->
          <CardHeader class="pb-3">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <CardTitle class="text-lg">{{ referral.name }}</CardTitle>
                <CardDescription class="flex items-center gap-1 mt-1">
                  <Mail class="h-3 w-3" />
                  {{ referral.email }}
                </CardDescription>
              </div>
              <Badge :variant="getStatusBadge(referral.status).variant">
                {{ getStatusBadge(referral.status).label }}
              </Badge>
            </div>
          </CardHeader>

          <!-- Content -->
          <CardContent class="space-y-4">
            <!-- Fecha de referencia -->
            <div class="flex items-center gap-2 text-sm">
              <Calendar class="h-4 w-4 text-muted-foreground" />
              <span class="text-muted-foreground">Referido el {{ formatDate(referral.created_at) }}</span>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-3 p-3 bg-muted/50 rounded-lg">
              <div>
                <p class="text-xs text-muted-foreground">Pagos</p>
                <p class="text-2xl font-bold">{{ referral.payment_count }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Suscripciones</p>
                <p class="text-2xl font-bold">{{ referral.subscription_count }}</p>
              </div>
            </div>

            <!-- Inversión total -->
            <div>
              <p class="text-xs text-muted-foreground mb-1 uppercase tracking-wide">Monto Invertido</p>
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ formatCurrency(referral.total_invested) }}
              </p>
            </div>

            <!-- Comisión ganada -->
            <div class="border-t pt-3">
              <p class="text-xs text-muted-foreground mb-1 uppercase tracking-wide">Tu Comisión</p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ formatCurrency(referral.advisor_commission) }}
              </p>
            </div>

            <!-- Action Button -->
            <Button variant="outline" class="w-full" size="sm">
              Ver Detalle
            </Button>
          </CardContent>
        </Card>
      </div>

      <!-- Paginación -->
      <div v-if="props.referrals.links && props.referrals.links.length > 0" class="flex justify-center gap-2 mt-6">
        <Link
          v-for="link in props.referrals.links"
          :key="link.label"
          :href="link.url"
          :only="['referrals']"
          class="px-3 py-1 border rounded-md text-sm"
          :class="{ 'bg-primary text-white border-primary': link.active, 'hover:bg-muted': !link.active }"
          v-html="link.label"
        />
      </div>
    </div>
  </AppLayout>
</template>
