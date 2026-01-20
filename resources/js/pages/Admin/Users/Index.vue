<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Users, Plus, Edit2, Trash2, Eye, EyeOff, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface User {
  id: number;
  name: string;
  email: string;
  unique_code: string;
  is_active: boolean;
  roles: string[];
  last_login: string | null;
  created_at: string;
}

interface PaginatedUsers {
  data: User[];
  links: Array<{ url: string | null; label: string; active: boolean }>;
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const props = defineProps<{
  users: PaginatedUsers;
  roles: string[];
  filters: { search: string; role: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: route('admin.dashboard') },
  { title: 'Usuarios', href: '#' },
];

const search = ref(props.filters.search);
const roleFilter = ref(props.filters.role);

const handleSearch = () => {
  const url = new URL(route('admin.users.index'), window.location.origin);
  if (search.value) url.searchParams.append('search', search.value);
  if (roleFilter.value) url.searchParams.append('role', roleFilter.value);
  window.location.href = url.toString();
};

const toggleStatus = (user: User) => {
  useForm({}).patch(route('admin.users.toggle_status', user.id));
};

const deleteUser = (user: User) => {
  if (confirm(`¿Estás seguro de que quieres eliminar a ${user.name}?`)) {
    useForm({}).delete(route('admin.users.destroy', user.id));
  }
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
    admin: 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
    client: 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
    client_business: 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200',
    staff: 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
    support: 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
  };
  return colors[role] || 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
};
</script>

<template>
  <Head title="Gestión de Usuarios" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold">Gestión de Usuarios</h1>
          <p class="text-muted-foreground mt-1">Administra los usuarios del sistema</p>
        </div>
        <Link href="/admin/users/create">
          <Button class="gap-2">
            <Plus class="h-5 w-5" />
            Nuevo Usuario
          </Button>
        </Link>
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
            <select v-model="roleFilter" class="px-3 py-2 border rounded-lg dark:bg-gray-900" @change="handleSearch">
              <option value="">Todos los roles</option>
              <option v-for="role in roles" :key="role" :value="role">
                {{ role }}
              </option>
            </select>
            <Button @click="handleSearch" variant="default">
              Buscar
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Tabla de usuarios -->
      <Card>
        <CardHeader>
          <CardTitle>Usuarios ({{ users.total }})</CardTitle>
          <CardDescription>Total: {{ users.total }} usuarios</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="border-b">
                <tr>
                  <th class="text-left py-3 px-4 font-semibold">Nombre</th>
                  <th class="text-left py-3 px-4 font-semibold">Email</th>
                  <th class="text-left py-3 px-4 font-semibold">Roles</th>
                  <th class="text-left py-3 px-4 font-semibold">Estado</th>
                  <th class="text-left py-3 px-4 font-semibold">Último Acceso</th>
                  <th class="text-center py-3 px-4 font-semibold">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users.data" :key="user.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-900 transition">
                  <td class="py-3 px-4">
                    <div>
                      <p class="font-semibold">{{ user.name }}</p>
                      <p class="text-xs text-muted-foreground">{{ user.unique_code }}</p>
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-sm">{{ user.email }}</p>
                  </td>
                  <td class="py-3 px-4">
                    <div class="flex gap-1 flex-wrap">
                      <Badge
                        v-for="role in user.roles"
                        :key="role"
                        :class="getRoleColor(role)"
                        variant="outline"
                      >
                        {{ role }}
                      </Badge>
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <Badge :variant="user.is_active ? 'default' : 'destructive'">
                      {{ user.is_active ? 'Activo' : 'Inactivo' }}
                    </Badge>
                  </td>
                  <td class="py-3 px-4 text-xs text-muted-foreground">
                    {{ formatDate(user.last_login) }}
                  </td>
                  <td class="py-3 px-4">
                    <div class="flex items-center justify-center gap-2">
                      <!-- Toggle status -->
                      <Button
                        variant="ghost"
                        size="icon"
                        @click="toggleStatus(user)"
                        :title="user.is_active ? 'Desactivar' : 'Activar'"
                      >
                        <component :is="user.is_active ? Eye : EyeOff" class="h-4 w-4" />
                      </Button>

                      <!-- Editar -->
                      <Link :href="route('admin.users.edit', user.id)">
                        <Button variant="ghost" size="icon" title="Editar">
                          <Edit2 class="h-4 w-4" />
                        </Button>
                      </Link>

                      <!-- Eliminar -->
                      <Button
                        variant="ghost"
                        size="icon"
                        @click="deleteUser(user)"
                        title="Eliminar"
                        class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div v-if="users.last_page > 1" class="flex justify-center gap-2 mt-6">
            <Link
              v-for="link in users.links"
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
