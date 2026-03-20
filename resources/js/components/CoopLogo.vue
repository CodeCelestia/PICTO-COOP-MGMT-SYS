<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    size?: number;
}>(), {
    size: 120,
});

const svgSize = computed(() => props.size);
</script>

<template>
    <svg 
        :width="svgSize" 
        :height="svgSize" 
        viewBox="0 0 200 200" 
        fill="none" 
        xmlns="http://www.w3.org/2000/svg"
    >
        <!-- Outer Circle -->
        <circle 
            cx="100" 
            cy="100" 
            r="90" 
            stroke="url(#gradient1)" 
            stroke-width="4" 
            fill="none"
            opacity="0.3"
        />
        
        <!-- Inner Circle -->
        <circle 
            cx="100" 
            cy="100" 
            r="75" 
            fill="url(#gradient2)" 
            opacity="0.1"
        />
        
        <!-- Letter C -->
        <path 
            d="M 140 100 A 50 50 0 1 1 100 50" 
            stroke="url(#gradient3)" 
            stroke-width="16" 
            stroke-linecap="round"
            fill="none"
        />
        
        <path 
            d="M 100 150 A 50 50 0 0 1 140 100" 
            stroke="url(#gradient3)" 
            stroke-width="16" 
            stroke-linecap="round"
            fill="none"
        />
        
        <!-- Decorative dots -->
        <circle cx="145" cy="100" r="6" fill="url(#gradient4)" />
        <circle cx="145" cy="100" r="3" fill="white" opacity="0.8" />
        
        <!-- Gradients -->
        <defs>
            <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#4a90e2;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#357abd;stop-opacity:1" />
            </linearGradient>
            
            <linearGradient id="gradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#4a90e2;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#357abd;stop-opacity:1" />
            </linearGradient>
            
            <linearGradient id="gradient3" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#e0e0e0;stop-opacity:1" />
            </linearGradient>
            
            <radialGradient id="gradient4">
                <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#4a90e2;stop-opacity:1" />
            </radialGradient>
        </defs>
    </svg>
</template>
