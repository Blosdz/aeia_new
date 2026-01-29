<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { type NavItem, type SharedData } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutDashboard, BookOpen, Folder, LayoutGrid, UserCircle, ShieldCheck, FileText, CreditCard, FileUp, Zap, TrendingUp, Users, Settings, Handshake, Wallet, Gift } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const menusByRole: Record<string, NavItem[]> = {
    admin: [
        { title: 'Dashboard', href: route('admin.dashboard'), icon: LayoutDashboard },
        { title: 'Usuarios', href: route('admin.users.index'), icon: Users },
        { title: 'Clientes', href: route('admin.clients.index'), icon: Users },
        { title: 'Pagos', href: route('admin.payments.index'), icon: CreditCard },
        { title: 'Fondos', href: route('admin.payments.dashboard'), icon: TrendingUp },
        { title: 'Rewards', href: route('admin.rewards'), icon: Gift },
        { title: 'Reportes', href: '/admin/reports', icon: LayoutGrid },
        { title: 'Configuración', href: '/admin/settings', icon: Settings },
    ],
    client: [
        { title: 'Dashboard', href: route('clients.dashboard'), icon: LayoutDashboard },
        { title: 'Perfil', href: route('clients.profile'), icon: UserCircle },
        { title: 'Pagos', href: route('clients.payments'), icon: CreditCard },
        { title: 'Suscripciones', href: route('clients.subscriptions'), icon: TrendingUp },
        { title: 'Recompensas', href: route('clients.rewards'), icon: Gift },
        { title: 'Coberturas', href: route('clients.coverage'), icon: ShieldCheck },
        { title: 'Documentos', href: route('clients.documents'), icon: FileUp },
    ],
    staff: [
        { title: 'Dashboard', href: route('staff.dashboard'), icon: LayoutDashboard },
        { title: 'Perfil', href: route('staff.profile.edit'), icon: UserCircle },
        { title: 'Comisiones', href: route('staff.commissions'), icon: Wallet },
        { title: 'Referrals', href: route('staff.referrals'), icon: Handshake },
    ],
    support: [
        { title: 'Dashboard', href: route('support.dashboard'), icon: LayoutDashboard },
        { title: 'Validar Usuarios', href: route('support.user-validation.index'), icon: Users },
        { title: 'Validar Pagos', href: route('support.payment-validation.index'), icon: CreditCard },
    ],
    supervisor: [
        { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
        { title: 'Reports', href: '/reports', icon: LayoutGrid },
    ],
    'client_business': [
        { title: 'Dashboard', href: route('clients.dashboard'), icon: LayoutDashboard },
        { title: 'Perfil', href: route('clients.profile'), icon: UserCircle },
        { title: 'Pagos', href: route('clients.payments'), icon: CreditCard },
        { title: 'Suscripciones', href: route('clients.subscriptions'), icon: TrendingUp },
        { title: 'Recompensas', href: route('clients.rewards'), icon: Gift },
        { title: 'Coberturas', href: route('clients.coverage'), icon: ShieldCheck },
        { title: 'Documentos', href: route('clients.documents'), icon: FileUp },
    ],
}



const page = usePage<SharedData>();
const roleNames = Array.isArray(page.props.auth?.roles) ? page.props.auth.roles : [];

const mainNavItems: NavItem[] = roleNames
    .flatMap(role => menusByRole[role] || [])
    .filter((item, index, self) => index === self.findIndex((i) => i.href === item.href));

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="floating">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
