<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { Separator } from '@/components/ui/separator'
import { ScrollArea } from '../components/ui/scroll-area';
import { Card,CardContent,CardDescription,CardFooter,CardTitle,CardHeader} from '@/components/ui/card'
import { TrendingUp, Percent, DollarSign, Users, Calendar } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const tags = Array.from({ length: 50 }).map(
  (_, i, a) => `v1.2.0-beta.${a.length - i}`,
)

// Data para los cards
const totalInvested = ref(45231.89);
const gainPercent = ref(12.5);
const totalRevenue = ref(8450.50);
const activeSubscriptions = ref(5);
const closureDays = ref(42);

// Calcular días restantes dinámicamente si lo necesitas
const daysRemaining = computed(() => {
  return Math.max(0, closureDays.value);
});

</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Cards de Resumen Principal -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-5">
                <!-- Monto Total de Inversión -->
                <Card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Inversión Total</CardTitle>
                        <DollarSign class="h-4 w-4 text-blue-600 dark:text-blue-300" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">${{ totalInvested.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                        <p class="text-xs text-muted-foreground">En todos tus planes activos</p>
                    </CardContent>
                </Card>

                <!-- Porcentaje de Ganancia -->
                <Card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">% Ganancia</CardTitle>
                        <Percent class="h-4 w-4 text-green-600 dark:text-green-300" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">+{{ gainPercent }}%</div>
                        <p class="text-xs text-muted-foreground">Retorno en tu inversión</p>
                    </CardContent>
                </Card>

                <!-- Revenue Total -->
                <Card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Ingresos</CardTitle>
                        <TrendingUp class="h-4 w-4 text-purple-600 dark:text-purple-300" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">${{ totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                        <p class="text-xs text-muted-foreground">Ganancias generadas</p>
                    </CardContent>
                </Card>

                <!-- Suscripciones Activas -->
                <Card class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950 dark:to-orange-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Suscripciones</CardTitle>
                        <Users class="h-4 w-4 text-orange-600 dark:text-orange-300" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ activeSubscriptions }}</div>
                        <p class="text-xs text-muted-foreground">Planes activos</p>
                    </CardContent>
                </Card>

                <!-- Días hasta Cierre -->
                <Card class="bg-gradient-to-br from-rose-50 to-rose-100 dark:from-rose-950 dark:to-rose-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Próximo Cierre</CardTitle>
                        <Calendar class="h-4 w-4 text-rose-600 dark:text-rose-300" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ daysRemaining }} días</div>
                        <p class="text-xs text-muted-foreground">Hasta el cierre de período</p>
                    </CardContent>
                </Card>
            </div>
            <!-- <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <PlaceholderPattern />
            </div> -->  
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                <div class="rounded-xl border bg-card text-card-foreground shadow">
                    <div class="flex flex-col gap-y-1.5 p-6">
                        <h3 class="font-semibold leading-none tracking-tight">Team Members</h3>
                        <p class="text-sm text-muted-foreground"> Invite your team members to collaborate. </p>
                    </div>
                    <div class="p-6 pt-0 grid gap-6">
                        <div class="flex items-center justify-between space-x-4">
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center justify-center font-normal text-foreground select-none shrink-0 bg-secondary overflow-hidden h-10 w-10 text-xs rounded-full">
                                    <img role="img" src="/avatars/01.png" class="h-full w-full object-cover" style=""><!---->
                                </span>
                                <div>
                                    <p class="text-sm font-medium leading-none"> Sofia Davis </p>
                                    <p class="text-sm text-muted-foreground"> m@example.com </p>
                                </div>
                            </div>
                            <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 ml-auto" id="reka-popover-trigger-v-103" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="" data-state="closed">Owner 
                                    <svg viewBox="0 0 15 15" width="1.2em" height="1.2em" class="ml-2 h-4 w-4 text-muted-foreground">
                                    <path fill="currentColor" fill-rule="evenodd" d="M3.135 6.158a.5.5 0 0 1 .707-.023L7.5 9.565l3.658-3.43a.5.5 0 0 1 .684.73l-4 3.75a.5.5 0 0 1-.684 0l-4-3.75a.5.5 0 0 1-.023-.707" clip-rule="evenodd">

                                    </path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between space-x-4">
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center justify-center font-normal text-foreground select-none shrink-0 bg-secondary overflow-hidden h-10 w-10 text-xs rounded-full">
                                    <img role="img" src="/avatars/02.png" class="h-full w-full object-cover" style="">
                                    <!---->
                                </span>
                                <div>
                                    <p class="text-sm font-medium leading-none"> Jackson Lee </p>
                                    <p class="text-sm text-muted-foreground"> p@example.com </p>
                                </div>
                            </div>
                            <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 ml-auto" id="reka-popover-trigger-v-104" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="" data-state="closed">
                                Member 
                                <svg viewBox="0 0 15 15" width="1.2em" height="1.2em" class="ml-2 h-4 w-4 text-muted-foreground">
                                    <path fill="currentColor" fill-rule="evenodd" d="M3.135 6.158a.5.5 0 0 1 .707-.023L7.5 9.565l3.658-3.43a.5.5 0 0 1 .684.73l-4 3.75a.5.5 0 0 1-.684 0l-4-3.75a.5.5 0 0 1-.023-.707" clip-rule="evenodd">

                                    </path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between space-x-4">
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center justify-center font-normal text-foreground select-none shrink-0 bg-secondary overflow-hidden h-10 w-10 text-xs rounded-full">
                                    <img role="img" src="/avatars/03.png" class="h-full w-full object-cover" style="">
                                <!---->
                                </span>
                                <div>
                                    <p class="text-sm font-medium leading-none"> Isabella Nguyen </p>
                                    <p class="text-sm text-muted-foreground"> i@example.com </p>
                                </div>
                            </div>
                            <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 ml-auto" id="reka-popover-trigger-v-105" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="" data-state="closed">
                                Member 
                                <svg viewBox="0 0 15 15" width="1.2em" height="1.2em" class="ml-2 h-4 w-4 text-muted-foreground">
                                    <path fill="currentColor" fill-rule="evenodd" d="M3.135 6.158a.5.5 0 0 1 .707-.023L7.5 9.565l3.658-3.43a.5.5 0 0 1 .684.73l-4 3.75a.5.5 0 0 1-.684 0l-4-3.75a.5.5 0 0 1-.023-.707" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card text-card-foreground shadow">
                    <div class="flex flex-col gap-y-1.5 p-6">
                        <h3 class="font-semibold leading-none tracking-tight">Recent Sales</h3>
                    </div>
                    <div class="p-6 pt-0">
                        <div class="space-y-8">
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center font-normal text-foreground select-none shrink-0 bg-secondary overflow-hidden text-xs rounded-full h-9 w-9">
                                    <img role="img" src="/avatars/01.png" class="h-full w-full object-cover" alt="Avatar" style="">
                                <!---->
                                </span>
                                <div class="ml-4 space-y-1">
                                    <p class="text-sm font-medium leading-none"> Olivia Martin </p>
                                    <p class="text-sm text-muted-foreground"> olivia.martin@email.com </p>
                                </div>
                                <div class="ml-auto font-medium"> +$1,999.00 </div>
                            </div>
                            <div class="flex items-center">
                                <span class="font-normal text-foreground select-none shrink-0 bg-secondary overflow-hidden text-xs rounded-full flex h-9 w-9 items-center justify-center space-y-0 border">
                                    <img role="img" src="/avatars/02.png" class="h-full w-full object-cover" alt="Avatar" style="">
                                <!---->
                                </span>
                                <div class="ml-4 space-y-1">
                                    <p class="text-sm font-medium leading-none"> Jackson Lee </p>
                                    <p class="text-sm text-muted-foreground"> jackson.lee@email.com </p>
                                </div>
                                <div class="ml-auto font-medium"> +$39.00 </div>
                            </div>
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center font-normal text-foreground select-none shrink-0 bg-secondary overflow-hidden text-xs rounded-full h-9 w-9">
                                    <img role="img" src="/avatars/03.png" class="h-full w-full object-cover" alt="Avatar" style="">
                                <!---->
                                </span>
                                <div class="ml-4 space-y-1">
                                    <p class="text-sm font-medium leading-none"> Isabella Nguyen </p>
                                    <p class="text-sm text-muted-foreground"> isabella.nguyen@email.com </p>
                                </div>
                                <div class="ml-auto font-medium"> +$299.00 </div>
                            </div>
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center font-normal text-foreground select-none shrink-0 bg-secondary overflow-hidden text-xs rounded-full h-9 w-9">
                                    <img role="img" src="/avatars/04.png" class="h-full w-full object-cover" alt="Avatar" style="">
                                <!---->
                                </span>
                                <div class="ml-4 space-y-1">
                                    <p class="text-sm font-medium leading-none"> William Kim </p>
                                    <p class="text-sm text-muted-foreground"> will@email.com </p>
                                </div>
                                <div class="ml-auto font-medium"> +$99.00 </div>
                            </div>
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center font-normal text-foreground select-none shrink-0 bg-secondary overflow-hidden text-xs rounded-full h-9 w-9">
                                    <img role="img" src="/avatars/05.png" class="h-full w-full object-cover" alt="Avatar" style="">
                                <!---->
                                </span>
                                <div class="ml-4 space-y-1">
                                    <p class="text-sm font-medium leading-none"> Sofia Davis </p>
                                    <p class="text-sm text-muted-foreground"> sofia.davis@email.com </p>
                                </div>
                                <div class="ml-auto font-medium"> +$39.00 </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <div class="rounded-xl border bg-card text-card-foregorund shadow col-span-4">
                    <div class="flex flex-col gap-y-1.5 p-6">
                        <h3 class="font-semibold leading-none tracking-tight">Overview</h3>
                    </div>
                </div>
                <div class="rounded-xl border bg-card text-card-foregorund shadow col-span-3">
                    <div class="flex flex-col gap-y-1.5 p-6">
                        <h3 class="font-semibold leading-none tracking-tight">Funds Available</h3>
                    </div>
                    <div class="p-6 pt-0">
                        <div class="space-y-8">
                            <Select>
                                <SelectTrigger class="w-[180px]">
                                    <SelectValue placeholder="Selecciona un año"></SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>
                                            2025
                                        </SelectLabel>
                                        <SelectItem value="2024">
                                            2024
                                        </SelectItem>
                                        <SelectItem value="2023">
                                            2023
                                        </SelectItem>
                                        <SelectItem value="2022">
                                            2022
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <ScrollArea class="h-72 rounded-md border">
                                <div class="p-4">
                                    <h4 class="mb-4 text-sm front medium leading-none">
                                        Fondos disponibles
                                    </h4>
                                    <div v-for="tag in tags" :key="tag">
                                      <div class="text-sm">
                                        {{ tag }}
                                      </div>
                                      <Separator class="my-2" />
                                    </div>

                                </div>
                            </ScrollArea>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>
