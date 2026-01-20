<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { Separator } from '@/components/ui/separator'
import { ScrollArea } from '../components/ui/scroll-area';
import { Card,CardContent,CardDescription,CardFooter,CardTitle} from '@/components/ui/card'

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

</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- elements of the dashboard -->
                <div class="rounded-xl border bg-card text-card-foreground shadow">
                    <div class="gap-y-1 5 p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 class="tracking-tight text-sm font-medium"> Total Revenue </h3>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div class="p-6 pt-0">
                        <div class="text-2xl font-bold"> $45,231.89 </div>
                        <p class="text-xs text-muted-foreground"> +20.1% from last month </p>
                    </div>
                </div>
                <div class="rounded-xl border bg-card text-card-foreground shadow">
                    <div class="gap-y-1.5 p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 class="tracking-tight text-sm font-medium"> Subscriptions </h3>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="p-6 pt-0">
                        <div class="text-2xl font-bold"> +2350 </div>
                        <p class="text-xs text-muted-foreground"> +180.1% from last month </p>
                    </div>
                </div>
                <div class="rounded-xl border bg-card text-card-foreground shadow">
                    <div class="gap-y-1.5 p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 class="tracking-tight text-sm font-medium"> Sales </h3>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground"><rect width="20" height="14" x="2" y="5" rx="2"></rect><path d="M2 10h20"></path></svg>
                    </div>
                    <div class="p-6 pt-0">
                        <div class="text-2xl font-bold"> +12,234 </div>
                        <p class="text-xs text-muted-foreground"> +19% from last month </p>
                    </div>
                </div>
                <div class="rounded-xl border bg-card text-card-foreground shadow">
                    <div class="gap-y-1.5 p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 class="tracking-tight text-sm font-medium"> Active Now </h3>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    </div>
                    <div class="p-6 pt-0">
                        <div class="text-2xl font-bold"> +573 </div>
                        <p class="text-xs text-muted-foreground"> +201 since last hour </p>
                    </div>
                </div>
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
