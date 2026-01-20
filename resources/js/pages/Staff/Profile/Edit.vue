<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { User, Phone, MapPin, FileText, Edit2, CheckCircle2, AlertCircle, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface User {
  id: number;
  name: string;
  email: string;
}

interface Profile {
  id: number;
  first_name: string;
  last_name: string;
  type: string;
  phone: string | null;
  phone_extension: string | null;
  city: string | null;
  country: string | null;
  dni: string | null;
  type_document: string | null;
  nacionality: string | null;
  job: string | null;
  birthdate: string | null;
  sex: string | null;
  bio: string | null;
  verified: boolean;
}

const props = defineProps<{
  user: User;
  profile: Profile | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('staff.dashboard') },
  { title: 'Perfil', href: '#' },
];

const showSuccess = ref(false);

const form = useForm({
  first_name: props.profile?.first_name ?? '',
  last_name: props.profile?.last_name ?? '',
  phone: props.profile?.phone ?? '',
  phone_extension: props.profile?.phone_extension ?? '',
  city: props.profile?.city ?? '',
  country: props.profile?.country ?? '',
  dni: props.profile?.dni ?? '',
  type_document: props.profile?.type_document ?? '',
  nacionality: props.profile?.nacionality ?? '',
  job: props.profile?.job ?? '',
  birthdate: props.profile?.birthdate ?? '',
  sex: props.profile?.sex ?? '',
  bio: props.profile?.bio ?? '',
});

const getDocumentTypeName = (type: string | null | undefined) => {
  const types: Record<string, string> = {
    DNI: 'DNI',
    PASSPORT: 'Pasaporte',
    RUC: 'RUC',
  };
  return types[type as string] || 'N/A';
};

const getSexName = (sex: string | null | undefined) => {
  const sexes: Record<string, string> = {
    M: 'Masculino',
    F: 'Femenino',
    O: 'Otro',
  };
  return sexes[sex as string] || 'N/A';
};

const formatDate = (date: string | null | undefined) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const submit = () => {
  form.put(route('staff.profile.update'), {
    onSuccess: () => {
      showSuccess.value = true;
      setTimeout(() => {
        showSuccess.value = false;
      }, 3000);
    },
  });
};

const cancelEdit = () => {
  form.reset();
};
</script>

<template>
  <Head title="Mi Perfil" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Mensaje de éxito -->
      <div v-if="showSuccess" class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
        <CheckCircle2 class="h-5 w-5 text-green-600 flex-shrink-0" />
        <div>
          <p class="font-medium text-green-900">¡Perfil actualizado!</p>
          <p class="text-sm text-green-700">Tus cambios han sido guardados exitosamente.</p>
        </div>
      </div>

      <!-- Resumen de Información Registrada -->
      <Card class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-blue-900 dark:text-blue-100">
            <FileText class="h-5 w-5" />
            Información Registrada en el Sistema
          </CardTitle>
          <CardDescription class="text-blue-700 dark:text-blue-300">Estos son todos los datos que tenemos registrados de ti</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Nombres -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Nombre</p>
              <p class="text-sm font-medium">{{ profile?.first_name || '—' }} {{ profile?.last_name || '—' }}</p>
            </div>

            <!-- Email -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Email</p>
              <p class="text-sm font-medium break-all">{{ user?.email || '—' }}</p>
            </div>

            <!-- DNI -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Documento</p>
              <p class="text-sm font-medium">{{ profile?.dni || '—' }} ({{ getDocumentTypeName(profile?.type_document) }})</p>
            </div>

            <!-- Teléfono -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Teléfono</p>
              <p class="text-sm font-medium">{{ profile?.phone_extension || '' }} {{ profile?.phone || '—' }}</p>
            </div>

            <!-- Ciudad -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Ciudad</p>
              <p class="text-sm font-medium">{{ profile?.city || '—' }}</p>
            </div>

            <!-- País -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">País</p>
              <p class="text-sm font-medium">{{ profile?.country || '—' }}</p>
            </div>

            <!-- Nacionalidad -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Nacionalidad</p>
              <p class="text-sm font-medium">{{ profile?.nacionality || '—' }}</p>
            </div>

            <!-- Ocupación -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Ocupación</p>
              <p class="text-sm font-medium">{{ profile?.job || '—' }}</p>
            </div>

            <!-- Género -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Género</p>
              <p class="text-sm font-medium">{{ getSexName(profile?.sex) }}</p>
            </div>

            <!-- Fecha de Nacimiento -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Nacimiento</p>
              <p class="text-sm font-medium">{{ formatDate(profile?.birthdate) }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Información del Usuario - Editar -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Edit2 class="h-5 w-5" />
            Editar Mi Información Personal
          </CardTitle>
          <CardDescription>Actualiza tu información de perfil</CardDescription>
        </CardHeader>
        <CardContent>
          <!-- Formulario de edición -->
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Nombres -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="first_name" class="font-semibold">Nombre</Label>
                <Input
                  id="first_name"
                  v-model="form.first_name"
                  type="text"
                  :placeholder="profile?.first_name ? `Tu nombre: ${profile.first_name}` : 'Tu nombre'"
                />
                <span v-if="form.errors.first_name" class="text-sm text-red-500 block">{{ form.errors.first_name }}</span>
              </div>
              <div class="space-y-2">
                <Label for="last_name" class="font-semibold">Apellido</Label>
                <Input
                  id="last_name"
                  v-model="form.last_name"
                  type="text"
                  :placeholder="profile?.last_name ? `Tu apellido: ${profile.last_name}` : 'Tu apellido'"
                />
                <span v-if="form.errors.last_name" class="text-sm text-red-500 block">{{ form.errors.last_name }}</span>
              </div>
            </div>

            <!-- Teléfono y Ciudad -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="phone" class="flex items-center gap-2 font-semibold">
                  <Phone class="h-4 w-4" />
                  Teléfono
                </Label>
                <div class="flex gap-2">
                  <Input
                    id="phone_extension"
                    v-model="form.phone_extension"
                    type="text"
                    placeholder="+1"
                    class="w-24"
                  />
                  <Input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    :placeholder="profile?.phone ? `Tel: ${profile.phone}` : '1234567890'"
                    class="flex-1"
                  />
                </div>
                <span v-if="form.errors.phone" class="text-sm text-red-500 block">{{ form.errors.phone }}</span>
              </div>
              <div class="space-y-2">
                <Label for="city" class="flex items-center gap-2 font-semibold">
                  <MapPin class="h-4 w-4" />
                  Ciudad
                </Label>
                <Input
                  id="city"
                  v-model="form.city"
                  type="text"
                  :placeholder="profile?.city ? `Ciudad: ${profile.city}` : 'Tu ciudad'"
                />
                <span v-if="form.errors.city" class="text-sm text-red-500 block">{{ form.errors.city }}</span>
              </div>
            </div>

            <!-- País y Nacionalidad -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="country" class="font-semibold">País</Label>
                <Input
                  id="country"
                  v-model="form.country"
                  type="text"
                  :placeholder="profile?.country ? `País: ${profile.country}` : 'Tu país'"
                />
                <span v-if="form.errors.country" class="text-sm text-red-500 block">{{ form.errors.country }}</span>
              </div>
              <div class="space-y-2">
                <Label for="nacionality" class="font-semibold">Nacionalidad</Label>
                <Input
                  id="nacionality"
                  v-model="form.nacionality"
                  type="text"
                  :placeholder="profile?.nacionality ? `Nacionalidad: ${profile.nacionality}` : 'Tu nacionalidad'"
                />
                <span v-if="form.errors.nacionality" class="text-sm text-red-500 block">{{ form.errors.nacionality }}</span>
              </div>
            </div>

            <!-- Documento de Identidad -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="type_document" class="flex items-center gap-2 font-semibold">
                  <FileText class="h-4 w-4" />
                  Tipo de Documento
                </Label>
                <Select v-model="form.type_document">
                  <SelectTrigger id="type_document">
                    <SelectValue :placeholder="profile?.type_document ? `Tipo: ${getDocumentTypeName(profile.type_document)}` : 'Selecciona tipo de documento'" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectItem value="DNI">DNI</SelectItem>
                      <SelectItem value="PASSPORT">Pasaporte</SelectItem>
                      <SelectItem value="RUC">RUC</SelectItem>
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <span v-if="form.errors.type_document" class="text-sm text-red-500 block">{{ form.errors.type_document }}</span>
              </div>
              <div class="space-y-2">
                <Label for="dni" class="font-semibold">Número de Documento</Label>
                <Input
                  id="dni"
                  v-model="form.dni"
                  type="text"
                  :placeholder="profile?.dni ? `Documento: ${profile.dni}` : 'Tu número de documento'"
                />
                <span v-if="form.errors.dni" class="text-sm text-red-500 block">{{ form.errors.dni }}</span>
              </div>
            </div>

            <!-- Información Adicional -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="job" class="font-semibold">Ocupación</Label>
                <Input
                  id="job"
                  v-model="form.job"
                  type="text"
                  :placeholder="profile?.job ? `Ocupación: ${profile.job}` : 'Tu ocupación'"
                />
                <span v-if="form.errors.job" class="text-sm text-red-500 block">{{ form.errors.job }}</span>
              </div>
              <div class="space-y-2">
                <Label for="sex" class="font-semibold">Género</Label>
                <Select v-model="form.sex">
                  <SelectTrigger id="sex">
                    <SelectValue :placeholder="profile?.sex ? `Género: ${getSexName(profile.sex)}` : 'Selecciona género'" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectItem value="M">Masculino</SelectItem>
                      <SelectItem value="F">Femenino</SelectItem>
                      <SelectItem value="O">Otro</SelectItem>
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <span v-if="form.errors.sex" class="text-sm text-red-500 block">{{ form.errors.sex }}</span>
              </div>
            </div>

            <!-- Fecha de Nacimiento -->
            <div class="space-y-2">
              <Label for="birthdate" class="font-semibold">Fecha de Nacimiento</Label>
              <Input
                id="birthdate"
                v-model="form.birthdate"
                type="date"
              />
              <div v-if="form.birthdate" class="text-sm text-muted-foreground mt-1">
                Seleccionado: {{ formatDate(form.birthdate) }}
              </div>
              <span v-if="form.errors.birthdate" class="text-sm text-red-500 block">{{ form.errors.birthdate }}</span>
            </div>

            <!-- Biografía -->
            <div class="space-y-2">
              <Label for="bio" class="font-semibold">Biografía</Label>
              <textarea
                id="bio"
                v-model="form.bio"
                placeholder="Cuéntanos sobre ti..."
                class="w-full px-3 py-2 border border-input rounded-md bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                rows="4"
              />
              <span v-if="form.errors.bio" class="text-sm text-red-500 block">{{ form.errors.bio }}</span>
            </div>

            <!-- Botones -->
            <div class="flex gap-2 justify-end pt-4">
              <Button variant="outline" type="button" @click="cancelEdit">Cancelar</Button>
              <Button type="submit" :disabled="form.processing" class="gap-2">
                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Estado de Verificación -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <CheckCircle2 class="h-5 w-5" />
            Estado de Verificación
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium">Perfil Verificado</p>
              <p class="text-sm text-muted-foreground">
                {{ profile?.verified ? 'Tu perfil ha sido verificado' : 'Tu perfil aún no ha sido verificado' }}
              </p>
            </div>
            <div
              :class="{
                'px-3 py-1 rounded-full text-sm font-medium flex items-center gap-2': true,
                'bg-green-100 text-green-800': profile?.verified,
                'bg-yellow-100 text-yellow-800': !profile?.verified,
              }"
            >
              <CheckCircle2 v-if="profile?.verified" class="h-4 w-4" />
              <AlertCircle v-else class="h-4 w-4" />
              {{ profile?.verified ? 'Verificado' : 'Pendiente' }}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
