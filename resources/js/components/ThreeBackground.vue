<script setup lang="ts">
import * as THREE from 'three';
import { onMounted, onUnmounted, ref } from 'vue';

const canvasRef = ref<HTMLCanvasElement | null>(null);
let scene: THREE.Scene;
let camera: THREE.PerspectiveCamera;
let renderer: THREE.WebGLRenderer;
let particles: THREE.Points;
let animationFrameId: number;
let particlesGeometry: THREE.BufferGeometry;
let originalPositions: Float32Array;

// Mouse tracking
const mouse = {
    x: 0,
    y: 0,
    targetX: 0,
    targetY: 0
};

const initThree = () => {
    if (!canvasRef.value) return;

    // Scene setup
    scene = new THREE.Scene();
    
    // Camera setup
    camera = new THREE.PerspectiveCamera(
        75,
        window.innerWidth / window.innerHeight,
        0.1,
        1000
    );
    camera.position.z = 50;

    // Renderer setup
    renderer = new THREE.WebGLRenderer({
        canvas: canvasRef.value,
        alpha: true,
        antialias: true,
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(window.devicePixelRatio);

    // Create particles
    particlesGeometry = new THREE.BufferGeometry();
    const particlesCount = 5000;
    const posArray = new Float32Array(particlesCount * 3);
    const velocities = new Float32Array(particlesCount * 3);

    for (let i = 0; i < particlesCount * 3; i += 3) {
        posArray[i] = (Math.random() - 0.5) * 200;
        posArray[i + 1] = (Math.random() - 0.5) * 200;
        posArray[i + 2] = (Math.random() - 0.5) * 200;
        
        velocities[i] = (Math.random() - 0.5) * 0.02;
        velocities[i + 1] = (Math.random() - 0.5) * 0.02;
        velocities[i + 2] = (Math.random() - 0.5) * 0.02;
    }

    // Store original positions for mouse interaction
    originalPositions = new Float32Array(posArray);

    particlesGeometry.setAttribute(
        'position',
        new THREE.BufferAttribute(posArray, 3)
    );

    particlesGeometry.setAttribute(
        'velocity',
        new THREE.BufferAttribute(velocities, 3)
    );

    // Particle material with size variation
    const particlesMaterial = new THREE.PointsMaterial({
        size: 1.2,
        color: 0x4a90e2,
        transparent: true,
        opacity: 0.8,
        blending: THREE.AdditiveBlending,
        sizeAttenuation: true,
    });

    particles = new THREE.Points(particlesGeometry, particlesMaterial);
    scene.add(particles);

    // Add point lights for depth
    const pointLight1 = new THREE.PointLight(0x4a90e2, 1, 100);
    pointLight1.position.set(50, 50, 50);
    scene.add(pointLight1);

    const pointLight2 = new THREE.PointLight(0x357abd, 1, 100);
    pointLight2.position.set(-50, -50, 50);
    scene.add(pointLight2);

    // Mouse move handler
    const handleMouseMove = (event: MouseEvent) => {
        mouse.targetX = (event.clientX / window.innerWidth) * 2 - 1;
        mouse.targetY = -(event.clientY / window.innerHeight) * 2 + 1;
    };

    window.addEventListener('mousemove', handleMouseMove);

    // Animation loop
    const animate = () => {
        animationFrameId = requestAnimationFrame(animate);

        // Smooth mouse following
        mouse.x += (mouse.targetX - mouse.x) * 0.05;
        mouse.y += (mouse.targetY - mouse.y) * 0.05;

        // Rotate particles based on mouse
        particles.rotation.x += 0.0003 + mouse.y * 0.0001;
        particles.rotation.y += 0.0005 + mouse.x * 0.0001;

        const time = Date.now() * 0.001;
        const positions = particlesGeometry.attributes.position.array as Float32Array;
        const velocities = particlesGeometry.attributes.velocity.array as Float32Array;
        
        // Update particle positions with mouse interaction
        for (let i = 0; i < positions.length; i += 3) {
            const x = positions[i];
            const y = positions[i + 1];
            const z = positions[i + 2];
            
            // Original position for reference
            const origX = originalPositions[i];
            const origY = originalPositions[i + 1];
            const origZ = originalPositions[i + 2];
            
            // Mouse influence on particles
            const mouseInfluenceX = mouse.x * 10;
            const mouseInfluenceY = mouse.y * 10;
            
            // Distance from particle to mouse position (in 2D space)
            const dx = x - mouseInfluenceX;
            const dy = y - mouseInfluenceY;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            // Repulsion effect when mouse is near
            let repulsionX = 0;
            let repulsionY = 0;
            
            if (distance < 30) {
                const force = (30 - distance) / 30;
                repulsionX = (dx / distance) * force * 2;
                repulsionY = (dy / distance) * force * 2;
            }
            
            // Wave motion
            const wave = Math.sin(time + origX * 0.02 + origZ * 0.02) * 0.5;
            
            // Update positions with all effects combined
            positions[i] = origX + Math.sin(time + i) * 0.3 + velocities[i] * time + repulsionX;
            positions[i + 1] = origY + wave + velocities[i + 1] * time + repulsionY;
            positions[i + 2] = origZ + Math.cos(time + i) * 0.3 + velocities[i + 2] * time;
            
            // Boundary wrapping
            if (positions[i] > 100) positions[i] = -100;
            if (positions[i] < -100) positions[i] = 100;
            if (positions[i + 1] > 100) positions[i + 1] = -100;
            if (positions[i + 1] < -100) positions[i + 1] = 100;
            if (positions[i + 2] > 100) positions[i + 2] = -100;
            if (positions[i + 2] < -100) positions[i + 2] = 100;
        }
        
        particlesGeometry.attributes.position.needsUpdate = true;

        // Camera subtle movement based on mouse
        camera.position.x = mouse.x * 5;
        camera.position.y = mouse.y * 5;
        camera.lookAt(scene.position);

        renderer.render(scene, camera);
    };

    animate();

    // Handle window resize
    const handleResize = () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    };

    window.addEventListener('resize', handleResize);
};

onMounted(() => {
    initThree();
});

onUnmounted(() => {
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }
    if (renderer) {
        renderer.dispose();
    }
    if (particlesGeometry) {
        particlesGeometry.dispose();
    }
});
</script>

<template>
    <canvas
        ref="canvasRef"
        class="fixed inset-0"
        style="z-index: 0; pointer-events: none;"
    />
</template>
