<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Phone, MapPin, FileText, Edit2, CheckCircle2, AlertCircle, Loader2, CreditCard, Trash2, Star } from 'lucide-vue-next';
import { ref, onMounted, reactive } from 'vue';
import type { BreadcrumbItem } from '@/types';
import axios from 'axios';

interface ProfileData {
  id?: number | null;
  user_id: number;
  first_name: string | null;
  last_name: string | null;
  email: string;
  phone: string | null;
  phone_extension: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  type_document: string | null;
  dni: string | null;
  country_dni: string | null;
  nacionality: string | null;
  job: string | null;
  birthdate: string | null;
  sex: string | null;
  photos_dni: string[] | null;
  verified: boolean;
}

interface CreditCardData {
  id: number;
  holder_name: string;
  bank_name: string;
  card_type: string;
  card_type_name: string;
  last_four: string;
  exp_month: number | string;
  exp_year: number;
  is_expired: boolean;
  is_default: boolean;
  created_at: string;
}

const props = defineProps<{
  profile: ProfileData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Perfil', href: route('clients.profile') },
];

const showSuccess = ref(false);
const creditCards = ref<CreditCardData[]>([]);
const isLoadingCards = ref(false);
const showCardForm = ref(false);

const form = useForm({
  first_name: props.profile?.first_name || '',
  last_name: props.profile?.last_name || '',
  phone: props.profile?.phone || '',
  phone_extension: props.profile?.phone_extension || '',
  city: props.profile?.city || '',
  state: props.profile?.state || '',
  country: props.profile?.country || '',
  type_document: props.profile?.type_document || '',
  country_dni: props.profile?.country_dni || '',
  dni: props.profile?.dni || '',
  nacionality: props.profile?.nacionality || '',
  job: props.profile?.job || '',
  birthdate: props.profile?.birthdate || '',
  sex: props.profile?.sex || '',
  photos_dni: props.profile?.photos_dni || [],
});

const cardForm = reactive({
  holder_name: '',
  card_number: '',
  exp_month: '',
  exp_year: '',
  cvv: '',
  bank_name: '',
  address_wallet: '',
  is_default: false,
  processing: false,
  errors: {} as Record<string, string>,
});

const getDocumentTypeName = (type: string | null) => {
  const types: Record<string, string> = {
    DNI: 'DNI',
    PASSPORT: 'Pasaporte',
    RUC: 'RUC',
  };
  return types[type as string] || 'N/A';
};

const getSexName = (sex: string | null) => {
  const sexes: Record<string, string> = {
    M: 'Masculino',
    F: 'Femenino',
    O: 'Otro',
  };
  return sexes[sex as string] || 'N/A';
};

const formatDate = (date: string | null) => {
  if (!date) return 'N/A';
  // Dividir la fecha en partes para evitar problemas de zona horaria
  const [year, month, day] = date.split('-').map(Number);
  // Crear la fecha usando los componentes locales
  const localDate = new Date(year, month - 1, day);
  return localDate.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const submit = () => {
  form.put(route('clients.profile.update'), {
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

const handleDNIFileUpload = (event: Event, side: 'front' | 'back') => {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      const result = e.target?.result as string;
      if (!form.photos_dni) {
        form.photos_dni = [];
      }
      if (side === 'front') {
        form.photos_dni[0] = result;
      } else {
        form.photos_dni[1] = result;
      }
    };
    reader.readAsDataURL(file);
  }
};

// Funciones para tarjetas de crédito
const loadCreditCards = async () => {
  isLoadingCards.value = true;
  try {
    const response = await axios.get(route('clients.credit_cards.index'));
    creditCards.value = response.data.success ? response.data.data : response.data;
  } catch (error) {
    console.error('Error cargando tarjetas:', error);
  } finally {
    isLoadingCards.value = false;
  }
};

const addCreditCard = async () => {
  cardForm.processing = true;
  cardForm.errors = {};

  // Validar número de tarjeta antes de enviar
  const cleanCardNumber = cardForm.card_number.replace(/\s+/g, '').replace(/[^0-9]/g, '');
  console.log('Tarjeta a enviar:', {
    holder_name: cardForm.holder_name,
    card_number: cleanCardNumber,
    exp_month: cardForm.exp_month,
    exp_year: cardForm.exp_year,
    cvv: cardForm.cvv,
    bank_name: cardForm.bank_name,
  });

  if (cleanCardNumber.length < 13 || cleanCardNumber.length > 19) {
    cardForm.errors = {
      card_number: ['El número de tarjeta debe tener entre 13 y 19 dígitos.'],
    };
    cardForm.processing = false;
    return;
  }

  if (!luhnCheck(cleanCardNumber)) {
    cardForm.errors = {
      card_number: ['El número de tarjeta no es válido. Verifica que sea correcto.'],
    };
    cardForm.processing = false;
    return;
  }

  try {
    const response = await axios.post(route('clients.credit_cards.store'), {
      holder_name: cardForm.holder_name,
      card_number: cleanCardNumber,
      exp_month: cardForm.exp_month,
      exp_year: cardForm.exp_year,
      cvv: cardForm.cvv,
      bank_name: cardForm.bank_name,
      address_wallet: cardForm.address_wallet,
      is_default: cardForm.is_default,
    }, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    if (response.data && response.data.data) {
      // Agregar la nueva tarjeta a la lista
      creditCards.value.push(response.data.data);
      
      // Resetear el formulario
      cardForm.holder_name = '';
      cardForm.card_number = '';
      cardForm.exp_month = '';
      cardForm.exp_year = '';
      cardForm.cvv = '';
      cardForm.bank_name = '';
      cardForm.address_wallet = '';
      cardForm.is_default = false;
      showCardForm.value = false;

      showSuccess.value = true;
      setTimeout(() => {
        showSuccess.value = false;
      }, 3000);
    }
  } catch (error: any) {
    console.error('Error agregando tarjeta:', error);
    if (error.response?.data?.errors) {
      cardForm.errors = error.response.data.errors;
    } else if (error.response?.data?.message) {
      console.error('Error:', error.response.data.message);
      cardForm.errors = {
        general: [error.response.data.message],
      };
    }
  } finally {
    cardForm.processing = false;
  }
};

const setDefaultCard = async (cardId: number) => {
  try {
    const response = await axios.put(route('clients.credit_cards.set_default', cardId));

    if (response.data) {
      // Actualizar el estado local
      creditCards.value.forEach((card) => {
        card.is_default = card.id === cardId;
      });
    }
  } catch (error) {
    console.error('Error estableciendo tarjeta por defecto:', error);
  }
};

const deleteCard = async (cardId: number) => {
  if (!confirm('¿Estás seguro de que deseas eliminar esta tarjeta?')) {
    return;
  }

  try {
    const response = await axios.delete(route('clients.credit_cards.destroy', cardId));

    if (response.data) {
      // Remover la tarjeta de la lista
      creditCards.value = creditCards.value.filter((card) => card.id !== cardId);
    }
  } catch (error) {
    console.error('Error eliminando tarjeta:', error);
  }
};

onMounted(() => {
  loadCreditCards();
});

// Función para obtener gradientes de tarjeta
const getCardGradient = (cardId: number) => {
  const gradients = [
    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', // Púrpura
    'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)', // Rosa/Rojo
    'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', // Azul/Cian
    'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)', // Verde/Turquesa
    'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', // Rosa/Amarillo
    'linear-gradient(135deg, #30cfd0 0%, #330867 100%)', // Cian/Púrpura oscuro
  ];
  return gradients[cardId % gradients.length];
};

// Función para obtener marca de tarjeta
const getCardBrand = (card: CreditCardData) => {
  return card.card_type_name || 'VISA';
};

// Función para validar número de tarjeta con Luhn
const luhnCheck = (cardNumber: string): boolean => {
  let sum = 0;
  let isEven = false;

  for (let i = cardNumber.length - 1; i >= 0; i--) {
    let digit = parseInt(cardNumber[i], 10);

    if (isEven) {
      digit *= 2;
      if (digit > 9) {
        digit -= 9;
      }
    }

    sum += digit;
    isEven = !isEven;
  }

  return sum % 10 === 0;
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


      <!-- Resumen de Información Registrada -->
      <Card class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-blue-900 dark:text-blue-100">
            <FileText class="h-5 w-5" />
            Información Registrada en el Sistema
          </CardTitle>
          <CardDescription class="text-blue-700 dark:text-blue-300">Estos son todos los datos que tenemos registrados de ti (sin tarjeta de crédito)</CardDescription>
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
              <p class="text-sm font-medium break-all">{{ profile?.email || '—' }}</p>
            </div>

            <!-- Country DNI -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Pais del Documento</p>
              <p class="text-sm font-medium">{{ profile?.country_dni || '—' }} ({{ getDocumentTypeName(profile?.type_document) }})</p>
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

            <!-- Estado/Provincia -->
            <div class="space-y-1">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Estado/Provincia</p>
              <p class="text-sm font-medium">{{ profile?.state || '—' }}</p>
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

            <!-- País, Estado y Nacionalidad -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                <Label for="state" class="font-semibold">Estado/Provincia</Label>
                <Input
                  id="state"
                  v-model="form.state"
                  type="text"
                  :placeholder="profile?.state ? `Estado: ${profile.state}` : 'Tu estado o provincia'"
                />
                <span v-if="form.errors.state" class="text-sm text-red-500 block">{{ form.errors.state }}</span>
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
              <div class="space-y-2">
                <Label for="country_dni" class="font-semibold">Pais Emisor del Documento</Label>
                <Input
                  id="country_dni"
                  v-model="form.country_dni"
                  type="text"
                  :placeholder="profile?.country_dni ? `Documento: ${profile.country_dni}` : 'Pais Emisor del documento'"
                />
                <span v-if="form.errors.country_dni" class="text-sm text-red-500 block">{{ form.errors.country_dni }}</span>
              </div>
            </div>

            <!-- Imágenes de Documento de Identificación -->
            <div class="space-y-3 border rounded-lg p-4 bg-background dark:bg-slate-950">
              <h3 class="font-semibold text-lg flex items-center gap-2">
                <FileText class="h-5 w-5" />
                Documento de Identificación
              </h3>
              <p class="text-sm text-muted-foreground">Carga las imágenes del frente y reverso de tu documento de identificación</p>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- DNI Frente -->
                <div class="space-y-2">
                  <Label for="dni_front" class="font-semibold">DNI Frente</Label>
                  <Input
                    id="dni_front"
                    type="file"
                    accept="image/*"
                    @change="(e) => handleDNIFileUpload(e, 'front')"
                    class="cursor-pointer file:cursor-pointer file:border-0 file:bg-primary file:text-primary-foreground file:px-3 file:py-1 hover:file:bg-primary/90"
                  />
                  <div v-if="profile?.photos_dni?.[0]" class="mt-2 relative">
                    <img :src="profile.photos_dni[0]" alt="DNI Frente" class="w-full h-48 object-cover rounded-lg border border-input" />
                    <p class="text-xs text-muted-foreground mt-1">Frente cargado</p>
                  </div>
                </div>

                <!-- DNI Reverso -->
                <div class="space-y-2">
                  <Label for="dni_back" class="font-semibold">DNI Reverso</Label>
                  <Input
                    id="dni_back"
                    type="file"
                    accept="image/*"
                    @change="(e) => handleDNIFileUpload(e, 'back')"
                    class="cursor-pointer file:cursor-pointer file:border-0 file:bg-primary file:text-primary-foreground file:px-3 file:py-1 hover:file:bg-primary/90"
                  />
                  <div v-if="profile?.photos_dni?.[1]" class="mt-2 relative">
                    <img :src="profile.photos_dni[1]" alt="DNI Reverso" class="w-full h-48 object-cover rounded-lg border border-input" />
                    <p class="text-xs text-muted-foreground mt-1">Reverso cargado</p>
                  </div>
                </div>
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

      
      <!-- Tarjetas de Crédito -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <CreditCard class="h-5 w-5" />
              <div>
                <CardTitle>Tarjetas de Crédito</CardTitle>
                <CardDescription>Gestiona tus tarjetas de pago</CardDescription>
              </div>
            </div>
            <Button
              v-if="!showCardForm"
              @click="showCardForm = true"
              class="gap-2"
            >
              <CreditCard class="h-4 w-4" />
              Agregar Tarjeta
            </Button>
          </div>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Formulario para agregar tarjeta -->
          <div v-if="showCardForm" class="border rounded-lg p-4 bg-muted/50 space-y-4">
            <h3 class="font-semibold text-lg">Nueva Tarjeta de Crédito</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="holder_name" class="font-semibold">Titular de la Tarjeta</Label>
                <Input
                  id="holder_name"
                  v-model="cardForm.holder_name"
                  type="text"
                  placeholder="Nombre completo"
                />
                <span v-if="cardForm.errors.holder_name" class="text-sm text-red-500 block">{{ cardForm.errors.holder_name }}</span>
              </div>
              <div class="space-y-2">
                <Label for="bank_name" class="font-semibold">Banco/Entidad</Label>
                <Input
                  id="bank_name"
                  v-model="cardForm.bank_name"
                  type="text"
                  placeholder="Ej: Banco Pichincha"
                />
                <span v-if="cardForm.errors.bank_name" class="text-sm text-red-500 block">{{ cardForm.errors.bank_name }}</span>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="card_number" class="font-semibold">Número de Tarjeta</Label>
              <Input
                id="card_number"
                v-model="cardForm.card_number"
                type="text"
                placeholder="4111111111111111"
                @input="cardForm.card_number = cardForm.card_number.replace(/\s+/g, '').slice(0, 19)"
              />
              <span v-if="cardForm.errors.card_number" class="text-sm text-red-500 block">{{ cardForm.errors.card_number }}</span>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="exp_month" class="font-semibold">Mes</Label>
                <Input
                  id="exp_month"
                  v-model="cardForm.exp_month"
                  type="text"
                  placeholder="MM"
                  maxlength="2"
                />
                <span v-if="cardForm.errors.exp_month" class="text-sm text-red-500 block">{{ cardForm.errors.exp_month }}</span>
              </div>
              <div class="space-y-2">
                <Label for="exp_year" class="font-semibold">Año</Label>
                <Input
                  id="exp_year"
                  v-model="cardForm.exp_year"
                  type="text"
                  placeholder="YYYY"
                  maxlength="4"
                />
                <span v-if="cardForm.errors.exp_year" class="text-sm text-red-500 block">{{ cardForm.errors.exp_year }}</span>
              </div>
              <div class="space-y-2">
                <Label for="cvv" class="font-semibold">CVV</Label>
                <Input
                  id="cvv"
                  v-model="cardForm.cvv"
                  type="text"
                  placeholder="***"
                  maxlength="4"
                />
                <span v-if="cardForm.errors.cvv" class="text-sm text-red-500 block">{{ cardForm.errors.cvv }}</span>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="address_wallet" class="font-semibold">Dirección del Billetera (Opcional)</Label>
              <Input
                id="address_wallet"
                v-model="cardForm.address_wallet"
                type="text"
                placeholder="Ej: Dirección blockchain (si aplica)"
              />
            </div>

            <div class="flex items-center gap-2">
              <input
                id="is_default"
                v-model="cardForm.is_default"
                type="checkbox"
                class="h-4 w-4 rounded border-input"
              />
              <Label for="is_default" class="font-normal cursor-pointer">
                Establecer como tarjeta predeterminada
              </Label>
            </div>

            <div class="flex gap-2 justify-end">
              <Button
                variant="outline"
                type="button"
                @click="showCardForm = false"
                :disabled="cardForm.processing"
              >
                Cancelar
              </Button>
              <Button
                type="button"
                @click="addCreditCard"
                :disabled="cardForm.processing"
                class="gap-2"
              >
                <Loader2 v-if="cardForm.processing" class="h-4 w-4 animate-spin" />
                {{ cardForm.processing ? 'Agregando...' : 'Agregar Tarjeta' }}
              </Button>
            </div>
          </div>

          <!-- Lista de tarjetas -->
          <div v-if="isLoadingCards" class="text-center py-8">
            <Loader2 class="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
            <p class="text-sm text-muted-foreground mt-2">Cargando tarjetas...</p>
          </div>

          <div v-else-if="creditCards.length === 0" class="text-center py-8">
            <CreditCard class="h-12 w-12 text-muted-foreground mx-auto opacity-50" />
            <p class="text-muted-foreground mt-2">No tienes tarjetas registradas</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="card in creditCards"
              :key="card.id"
              class="group relative h-64 rounded-2xl overflow-hidden cursor-pointer transition-all duration-500 hover:shadow-2xl hover:-translate-y-2"
              :style="{
                background: getCardGradient(card.id),
              }"
            >
              <!-- Efecto de brillo/shine -->
              <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-gradient-to-r from-white via-transparent to-white transition-opacity duration-500" />
              
              <div class="relative h-full p-6 flex flex-col justify-between text-white">
                <!-- Header: Chip de tarjeta + Logo -->
                <div class="flex items-start justify-between">
                  <!-- Chip -->
                  <div class="w-12 h-8 bg-gradient-to-br from-yellow-300 to-yellow-600 rounded-lg border-2 border-yellow-700 shadow-lg" />
                  
                  <!-- Tipo de tarjeta (Visa/Mastercard) -->
                  <div class="text-right">
                    <p class="text-xs opacity-70 uppercase tracking-widest">{{ getCardBrand(card) }}</p>
                    <p class="text-sm font-bold">{{ card.bank_name }}</p>
                  </div>
                </div>

                <!-- Número de tarjeta (últimos 4 dígitos) -->
                <div class="space-y-2">
                  <p class="text-xs opacity-70 uppercase tracking-widest">Número de Tarjeta</p>
                  <p class="text-2xl font-mono font-bold tracking-wider">•••• •••• •••• {{ card.last_four }}</p>
                </div>

                <!-- Footer: Titular y vencimiento -->
                <div class="flex items-end justify-between">
                  <div class="flex-1">
                    <p class="text-xs opacity-70 uppercase tracking-widest mb-1">Card Holder</p>
                    <p class="text-sm font-semibold uppercase truncate">{{ card.holder_name }}</p>
                  </div>
                  
                  <div class="text-right">
                    <p class="text-xs opacity-70 uppercase tracking-widest mb-1">Expires</p>
                    <p class="text-lg font-mono font-semibold">{{ String(card.exp_month).padStart(2, '0') }}/{{ String(card.exp_year).slice(-2) }}</p>
                  </div>
                </div>

                <!-- Botones de acciones (aparecen al hover) -->
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3 rounded-2xl backdrop-blur-sm">
                  <button
                    v-if="!card.is_default"
                    @click.stop="setDefaultCard(card.id)"
                    class="bg-white/20 hover:bg-white/30 backdrop-blur-sm p-3 rounded-full transition-all duration-300 transform hover:scale-110"
                    title="Establecer como predeterminada"
                  >
                    <Star class="h-5 w-5 text-yellow-300" />
                  </button>
                  <div v-else class="bg-yellow-400/20 backdrop-blur-sm px-4 py-2 rounded-full flex items-center gap-2">
                    <Star class="h-4 w-4 text-yellow-300 fill-yellow-300" />
                    <span class="text-xs font-semibold text-yellow-300">Predeterminada</span>
                  </div>

                  <button
                    @click.stop="deleteCard(card.id)"
                    class="bg-red-500/20 hover:bg-red-500/40 backdrop-blur-sm p-3 rounded-full transition-all duration-300 transform hover:scale-110"
                    title="Eliminar tarjeta"
                  >
                    <Trash2 class="h-5 w-5 text-red-300" />
                  </button>
                </div>

                <!-- Badge de tarjeta predeterminada (esquina superior derecha) -->
                <div v-if="card.is_default" class="absolute top-3 right-3 bg-yellow-400/90 backdrop-blur-sm px-2 py-1 rounded-full">
                  <p class="text-xs font-bold text-black uppercase">Predeterminada</p>
                </div>

                <!-- Badge de tarjeta vencida -->
                <div v-if="card.is_expired" class="absolute top-3 left-3 bg-red-500/90 backdrop-blur-sm px-2 py-1 rounded-full">
                  <p class="text-xs font-bold text-white uppercase">Vencida</p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
