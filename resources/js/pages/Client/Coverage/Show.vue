<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ShieldCheck, Calendar, DollarSign, Users } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Coverage {
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
  subscriptionParticipants?: any[];
  rewards?: any[];
}

const props = defineProps<{
  coverage: Coverage;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Coberturas', href: route('clients.coverage') },
  { title: props.coverage?.planType?.name, href: '#' },
];

const isExpired = (expiresAt: string | null) => {
  if (!expiresAt) return false;
  return new Date(expiresAt) < new Date();
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
  }).format(amount || 0);
};

const formatDate = (date: string | null) => {
  if (!date) return 'Sin vencimiento';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};
</script>

<template>
  <Head title="Detalles de Cobertura" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold">{{ coverage?.planType?.name }}</h1>
          <p class="text-muted-foreground mt-1">{{ coverage?.unique_code }}</p>
        </div>
        <Badge :variant="isExpired(coverage?.expires_at) ? 'destructive' : 'default'">
          {{ isExpired(coverage?.expires_at) ? 'Vencida' : 'Activa' }}
        </Badge>
      </div>

      <!-- Información Principal -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm flex items-center gap-2">
              <Calendar class="h-4 w-4" />
              Inicio
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-medium">{{ formatDate(coverage?.started_at) }}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm flex items-center gap-2">
              <Calendar class="h-4 w-4" />
              Vencimiento
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-medium">{{ formatDate(coverage?.expires_at) }}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm flex items-center gap-2">
              <DollarSign class="h-4 w-4" />
              Rango
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-medium">
              {{ formatCurrency(coverage?.planType?.amount_min) }} - {{ formatCurrency(coverage?.planType?.amount_max) }}
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Detalles de Cobertura -->
      <Card>
        <CardHeader>
          <CardTitle>Detalles de la Cobertura</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div>
              <p class="text-sm text-muted-foreground">Nombre del Plan</p>
              <p class="font-bold">{{ coverage?.planType?.name }}</p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Código Único</p>
              <p class="font-mono font-medium">{{ coverage?.unique_code }}</p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Período de Cobertura</p>
              <p class="font-medium">
                {{ formatDate(coverage?.started_at) }} hasta {{ formatDate(coverage?.expires_at) }}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Participantes -->
      <Card v-if="coverage?.subscriptionParticipants && coverage.subscriptionParticipants.length > 0">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Users class="h-5 w-5" />
            Participantes
          </CardTitle>
          <CardDescription>{{ coverage.subscriptionParticipants.length }} participante(s)</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div
              v-for="participant in coverage.subscriptionParticipants"
              :key="participant.id"
              class="p-3 border rounded-lg"
            >
              <p class="font-medium capitalize">{{ participant.role }}</p>
              <p class="text-xs text-muted-foreground">
                {{ participant.profile?.first_name }} {{ participant.profile?.last_name }}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Recompensas -->
      <Card v-if="coverage?.rewards && coverage.rewards.length > 0">
        <CardHeader>
          <CardTitle>Recompensas Generadas</CardTitle>
          <CardDescription>{{ coverage.rewards.length }} recompensa(s)</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div v-for="reward in coverage.rewards" :key="reward.id" class="p-3 border rounded-lg">
              <div class="flex justify-between">
                <div>
                  <p class="font-medium capitalize">{{ reward.reason }}</p>
                  <p class="text-xs text-muted-foreground">{{ formatDate(reward.period_at) }}</p>
                </div>
                <p class="font-bold">{{ formatCurrency(reward.amount) }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
