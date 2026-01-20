<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useElementVisibility, useTransition, TransitionPresets } from '@vueuse/core';
import { PieChart, Wallet, CreditCard, TrendingUp, ArrowUpRight, ArrowDownRight, Activity, DollarSign, BarChart3, Menu, X } from 'lucide-vue-next';

const isMobileMenuOpen = ref(false);

// Animated Counter Logic
const counterSection = ref(null);
const isCounterVisible = useElementVisibility(counterSection);
const baseNumber = ref(0);
const displayedNumber = useTransition(baseNumber, {
  duration: 2000,
  transition: TransitionPresets.easeOutExpo,
});

watch(isCounterVisible, (visible) => {
  if (visible) {
    baseNumber.value = 1000;
  } else {
    baseNumber.value = 0;
  }
});

const historyItems = [
    { amount: 300, label: 'Coverage Fund Profit', time: '2 mins ago' },
    { amount: 450, label: 'AI Trade Yield', time: '1 hour ago' },
    { amount: 150, label: 'Daily Dividend', time: '4 hours ago' },
    { amount: 100, label: 'Referral Bonus', time: '5 hours ago' },
];

const bubbles = Array.from({ length: 15 }, (_, i) => ({
    id: i,
    size: 60 + Math.random() * 120, // size in px
    left: Math.random() * 100, // percentage
    delay: Math.random() * 10, // seconds
    duration: 10 + Math.random() * 15, // seconds
}));
</script>

<template>
    <Head title="Welcome to AEIA" />
    
    <div class="h-screen w-full overflow-y-scroll snap-y snap-mandatory scroll-smooth bg-slate-950 text-white">
        <!-- Navigation -->
        <nav class="fixed top-0 left-0 w-full z-50 transition-all duration-300" :class="{ 'bg-slate-950/90 backdrop-blur-md border-b border-white/5': isMobileMenuOpen || true }">
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                <!-- Logo -->
                <div class="text-2xl font-black tracking-tighter bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent z-50 relative">AEIA</div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#" class="text-slate-400 hover:text-white transition-colors text-sm font-medium">Features</a>
                    <a href="#" class="text-slate-400 hover:text-white transition-colors text-sm font-medium">Pricing</a>
                    <a href="#" class="text-slate-400 hover:text-white transition-colors text-sm font-medium">About</a>
                </div>

                <!-- Desktop Actions -->
                <div class="hidden md:flex items-center gap-4">
                    <Link :href="route('login')" class="text-slate-300 hover:text-white transition-colors text-sm font-medium">Log in</Link>
                    <Link :href="route('register')" class="bg-white text-black px-5 py-2 rounded-full text-sm font-bold hover:bg-blue-50 transition-colors">Get Started</Link>
                </div>

                <!-- Mobile Toggle -->
                <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="md:hidden text-white z-50 relative focus:outline-none p-2">
                    <component :is="isMobileMenuOpen ? X : Menu" />
                </button>
            </div>
        </nav>

        <!-- Mobile Menu Overlay -->
        <div 
            class="fixed inset-0 bg-slate-950/95 backdrop-blur-xl z-40 flex flex-col justify-center items-center gap-8 transition-transform duration-300 ease-in-out md:hidden"
            :class="isMobileMenuOpen ? 'translate-x-0' : 'translate-x-full'"
        >
            <a href="#" class="text-3xl font-bold text-slate-300 hover:text-white" @click="isMobileMenuOpen = false">Features</a>
            <a href="#" class="text-3xl font-bold text-slate-300 hover:text-white" @click="isMobileMenuOpen = false">Pricing</a>
            <a href="#" class="text-3xl font-bold text-slate-300 hover:text-white" @click="isMobileMenuOpen = false">About</a>
            <div class="h-px w-24 bg-slate-800 my-4"></div>
            <Link :href="route('login')" class="text-2xl font-medium text-slate-300">Log in</Link>
            <Link :href="route('register')" class="px-8 py-3 bg-blue-600 rounded-full text-xl font-bold text-white shadow-lg shadow-blue-900/50">Get Started</Link>
        </div>

        <!-- Section 1: Hero -->
        <section class="min-h-screen w-full snap-start relative flex flex-col items-center justify-center overflow-hidden bg-slate-950">
            <!-- Lava Bubbles Animation -->
            <div class="absolute inset-0 w-full h-full pointer-events-none overflow-hidden select-none">
                 <div v-for="bubble in bubbles" :key="bubble.id"
                    class="absolute rounded-full blur-3xl animate-rise"
                    :style="{
                        width: `${bubble.size}px`,
                        height: `${bubble.size}px`,
                        left: `${bubble.left}%`,
                        bottom: `-${bubble.size + 100}px`,
                        backgroundColor: bubble.id % 2 === 0 ? 'rgba(37, 99, 235, 0.4)' : 'rgba(99, 102, 241, 0.4)',
                        animationDelay: `-${bubble.delay}s`,
                        animationDuration: `${bubble.duration}s`
                    }"
                 ></div>
            </div>
            
            <div class="z-10 text-center max-w-4xl px-4 animate-fade-in-up">
                <h1 class="text-6xl md:text-8xl font-black tracking-tight mb-6 bg-gradient-to-br from-white to-slate-500 bg-clip-text text-transparent">
                    Future Finance.
                </h1>
                <p class="text-xl md:text-2xl text-slate-400 mb-8 max-w-2xl mx-auto">
                    Experience the next generation of financial intelligence powered by AEIA.
                </p>
                <Link :href="route('register')" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white transition-all duration-200 bg-blue-600 rounded-full hover:bg-blue-700 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 focus:ring-offset-slate-900 shadow-[0_0_20px_rgba(37,99,235,0.5)]">
                    Get Started
                </Link>
            </div>
        </section>

        <!-- Section 2: Efficiency -->
        <section class="min-h-screen w-full snap-start flex flex-col items-center justify-center px-6 relative bg-slate-950/50">
             <div class="max-w-6xl w-full">
                <span class="text-blue-500 font-medium tracking-widest uppercase mb-4 block">Efficiency at its best</span>
                <h2 class="text-6xl md:text-9xl font-black tracking-tighter mb-8 leading-none">
                    AEIA DAILY<br />FINANCE
                </h2>
                <p class="text-slate-500 text-xl md:text-3xl font-light max-w-3xl leading-relaxed">
                    Empowering you with global AI finance tools designed to maximize your portfolio's potential through automated precision.
                </p>
            </div>
        </section>

        <!-- Section 3: Analytics, Budgeting, Accounts -->
        <section class="min-h-screen w-full snap-start flex items-center justify-center px-6 py-24 bg-slate-900" ref="counterSection">
            <div class="max-w-7xl w-full grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Card 1: Analytics -->
                <div class="bg-gray-800/50 backdrop-blur-sm p-8 rounded-3xl border border-gray-700 hover:border-blue-500/50 transition-all hover:-translate-y-2 group">
                    <div class="flex items-center justify-between mb-8">
                        <div class="bg-blue-500/10 p-3 rounded-2xl text-blue-400 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                            <PieChart :size="32" stroke-width="1.5" />
                        </div>
                        <span class="text-green-400 flex items-center gap-1 bg-green-400/10 px-3 py-1 rounded-full text-sm font-medium">
                            <TrendingUp :size="16" /> +12.5%
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Analytics</h3>
                    <p class="text-gray-400 mb-6 font-light">Real-time market analysis and portfolio performance tracking.</p>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 text-sm">Monthly Volume</span>
                            <span class="text-white font-mono font-medium">$14,250.00</span>
                        </div>
                        <div class="w-full bg-gray-700/50 rounded-full h-2 overflow-hidden">
                            <div class="bg-blue-500 h-full rounded-full w-3/4 animate-pulse"></div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Budgeting -->
                <div class="bg-gray-800/50 backdrop-blur-sm p-8 rounded-3xl border border-gray-700 hover:border-purple-500/50 transition-all hover:-translate-y-2 group">
                    <div class="flex items-center justify-between mb-8">
                         <div class="bg-purple-500/10 p-3 rounded-2xl text-purple-400 group-hover:bg-purple-500 group-hover:text-white transition-colors">
                            <Wallet :size="32" stroke-width="1.5" />
                        </div>
                        <span class="text-purple-300 text-sm font-medium bg-purple-500/10 px-3 py-1 rounded-full">Monthly</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Budgeting</h3>
                    <p class="text-gray-400 mb-6 font-light">Smart allocation for coverage funds and operational costs.</p>

                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-400">Marketing</span>
                                <span class="text-white font-mono">$2,400 / $5,000</span>
                            </div>
                             <div class="w-full bg-gray-700/50 rounded-full h-2">
                                <div class="bg-purple-500 h-full rounded-full w-1/2"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-400">Operations</span>
                                <span class="text-white font-mono">$8,500 / $10,000</span>
                            </div>
                             <div class="w-full bg-gray-700/50 rounded-full h-2">
                                <div class="bg-pink-500 h-full rounded-full w-4/5"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Accounts -->
                <div class="bg-gray-800/50 backdrop-blur-sm p-8 rounded-3xl border border-gray-700 hover:border-emerald-500/50 transition-all hover:-translate-y-2 group">
                    <div class="flex items-center justify-between mb-8">
                         <div class="bg-emerald-500/10 p-3 rounded-2xl text-emerald-400 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                            <CreditCard :size="32" stroke-width="1.5" />
                        </div>
                         <div class="flex -space-x-2">
                            <div class="w-8 h-8 rounded-full bg-slate-600 border border-gray-800"></div>
                            <div class="w-8 h-8 rounded-full bg-slate-500 border border-gray-800"></div>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Accounts</h3>
                    <p class="text-gray-400 mb-6 font-light">Manage your checking, savings, and investment accounts.</p>

                    <div class="space-y-4">
                         <div class="bg-gray-900/50 p-4 rounded-xl border border-gray-800 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-white/10 p-2 rounded-lg"><DollarSign :size="16" class="text-emerald-400" /></div>
                                <div>
                                    <div class="text-xs text-gray-500">Main Balance</div>
                                    <div class="font-mono font-bold text-white text-lg">${{ Math.round(displayedNumber).toLocaleString() }}</div>
                                </div>
                            </div>
                             <ArrowUpRight :size="16" class="text-emerald-500" />
                        </div>
                         <div class="bg-gray-900/50 p-4 rounded-xl border border-gray-800 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-white/10 p-2 rounded-lg"><Activity :size="16" class="text-blue-400" /></div>
                                <div>
                                    <div class="text-xs text-gray-500">Yield</div>
                                    <div class="font-mono font-bold text-white text-lg">+ $450.20</div>
                                </div>
                            </div>
                             <ArrowUpRight :size="16" class="text-blue-500" />
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Section 4: Final CTA / Footer -->
        <section class="min-h-screen w-full snap-start flex flex-col items-center justify-center relative overflow-hidden bg-slate-950">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-blue-900/20 via-slate-950 to-slate-950"></div>
            
            <div class="z-10 text-center max-w-3xl px-6">
                <h2 class="text-4xl md:text-6xl font-bold mb-8">Ready to start your journey?</h2>
                <div class="flex flex-col md:flex-row gap-4 justify-center">
                    <Link :href="route('register')" class="px-8 py-4 rounded-lg bg-blue-600 text-white font-bold text-lg hover:bg-blue-500 transition-all shadow-lg shadow-blue-900/50">
                        Create Free Account
                    </Link>
                    <Link :href="route('login')" class="px-8 py-4 rounded-lg border border-slate-700 text-white font-bold text-lg hover:bg-slate-800 transition-all">
                        Contact Sales
                    </Link>
                </div>
                
                <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-8 text-center text-slate-500 text-sm">
                    <div class="flex flex-col">
                        <span class="font-bold text-white text-lg mb-1">24/7</span>
                        <span>Support</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-white text-lg mb-1">100%</span>
                        <span>Secure</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-white text-lg mb-1">Global</span>
                        <span>Coverage</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-white text-lg mb-1">0%</span>
                        <span>Hidden Fees</span>
                    </div>
                </div>
            </div>

            <footer class="absolute bottom-6 w-full text-center text-slate-600 text-sm">
                &copy; {{ new Date().getFullYear() }} AEIA Inc. All rights reserved.
            </footer>
        </section>
    </div>
</template>

<style scoped>
@keyframes rise {
    0% {
        transform: translateY(0) translateX(0);
        opacity: 0;
    }
    10% {
        opacity: 0.6;
    }
    50% {
        transform: translateY(-50vh) translateX(20px);
        opacity: 0.6;
    }
    100% {
        transform: translateY(-120vh) translateX(-20px);
        opacity: 0;
    }
}
.animate-rise {
    animation-name: rise;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
}

/* Custom keyframes if needed, but Tailwind utilities are mostly sufficient */
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-up {
    animation: fade-in-up 1s ease-out forwards;
}
</style>
