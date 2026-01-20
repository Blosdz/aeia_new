<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowLeft } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Role {
  id: number;
  name: string;
}

const props = defineProps<{
  roles: Role[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: route('admin.dashboard') },
  { title: 'Usuarios', href: route('admin.users.index') },
  { title: 'Crear', href: '#' },
];

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: [] as number[],
  is_active: true,
});

console.log('Initial props.roles:', props.roles);
console.log('Initial form.roles:', form.roles);

const submit = () => {
  form.post(route('admin.users.store'));
};

const toggleRole = (roleId: number) => {
  console.log('Toggling role:', roleId);
  console.log('Form.roles is:', form.roles, 'type:', typeof form.roles);
  
  const currentRoles = Array.isArray(form.roles) ? form.roles : [];
  const index = currentRoles.indexOf(roleId);
  
  if (index > -1) {
    // Remover
    currentRoles.splice(index, 1);
  } else {
    // Agregar
    currentRoles.push(roleId);
  }
  
  // Rehacer el array completamente
  form.roles = [...currentRoles];
  
  console.log('New form.roles:', form.roles);
};
</script>

<template>
  <Head title="Crear Usuario" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/admin/users">
          <Button variant="ghost" size="icon">
            <ArrowLeft class="h-5 w-5" />
          </Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold">Crear Nuevo Usuario</h1>
          <p class="text-muted-foreground mt-1">Agrega un nuevo usuario al sistema</p>
        </div>
      </div>

      <div class="max-w-2xl">
        <!-- Información del usuario -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Información del Usuario</CardTitle>
            <CardDescription>Datos básicos del nuevo usuario</CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-4">
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

              <!-- Contraseña -->
              <div>
                <Label for="password" class="text-sm font-semibold">Contraseña</Label>
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
              <div>
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
                <Label for="is_active" class="text-sm font-medium cursor-pointer">Activar usuario</Label>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- Roles -->
        <Card class="mb-6" :class="{ 'border-red-500': form.errors.roles }">
          <CardHeader>
            <CardTitle>Roles</CardTitle>
            <CardDescription>Asigna al menos un rol a este usuario</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="role in props.roles" :key="role.id" class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer">
                <Checkbox
                  :id="`role_${role.id}`"
                  :checked="form.roles.includes(role.id)"
                  @click="toggleRole(role.id)"
                />
                <Label :for="`role_${role.id}`" class="flex-1 font-medium cursor-pointer capitalize">
                  {{ role.name }}
                </Label>
              </div>
              <div v-if="form.errors.roles" class="text-sm text-red-500 bg-red-50 dark:bg-red-950 p-3 rounded-lg border border-red-200 dark:border-red-800 mt-3">
                <strong>⚠️ Error:</strong> {{ form.errors.roles }}
              </div>
              <div v-if="form.roles.length === 0" class="text-sm text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950 p-3 rounded-lg border border-orange-200 dark:border-orange-800 mt-3">
                <strong>📌 Nota:</strong> Debes seleccionar al menos un rol antes de crear el usuario
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Botones de acción -->
        <div class="flex gap-3">
          <Button
            type="button"
            @click="submit"
            :disabled="form.processing || form.roles.length === 0"
            class="flex-1"
          >
            {{ form.processing ? 'Creando...' : 'Crear Usuario' }}
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
