<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ShieldCheck, Plus, Eye } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Subscription {
  id: number;
  unique_code: string;
  started_at: string;
  expires_at: string | null;
  planType?: {
    id: number;
    name: string;
    amount_min: number;
    amount_max: number;
  };
}

interface PaginatedData {
  data: Subscription[];
  links: any;
  meta: any;
}

const props = defineProps<{
  coverages: PaginatedData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Coberturas', href: route('clients.coverage') },
];

const formatDate = (date: string | null) => {
  if (!date) return 'Sin vencimiento';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const isExpired = (expiresAt: string | null) => {
  if (!expiresAt) return false;
  return new Date(expiresAt) < new Date();
};

const getStatus = (expiresAt: string | null) => {
  if (!expiresAt) return 'Activo';
  return isExpired(expiresAt) ? 'Vencido' : 'Activo';
};

const getStatusVariant = (expiresAt: string | null) => {
  return isExpired(expiresAt) ? 'destructive' : 'default';
};
</script>

<template>
  <Head title="Mis Coberturas" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header con Botón -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold">Mis Coberturas</h1>
          <p class="text-muted-foreground">Planes de protección activos</p>
        </div>
        <Link :href="route('clients.coverage.select')">
          <Button class="flex items-center gap-2">
            <Plus class="h-4 w-4" />
            Nueva Cobertura
          </Button>
        </Link>
      </div>

      <!-- Listado de Coberturas -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <ShieldCheck class="h-5 w-5" />
            Mis Planes de Cobertura
          </CardTitle>
          <CardDescription>{{ props.coverages?.data?.length || 0 }} cobertura(s) activa(s)</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!props.coverages?.data || props.coverages.data.length === 0" class="text-center py-12">
            <ShieldCheck class="h-12 w-12 mx-auto text-gray-400 mb-2" />
            <p class="text-muted-foreground">No tienes coberturas activas</p>
            <p class="text-sm text-gray-500 mt-2">Protege tus inversiones con una cobertura</p>
          </div>

          <div v-else class="grid gap-4">
            <div
              v-for="coverage in props.coverages.data"
              :key="coverage.id"
              class="p-4 border rounded-lg hover:shadow-md transition"
            >
              <div class="flex justify-between items-start mb-3">
                <div>
                  <h3 class="font-bold text-lg">{{ coverage.planType?.name || 'Cobertura' }}</h3>
                  <p class="text-sm text-muted-foreground">{{ coverage.unique_code }}</p>
                </div>
                <Badge :variant="getStatusVariant(coverage.expires_at)">
                  {{ getStatus(coverage.expires_at) }}
                </Badge>
              </div>

              <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
                <div>
                  <p class="text-muted-foreground">Inicio</p>
                  <p class="font-medium">{{ formatDate(coverage.started_at) }}</p>
                </div>
                <div>
                  <p class="text-muted-foreground">Vencimiento</p>
                  <p class="font-medium">{{ formatDate(coverage.expires_at) }}</p>
                </div>
                <div>
                  <p class="text-muted-foreground">Rango</p>
                  <p class="font-medium">
                    ${{ coverage.planType?.amount_min }} - ${{ coverage.planType?.amount_max }}
                  </p>
                </div>
              </div>

              <Link :href="route('clients.coverage.show', coverage.id)">
                <Button variant="outline" size="sm" class="w-full">
                  <Eye class="h-4 w-4 mr-2" />
                  Ver Detalles
                </Button>
              </Link>
            </div>
          </div>

          <!-- Paginación -->
          <div v-if="props.coverages?.meta?.total > 15" class="flex gap-2 mt-6 justify-center">
            <div v-for="link in props.coverages.links" :key="link.label">
              <Link v-if="link.url && link.label !== '&laquo; Anterior' && link.label !== 'Siguiente &raquo;'" :href="link.url" class="text-blue-600">
                <Button :variant="link.active ? 'default' : 'outline'" size="sm">{{ link.label }}</Button>
              </Link>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
