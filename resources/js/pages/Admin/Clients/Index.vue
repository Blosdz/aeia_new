<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Users, CheckCircle, Clock, XCircle, Eye, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface Profile {
  dni: string;
  phone: string;
  phone_extension: string;
  verification_status: 'pending' | 'verified' | 'rejected';
  verified_at: string | null;
}

interface Client {
  id: number;
  name: string;
  email: string;
  unique_code: string;
  is_active: boolean;
  roles: string[];
  profile: Profile | null;
  created_at: string;
  last_login: string | null;
}

interface PaginatedClients {
  data: Client[];
  links: Array<{ url: string | null; label: string; active: boolean }>;
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const props = defineProps<{
  clients: PaginatedClients;
  filters: { search: string; status: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: route('admin.dashboard') },
  { title: 'Clientes', href: '#' },
];

const search = ref(props.filters.search);
const statusFilter = ref(props.filters.status);

const handleSearch = () => {
  const url = new URL(route('admin.clients.index'), window.location.origin);
  if (search.value) url.searchParams.append('search', search.value);
  if (statusFilter.value) url.searchParams.append('status', statusFilter.value);
  window.location.href = url.toString();
};

const getStatusBadge = (status: string) => {
  const statusMap = {
    pending: { label: 'Pendiente', variant: 'secondary' as const, icon: Clock },
    verified: { label: 'Verificado', variant: 'default' as const, icon: CheckCircle },
    rejected: { label: 'Rechazado', variant: 'destructive' as const, icon: XCircle },
  };
  return statusMap[status as keyof typeof statusMap] || { label: status, variant: 'secondary' as const, icon: Clock };
};

const formatDate = (date: string | null) => {
  if (!date) return 'Nunca';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const getRoleColor = (role: string) => {
  const colors: Record<string, string> = {
    client: 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
    client_business: 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200',
  };
  return colors[role] || 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
};
</script>

<template>
  <Head title="Gestión de Clientes" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold">Gestión de Clientes</h1>
          <p class="text-muted-foreground mt-1">Verifica y administra los clientes del sistema</p>
        </div>
        <div class="text-right">
          <p class="text-2xl font-bold">{{ clients.total }}</p>
          <p class="text-xs text-muted-foreground">Total de clientes</p>
        </div>
      </div>

      <!-- Filtros -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
              <div class="relative">
                <Search class="absolute left-3 top-3 h-5 w-5 text-muted-foreground" />
                <Input
                  v-model="search"
                  type="text"
                  placeholder="Buscar por nombre, email o código..."
                  class="pl-10"
                  @keyup.enter="handleSearch"
                />
              </div>
            </div>
            <select v-model="statusFilter" class="px-3 py-2 border rounded-lg dark:bg-gray-900" @change="handleSearch">
              <option value="">Todos los estados</option>
              <option value="pending">Pendiente</option>
              <option value="verified">Verificado</option>
              <option value="rejected">Rechazado</option>
            </select>
            <Button @click="handleSearch" variant="default">
              Buscar
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Tabla de clientes -->
      <Card>
        <CardHeader>
          <CardTitle>Clientes ({{ clients.total }})</CardTitle>
          <CardDescription>Clientes registrados en el sistema</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="border-b">
                <tr>
                  <th class="text-left py-3 px-4 font-semibold">Cliente</th>
                  <th class="text-left py-3 px-4 font-semibold">Email</th>
                  <th class="text-left py-3 px-4 font-semibold">Rol</th>
                  <th class="text-left py-3 px-4 font-semibold">Verificación</th>
                  <th class="text-left py-3 px-4 font-semibold">Teléfono</th>
                  <th class="text-left py-3 px-4 font-semibold">Último Acceso</th>
                  <th class="text-center py-3 px-4 font-semibold">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="client in clients.data" :key="client.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-900 transition">
                  <td class="py-3 px-4">
                    <div>
                      <p class="font-semibold">{{ client.name }}</p>
                      <p class="text-xs text-muted-foreground">{{ client.unique_code }}</p>
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-sm">{{ client.email }}</p>
                  </td>
                  <td class="py-3 px-4">
                    <div class="flex gap-1 flex-wrap">
                      <Badge
                        v-for="role in client.roles"
                        :key="role"
                        :class="getRoleColor(role)"
                        variant="outline"
                      >
                        {{ role }}
                      </Badge>
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <div v-if="client.profile" class="flex items-center gap-2">
                      <component 
                        :is="getStatusBadge(client.profile.verification_status).icon" 
                        class="h-4 w-4"
                      />
                      <Badge :variant="getStatusBadge(client.profile.verification_status).variant">
                        {{ getStatusBadge(client.profile.verification_status).label }}
                      </Badge>
                    </div>
                    <span v-else class="text-xs text-muted-foreground">Sin perfil</span>
                  </td>
                  <td class="py-3 px-4">
                    <div v-if="client.profile" class="text-xs">
                      <p>{{ client.profile.phone }}</p>
                      <p class="text-muted-foreground">{{ client.profile.phone_extension }}</p>
                    </div>
                    <span v-else class="text-xs text-muted-foreground">-</span>
                  </td>
                  <td class="py-3 px-4 text-xs text-muted-foreground">
                    {{ formatDate(client.last_login) }}
                  </td>
                  <td class="py-3 px-4">
                    <div class="flex items-center justify-center">
                      <Link :href="route('admin.clients.show', client.id)">
                        <Button variant="ghost" size="icon" title="Ver detalle">
                          <Eye class="h-4 w-4" />
                        </Button>
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div v-if="clients.last_page > 1" class="flex justify-center gap-2 mt-6">
            <Link
              v-for="link in clients.links"
              :key="link.label"
              :href="link.url || '#'"
              class="px-3 py-2 rounded border"
              :class="link.active ? 'bg-primary text-white' : 'bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800'"
            >
              {{ link.label }}
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
