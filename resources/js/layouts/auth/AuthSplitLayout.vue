<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Bitcoin, DollarSign, Euro, PoundSterling, JapaneseYen, Wallet, CreditCard, Gem, CircleDollarSign } from 'lucide-vue-next';

const page = usePage();
const name = page.props.name;
const quote = page.props.quote;

defineProps<{
    title?: string;
    description?: string;
}>();

// Animation Logic
const icons = [
    { component: Bitcoin, color: 'text-orange-500' },
    { component: DollarSign, color: 'text-green-500' },
    { component: Euro, color: 'text-blue-500' },
    { component: PoundSterling, color: 'text-purple-500' },
    { component: JapaneseYen, color: 'text-red-500' },
    { component: Gem, color: 'text-pink-500' },
    { component: Wallet, color: 'text-yellow-500' },
    { component: CircleDollarSign, color: 'text-emerald-500' },
];

const angle = ref(0);
let animationFrameId: number;

const radiusX = 250; 
const radiusZ = 100; // Ellipse depth factor

const nodes = computed(() => {
    return icons.map((icon, index) => {
        const theta = angle.value + (index * (2 * Math.PI / icons.length));
        
        // 3D coordinates
        const x = radiusX * Math.cos(theta);
        const z = radiusZ * Math.sin(theta);
        
        // Perspective projection
        const perspective = 800;
        const scale = perspective / (perspective - z);
        
        return {
            ...icon,
            x: x, 
            y: (z * 0.45) - (x * 0.20), // Diagonal tilt
            z: z,
            scale: scale,
            opacity: mapRange(z, -radiusZ, radiusZ, 0.4, 1),
            zIndex: Math.round(z + radiusZ), 
        };
    });
});

function mapRange(value: number, inMin: number, inMax: number, outMin: number, outMax: number) {
  return ((value - inMin) * (outMax - outMin)) / (inMax - inMin) + outMin;
}

const animate = () => {
    angle.value += 0.005; // Speed
    animationFrameId = requestAnimationFrame(animate);
};

onMounted(() => {
    animate();
});

onUnmounted(() => {
    cancelAnimationFrame(animationFrameId);
});
</script>

<template>
    <div class="grid min-h-screen w-full lg:grid-cols-2">
        <div class="relative hidden h-full flex-col p-10 text-white lg:flex overflow-hidden">
             <div class="absolute inset-0 bg-slate-950">
                <div class="absolute top-[-20%] left-[-20%] w-[50vw] h-[50vw] bg-blue-600/20 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-[-20%] right-[-20%] w-[50vw] h-[50vw] bg-indigo-600/10 rounded-full blur-[100px] pointer-events-none"></div>

                <!-- Orbit Animation Container -->
                <div class="absolute inset-0 flex items-center justify-center perspective-[1000px]">
                     <div class="relative w-full h-full flex items-center justify-center" style="transform-style: preserve-3d;">
                        <!-- Icons -->
                        <div 
                            v-for="(node, index) in nodes" 
                            :key="index"
                            class="absolute flex items-center justify-center rounded-full bg-black border border-slate-800 backdrop-blur-md shadow-2xl transition-transform will-change-transform"
                            :class="node.color"
                            :style="{
                                transform: `translate3d(${node.x}px, ${node.y}px, 0) scale(${node.scale})`,
                                zIndex: node.zIndex,
                                opacity: node.opacity,
                                width: '64px',
                                height: '64px'
                            }"
                        >
                            <component :is="node.component" :size="32" stroke-width="1.5" />
                        </div>
                    </div>
                </div>
            </div>
            
            <Link :href="route('home')" class="relative z-20 flex items-center text-lg font-medium">
                <span class="text-2xl font-black tracking-tighter bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">AEIA</span>
            </Link>
            <div v-if="quote" class="relative z-20 mt-auto">
                <blockquote class="space-y-2">
                    <p class="text-lg">&ldquo;{{ quote.message }}&rdquo;</p>
                    <footer class="text-sm text-blue-200">{{ quote.author }}</footer>
                </blockquote>
            </div>
        </div>
        <div class="flex h-full items-center justify-center bg-slate-50 p-6 lg:p-8 dark:bg-slate-900">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <!-- Mobile Logo -->
                <div class="flex justify-center mb-4 lg:hidden">
                    <Link :href="route('home')" class="text-3xl font-black tracking-tighter bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">AEIA</Link>
                </div>

                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-2xl font-semibold tracking-tight" v-if="title">{{ title }}</h1>
                    <p class="text-sm text-muted-foreground" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
