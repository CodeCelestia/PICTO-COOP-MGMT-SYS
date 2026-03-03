<script setup lang="ts">
import AuthTechBackground from '@/components/auth/AuthTechBackground.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative min-h-svh overflow-hidden bg-slate-950 text-slate-100">
        <AuthTechBackground />

        <div
            class="absolute inset-0 bg-[radial-gradient(80%_70%_at_10%_20%,rgba(59,130,246,0.24),transparent_60%),radial-gradient(60%_50%_at_90%_80%,rgba(14,165,233,0.18),transparent_70%)]"
        />
        <div
            class="absolute inset-0 bg-gradient-to-br from-slate-950/95 via-blue-950/75 to-slate-900/95"
        />

        <div class="relative z-10 flex min-h-svh items-center justify-center p-4 md:p-6">
            <!-- Single Centered Card -->
            <div
                class="w-full max-w-5xl overflow-hidden rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.5)]"
            >
                <div class="grid lg:grid-cols-2">
                    <!-- Left Side - Dark Branding -->
                    <div class="hidden lg:flex flex-col items-center justify-center p-12 bg-gradient-to-br from-slate-900/95 to-slate-800/95 backdrop-blur-xl relative overflow-hidden">
                        <!-- Decorative elements -->
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(59,130,246,0.1),transparent_50%)]"></div>
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_80%,rgba(99,102,241,0.08),transparent_50%)]"></div>
                        
                        <div class="relative space-y-10 text-center">
                            <!-- Logo Section -->
                            <div class="flex items-center justify-center gap-8">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-xl shadow-blue-500/30">
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <span class="mt-3 text-xs font-medium text-slate-300 uppercase tracking-wider">Province</span>
                                </div>
                                
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-xl shadow-indigo-500/30">
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <span class="mt-3 text-xs font-medium text-slate-300 uppercase tracking-wider">Coop</span>
                                </div>
                            </div>
                            
                            <!-- COOP MIS Text -->
                            <div class="space-y-4">
                                <h1 class="text-4xl font-bold text-white tracking-tight">
                                    Manage your<br/>cooperative
                                </h1>
                                <p class="text-sm text-slate-400 max-w-xs mx-auto leading-relaxed">
                                    Cooperative Management Information System
                                </p>
                            </div>

                            <!-- Footer text -->
                            <div class="pt-8">
                                <p class="text-xs text-slate-500">
                                    Secure • Reliable • Modern
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - White Login Form -->
                    <div class="bg-white p-10 lg:p-12 flex flex-col justify-center">
                        <div class="max-w-md mx-auto w-full">
                            <div class="mb-10 space-y-3">
                                <h2 class="text-3xl font-bold text-slate-900 tracking-tight">
                                    {{ title }}
                                </h2>
                                <p class="text-sm text-slate-500" v-if="description">
                                    {{ description }}
                                </p>
                            </div>

                            <slot />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
