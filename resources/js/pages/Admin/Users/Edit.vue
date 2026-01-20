<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Role {
  id: number;
  name: string;
}

interface UserData {
  id: number;
  name: string;
  email: string;
  unique_code: string;
  is_active: boolean;
  roles: number[];
  created_at: string;
  last_login: string | null;
}

const props = defineProps<{
  user: UserData;
  roles: Role[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: route('admin.dashboard') },
  { title: 'Usuarios', href: route('admin.users.index') },
  { title: props.user.name, href: '#' },
];

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  password: '',
  password_confirmation: '',
  roles: props.user.roles,
  is_active: props.user.is_active,
});

const submit = () => {
  form.put(route('admin.users.update', props.user.id));
};

const deleteUser = () => {
  if (confirm(`¿Estás seguro de que quieres eliminar a ${props.user.name}? Esta acción no se puede deshacer.`)) {
    useForm({}).delete(route('admin.users.destroy', props.user.id));
  }
};

const toggleRole = (roleId: number) => {
  const index = form.roles.indexOf(roleId);
  if (index > -1) {
    form.roles.splice(index, 1);
  } else {
    form.roles.push(roleId);
  }
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};
</script>

<template>
  <Head :title="`Editar Usuario - ${user.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/admin/users">
          <Button variant="ghost" size="icon">
            <ArrowLeft class="h-5 w-5" />
          </Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold">{{ user.name }}</h1>
          <p class="text-muted-foreground mt-1">Edita la información del usuario</p>
        </div>
        <Button
          variant="destructive"
          @click="deleteUser"
          class="gap-2"
        >
          <Trash2 class="h-5 w-5" />
          Eliminar
        </Button>
      </div>

      <div class="max-w-2xl">
        <!-- Información del usuario -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Información del Usuario</CardTitle>
            <CardDescription>Actualiza los datos del usuario</CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-4">
              <!-- Código único -->
              <div>
                <Label class="text-sm font-semibold">Código Único</Label>
                <Input
                  :value="user.unique_code"
                  type="text"
                  disabled
                  class="mt-2 bg-gray-100 dark:bg-gray-800"
                />
              </div>

              <!-- Nombre -->
              <div>
                <Label for="name" class="text-sm font-semibold">Nombre Completo</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  placeholder="Juan Pérez"
                  class="mt-2"
                />
                <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
              </div>

              <!-- Email -->
              <div>
                <Label for="email" class="text-sm font-semibold">Email</Label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  placeholder="juan@example.com"
                  class="mt-2"
                />
                <span v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</span>
              </div>

              <!-- Nueva contraseña (opcional) -->
              <div>
                <Label for="password" class="text-sm font-semibold">Nueva Contraseña (dejar en blanco para no cambiar)</Label>
                <Input
                  id="password"
                  v-model="form.password"
                  type="password"
                  placeholder="••••••••"
                  class="mt-2"
                />
                <span v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</span>
              </div>

              <!-- Confirmar contraseña -->
              <div v-if="form.password">
                <Label for="password_confirmation" class="text-sm font-semibold">Confirmar Contraseña</Label>
                <Input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  placeholder="••••••••"
                  class="mt-2"
                />
                <span v-if="form.errors.password_confirmation" class="text-sm text-red-500">{{ form.errors.password_confirmation }}</span>
              </div>

              <!-- Estado -->
              <div class="flex items-center gap-2 pt-2">
                <Checkbox
                  id="is_active"
                  v-model:checked="form.is_active"
                />
                <Label for="is_active" class="text-sm font-medium cursor-pointer">Usuario activo</Label>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- Roles -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Roles</CardTitle>
            <CardDescription>Actualiza los roles del usuario</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="role in props.roles" :key="role.id" class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900">
                <Checkbox
                  :id="`role_${role.id}`"
                  :checked="form.roles.includes(role.id)"
                  @update:checked="toggleRole(role.id)"
                />
                <Label :for="`role_${role.id}`" class="flex-1 font-medium cursor-pointer">
                  {{ role.name }}
                </Label>
              </div>
              <span v-if="form.errors.roles" class="text-sm text-red-500 block mt-2">{{ form.errors.roles }}</span>
            </div>
          </CardContent>
        </Card>

        <!-- Información adicional -->
        <Card class="mb-6 bg-gray-50 dark:bg-gray-900">
          <CardHeader>
            <CardTitle class="text-base">Información Adicional</CardTitle>
          </CardHeader>
          <CardContent class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Usuario creado:</span>
              <span class="font-semibold">{{ formatDate(user.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Último acceso:</span>
              <span class="font-semibold">{{ user.last_login ? formatDate(user.last_login) : 'Nunca' }}</span>
            </div>
          </CardContent>
        </Card>

        <!-- Botones de acción -->
        <div class="flex gap-3">
          <Button
            type="submit"
            @click="submit"
            :disabled="form.processing"
            class="flex-1"
          >
            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
          </Button>
          <Link href="/admin/users" class="flex-1">
            <Button variant="outline" class="w-full">
              Cancelar
            </Button>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
