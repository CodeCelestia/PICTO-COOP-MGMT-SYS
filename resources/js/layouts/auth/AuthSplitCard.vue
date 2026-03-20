<script setup lang="ts">
import ThreeBackground from '@/components/ThreeBackground.vue';
import CoopLogo from '@/components/CoopLogo.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#0a0f1e] p-4 md:p-8">
        <!-- Three.js Animated Background -->
        <ThreeBackground />
        
        <!-- Split Card Container -->
        <div class="relative w-full max-w-6xl" style="z-index: 10;">
            <div class="grid overflow-hidden rounded-2xl shadow-2xl md:grid-cols-2 lg:min-h-150">
                <!-- Left Side - Branding -->
                <div class="relative flex flex-col items-center justify-center bg-linear-to-br from-[#4a90e2] to-[#357abd] p-12 text-white">
                    <!-- Decorative circles -->
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white opacity-5"></div>
                        <div class="absolute -bottom-20 -right-20 h-80 w-80 rounded-full bg-white opacity-5"></div>
                        <div class="absolute left-1/2 top-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white opacity-5"></div>
                    </div>
                    
                    <!-- Content -->
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <!-- Logo -->
                        <div class="mb-8">
                            <CoopLogo :size="140" />
                        </div>
                        
                        <!-- Title -->
                        <h1 class="mb-4 text-4xl font-bold tracking-tight lg:text-5xl">
                            COOP SYSTEM
                        </h1>
                        
                        <!-- Subtitle -->
                        <p class="mb-2 text-lg font-medium opacity-90">
                            Management Information System
                        </p>
                        
                        <p class="max-w-md text-sm opacity-75">
                            Provincial ICT Office
                        </p>
                        
                        <!-- Tagline -->
                        <div class="mt-12 max-w-md">
                            <p class="text-sm leading-relaxed opacity-80">
                                Streamlining government operations through modern digital solutions.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Login Form -->
                <div class="flex flex-col justify-center bg-[#1a1f2e] p-8 md:p-12">
                    <div class="mx-auto w-full max-w-md">
                        <slot />
                    </div>
                    
                    <!-- Footer -->
                    <div class="mt-8 text-center">
                        <p class="text-xs text-gray-500">
                            © {{ new Date().getFullYear() }} Provincial ICT Office. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
