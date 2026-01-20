<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, CheckCircle, Plus, UserPlus, Users, Trash2 } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';

interface Plan {
  id: number;
  name: string;
  amount_min: number;
  amount_max: number;
  category: string;
}

interface ClientAccount {
  id: number;
  bank_name: string;
  holder_name: string;
  last4: string;
}

interface Beneficiary {
  id: number;
  profile_id: number;
  first_name: string;
  last_name: string;
  sex: string;
  type: string;
  type_document: string;
  dni: string;
  phone_extension: string;
  phone: string;
  nacionality: string;
  city: string;
  verification_status: 'pending' | 'verified' | 'rejected';
  verified_at: string | null;
}

const props = defineProps<{
  coverage: Plan;
  clientAccounts: ClientAccount[];
  beneficiaries: Beneficiary[];
  profileId: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Coberturas', href: route('clients.coverage') },
  { title: 'Seleccionar Cobertura', href: route('clients.coverage.select') },
  { title: props.coverage?.name || 'Detalle', href: '#' },
];

// Estado para el diálogo de crear beneficiario
const isDialogOpen = ref(false);

// Formulario para crear beneficiario
const beneficiaryForm = useForm({
  profile_id: props.profileId,
  first_name: '',
  last_name: '',
  sex: '',
  type: 'user',
  type_document: '',
  dni: '',
  phone_extension: '',
  phone: '',
  nacionality: '',
  city: '',
});
console.log(beneficiaryForm);

// Formulario para contratar cobertura
const coverageForm = useForm({
  plan_type_id: props.coverage?.id,
  beneficiary_ids: [] as number[],
  client_account_id: props.clientAccounts[0]?.id || '',
  currency: 'USD',
});

// Precio por beneficiario (tomado del plan amount_min)
const pricePerBeneficiary = computed(() => props.coverage?.amount_min || 0);

// Total a pagar
const totalAmount = computed(() => {
  return pricePerBeneficiary.value * coverageForm.beneficiary_ids.length;
});

// Seleccionar/deseleccionar beneficiario
const toggleBeneficiary = (beneficiaryId: number) => {
  const index = coverageForm.beneficiary_ids.indexOf(beneficiaryId);
  if (index > -1) {
    coverageForm.beneficiary_ids.splice(index, 1);
  } else {
    coverageForm.beneficiary_ids.push(beneficiaryId);
  }
};

// Verificar si un beneficiario está seleccionado
const isBeneficiarySelected = (beneficiaryId: number) => {
  return coverageForm.beneficiary_ids.includes(beneficiaryId);
};

// Crear beneficiario
const submitBeneficiary = () => {
  beneficiaryForm.post(route('clients.beneficiaries.store'), {
    onSuccess: () => {
      isDialogOpen.value = false;
      beneficiaryForm.reset();
    },
  });
};

// Contratar cobertura
const submitCoverage = () => {
  if (coverageForm.beneficiary_ids.length === 0) {
    alert('Debes seleccionar al menos un beneficiario');
    return;
  }

  coverageForm.post(route('clients.coverage.store'), {
    onSuccess: () => {
      router.visit(route('clients.coverage'));
    },
  });
};

// Eliminar beneficiario
const deleteBeneficiary = (beneficiaryId: number) => {
  if (confirm('¿Estás seguro de eliminar este beneficiario?')) {
    router.delete(route('clients.beneficiaries.destroy', beneficiaryId), {
      preserveScroll: true,
    });
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount);
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'verified':
      return 'default';
    case 'pending':
      return 'secondary';
    case 'rejected':
      return 'destructive';
    default:
      return 'secondary';
  }
};

const getStatusText = (status: string) => {
  switch (status) {
    case 'verified':
      return 'Verificado';
    case 'pending':
      return 'Pendiente';
    case 'rejected':
      return 'Rechazado';
    default:
      return status;
  }
};
</script>

<template>
  <Head :title="`Cobertura ${coverage?.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-4 md:space-y-6 p-4 md:p-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl md:text-3xl font-bold">{{ coverage?.name }}</h1>
        <p class="text-sm md:text-base text-muted-foreground mt-1">Gestiona tus beneficiarios y contrata tu cobertura</p>
      </div>

      <!-- Advertencia si no tiene cuentas -->
      <div v-if="!clientAccounts || clientAccounts.length === 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex gap-3">
        <AlertCircle class="h-5 w-5 text-yellow-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-yellow-900">No tienes cuentas de pago registradas</p>
          <p class="text-sm text-yellow-700 mt-1">Debes agregar una cuenta bancaria antes de contratar una cobertura</p>
        </div>
      </div>

      <div class="grid gap-4 md:gap-6 lg:grid-cols-3">
        <!-- Lista de Beneficiarios -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
              <div>
                <CardTitle class="flex items-center gap-2">
                  <Users class="h-5 w-5" />
                  Beneficiarios
                </CardTitle>
                <CardDescription class="text-xs sm:text-sm">
                  {{ beneficiaries?.length || 0 }} beneficiario(s) registrado(s)
                </CardDescription>
              </div>
              <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                  <Button size="sm" variant="outline" class="w-full sm:w-auto">
                    <Plus class="h-4 w-4 mr-2" />
                    <span class="hidden sm:inline">Agregar Beneficiario</span>
                    <span class="sm:hidden">Agregar</span>
                  </Button>
                </DialogTrigger>
                <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto w-[95vw] sm:w-full">
                  <DialogHeader>
                    <DialogTitle class="text-lg sm:text-xl">Agregar Nuevo Beneficiario</DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm">
                      Complete la información del beneficiario para la cobertura
                    </DialogDescription>
                  </DialogHeader>

                  <form @submit.prevent="submitBeneficiary" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <Label class="mb-4" for="first_name">Nombres</Label>
                        <Input
                          id="first_name"
                          v-model="beneficiaryForm.first_name"
                          placeholder="Nombres"
                          required
                        />
                        <span v-if="beneficiaryForm.errors.first_name" class="text-sm text-red-500">
                          {{ beneficiaryForm.errors.first_name }}
                        </span>
                      </div>

                      <div>
                        <Label class="mb-4"for="last_name">Apellidos</Label>
                        <Input
                          id="last_name"
                          v-model="beneficiaryForm.last_name"
                          placeholder="Apellidos"
                          required
                        />
                        <span v-if="beneficiaryForm.errors.last_name" class="text-sm text-red-500">
                          {{ beneficiaryForm.errors.last_name }}
                        </span>
                      </div>

                      <div>
                        <Label class="mb-4" for="sex">Sexo</Label>
                        <Select v-model="beneficiaryForm.sex">
                          <SelectTrigger id="sex">
                            <SelectValue placeholder="Selecciona sexo" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectGroup>
                              <SelectItem value="M">Masculino</SelectItem>
                              <SelectItem value="F">Femenino</SelectItem>
                              <SelectItem value="O">Otro</SelectItem>
                            </SelectGroup>
                          </SelectContent>
                        </Select>
                        <span v-if="beneficiaryForm.errors.sex" class="text-sm text-red-500">
                          {{ beneficiaryForm.errors.sex }}
                        </span>
                      </div>

                      <div>
                        <Label class="mb-4" for="type_document">Tipo de Documento</Label>
                        <Select v-model="beneficiaryForm.type_document">
                          <SelectTrigger id="type_document">
                            <SelectValue placeholder="Tipo de documento" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectGroup>
                              <SelectItem value="DNI">DNI</SelectItem>
                              <SelectItem value="Passport">Pasaporte</SelectItem>
                              <SelectItem value="CE">Carnet de Extranjería</SelectItem>
                            </SelectGroup>
                          </SelectContent>
                        </Select>
                        <span v-if="beneficiaryForm.errors.type_document" class="text-sm text-red-500">
                          {{ beneficiaryForm.errors.type_document }}
                        </span>
                      </div>

                      <div>
                        <Label class="mb-4" for="dni">Número de Documento</Label>
                        <Input
                          id="dni"
                          v-model="beneficiaryForm.dni"
                          placeholder="Número de documento"
                          required
                        />
                        <span v-if="beneficiaryForm.errors.dni" class="text-sm text-red-500">
                          {{ beneficiaryForm.errors.dni }}
                        </span>
                      </div>

                      <div class="sm:col-span-2">
                        <Label class="mb-4" for="phone">Teléfono</Label>
                        <div class="flex gap-2">
                          <Input
                            id="phone_extension"
                            v-model="beneficiaryForm.phone_extension"
                            placeholder="+51"
                            class="w-16 sm:w-20"
                          />
                          <Input
                            id="phone"
                            v-model="beneficiaryForm.phone"
                            placeholder="Número de teléfono"
                            class="flex-1"
                          />
                        </div>
                        <span v-if="beneficiaryForm.errors.phone" class="text-sm text-red-500">
                          {{ beneficiaryForm.errors.phone }}
                        </span>
                      </div>

                      <div>
                        <Label class="mb-4" for="nacionality">Nacionalidad</Label>
                        <Input
                          id="nacionality"
                          v-model="beneficiaryForm.nacionality"
                          placeholder="País de nacionalidad"
                        />
                        <span v-if="beneficiaryForm.errors.nacionality" class="text-sm text-red-500">
                          {{ beneficiaryForm.errors.nacionality }}
                        </span>
                      </div>

                      <div>
                        <Label class="mb-4" for="city">Ciudad</Label>
                        <Input
                          id="city"
                          v-model="beneficiaryForm.city"
                          placeholder="Ciudad de residencia"
                        />
                        <span v-if="beneficiaryForm.errors.city" class="text-sm text-red-500">
                          {{ beneficiaryForm.errors.city }}
                        </span>
                      </div>
                    </div>

                    <DialogFooter class="flex-col sm:flex-row gap-2">
                      <Button type="button" variant="outline" @click="isDialogOpen = false" class="w-full sm:w-auto">
                        Cancelar
                      </Button>
                      <Button type="submit" :disabled="beneficiaryForm.processing" class="w-full sm:w-auto">
                        {{ beneficiaryForm.processing ? 'Guardando...' : 'Guardar Beneficiario' }}
                      </Button>
                    </DialogFooter>
                  </form>
                </DialogContent>
              </Dialog>
            </div>
          </CardHeader>
          <CardContent>
            <!-- Sin beneficiarios -->
            <div v-if="!beneficiaries || beneficiaries.length === 0" class="text-center py-8 md:py-12">
              <UserPlus class="h-10 w-10 md:h-12 md:w-12 mx-auto text-gray-400 mb-4" />
              <p class="text-sm md:text-base text-muted-foreground mb-2">No tienes beneficiarios registrados</p>
              <p class="text-xs md:text-sm text-gray-500 mb-4 px-4">
                Debes agregar al menos un beneficiario para contratar una cobertura
              </p>
              <Button @click="isDialogOpen = true" variant="outline" class="w-full sm:w-auto">
                <Plus class="h-4 w-4 mr-2" />
                Agregar Primer Beneficiario
              </Button>
            </div>

            <!-- Lista de beneficiarios -->
            <div v-else class="space-y-3">
              <div
                v-for="beneficiary in beneficiaries"
                :key="beneficiary.id"
                class="p-3 md:p-4 border rounded-lg hover:shadow-md transition cursor-pointer"
                :class="{
                  'border-blue-500 bg-blue-50 dark:bg-blue-950': isBeneficiarySelected(beneficiary.id),
                  'border-gray-200': !isBeneficiarySelected(beneficiary.id)
                }"
                @click="toggleBeneficiary(beneficiary.id)"
              >
                <div class="flex flex-col sm:flex-row sm:justify-between gap-3">
                  <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                      <h3 class="text-sm md:text-base font-bold">
                        {{ beneficiary.first_name }} {{ beneficiary.last_name }}
                      </h3>
                      <Badge :variant="getStatusBadge(beneficiary.verification_status)" class="text-xs">
                        {{ getStatusText(beneficiary.verification_status) }}
                      </Badge>
                      <CheckCircle
                        v-if="isBeneficiarySelected(beneficiary.id)"
                        class="h-4 w-4 md:h-5 md:w-5 text-blue-600"
                      />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 md:gap-2 text-xs md:text-sm text-muted-foreground">
                      <p class="truncate">
                        <span class="font-medium">{{ beneficiary.type_document }}:</span>
                        {{ beneficiary.dni }}
                      </p>
                      <p class="truncate">
                        <span class="font-medium">Teléfono:</span>
                        {{ beneficiary.phone_extension }} {{ beneficiary.phone }}
                      </p>
                      <p class="truncate">
                        <span class="font-medium">Nacionalidad:</span>
                        {{ beneficiary.nacionality }}
                      </p>
                      <p class="truncate">
                        <span class="font-medium">Ciudad:</span>
                        {{ beneficiary.city }}
                      </p>
                    </div>

                    <div class="mt-2 text-xs md:text-sm font-medium text-blue-600">
                      Costo: {{ formatCurrency(pricePerBeneficiary) }}
                    </div>
                  </div>

                  <Button
                    variant="ghost"
                    size="sm"
                    @click.stop="deleteBeneficiary(beneficiary.id)"
                    class="text-red-500 hover:text-red-700 self-start sm:self-center"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Resumen y Pago -->
        <div class="space-y-4">
          <!-- Resumen de Plan -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg md:text-xl">Plan Seleccionado</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 md:space-y-4">
              <div>
                <p class="text-xs md:text-sm text-muted-foreground">Plan</p>
                <p class="text-sm md:text-base font-bold">{{ coverage?.name }}</p>
              </div>

              <div>
                <p class="text-xs md:text-sm text-muted-foreground">Precio por beneficiario</p>
                <p class="text-lg md:text-xl font-bold">{{ formatCurrency(pricePerBeneficiary) }}</p>
              </div>

              <div>
                <p class="text-xs md:text-sm text-muted-foreground">Beneficiarios seleccionados</p>
                <p class="text-xl md:text-2xl font-bold">{{ coverageForm.beneficiary_ids.length }}</p>
              </div>

              <div class="pt-3 border-t">
                <p class="text-xs md:text-sm text-muted-foreground">Total a pagar</p>
                <p class="text-2xl md:text-3xl font-bold text-blue-600">{{ formatCurrency(totalAmount) }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Formulario de Pago -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg md:text-xl">Método de Pago</CardTitle>
            </CardHeader>
            <CardContent>
              <form @submit.prevent="submitCoverage" class="space-y-3 md:space-y-4">
                <!-- Cuenta de Pago -->
                <div>
                  <Label for="client_account_id">Cuenta de Pago</Label>
                  <Select v-model="coverageForm.client_account_id">
                    <SelectTrigger id="client_account_id" class="mt-2">
                      <SelectValue placeholder="Selecciona una cuenta" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem
                          v-for="account in clientAccounts"
                          :key="account.id"
                          :value="String(account.id)"
                        >
                          {{ account.bank_name }} - •••• {{ account.last4 }}
                        </SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <span v-if="coverageForm.errors.client_account_id" class="text-sm text-red-500">
                    {{ coverageForm.errors.client_account_id }}
                  </span>
                </div>

                <!-- Moneda -->
                <div>
                  <Label for="currency">Moneda</Label>
                  <Select v-model="coverageForm.currency">
                    <SelectTrigger id="currency" class="mt-2">
                      <SelectValue placeholder="Selecciona moneda" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem value="USD">USD - Dólar</SelectItem>
                        <SelectItem value="EUR">EUR - Euro</SelectItem>
                        <SelectItem value="PEN">PEN - Sol Peruano</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                </div>

                <div class="bg-purple-50 dark:bg-purple-950 p-2.5 md:p-3 rounded-lg text-xs text-purple-600 dark:text-purple-300">
                  ℹ️ Cada beneficiario requiere una suscripción individual. El pago se procesará para todos los beneficiarios seleccionados.
                </div>

                <Button
                  type="submit"
                  class="w-full text-sm md:text-base"
                  :disabled="coverageForm.processing || coverageForm.beneficiary_ids.length === 0 || !clientAccounts?.length"
                >
                  {{ coverageForm.processing ? 'Procesando...' : 'Contratar Cobertura' }}
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
