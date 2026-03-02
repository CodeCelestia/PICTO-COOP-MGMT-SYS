<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import * as THREE from 'three';

const containerRef = ref<HTMLElement | null>(null);

let renderer: THREE.WebGLRenderer | null = null;
let scene: THREE.Scene | null = null;
let camera: THREE.PerspectiveCamera | null = null;
let frameId: number | null = null;
let resizeHandler: (() => void) | null = null;

onMounted(() => {
    if (!containerRef.value) {
        return;
    }

    const container = containerRef.value;

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(
        55,
        container.clientWidth / Math.max(container.clientHeight, 1),
        0.1,
        100,
    );
    camera.position.set(0, 0.2, 7);

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.6));
    renderer.setSize(container.clientWidth, container.clientHeight);
    container.appendChild(renderer.domElement);

    const ambientLight = new THREE.AmbientLight('#93c5fd', 0.8);
    const keyLight = new THREE.PointLight('#38bdf8', 2.8, 18);
    keyLight.position.set(3.2, 2.2, 4.4);

    const rimLight = new THREE.PointLight('#1d4ed8', 2.2, 20);
    rimLight.position.set(-4.5, -2, -2);

    scene.add(ambientLight, keyLight, rimLight);

    const mainGeometry = new THREE.IcosahedronGeometry(1.7, 1);
    const mainMaterial = new THREE.MeshStandardMaterial({
        color: '#2563eb',
        wireframe: true,
        transparent: true,
        opacity: 0.42,
        emissive: '#0f172a',
        emissiveIntensity: 0.55,
    });
    const mainMesh = new THREE.Mesh(mainGeometry, mainMaterial);
    mainMesh.position.set(-1.8, 0.1, -0.2);
    scene.add(mainMesh);

    const supportGeometry = new THREE.TorusKnotGeometry(0.85, 0.2, 150, 26);
    const supportMaterial = new THREE.MeshStandardMaterial({
        color: '#60a5fa',
        wireframe: true,
        transparent: true,
        opacity: 0.35,
    });
    const supportMesh = new THREE.Mesh(supportGeometry, supportMaterial);
    supportMesh.position.set(2.3, -0.5, -0.8);
    scene.add(supportMesh);

    const particlesGeometry = new THREE.BufferGeometry();
    const particleCount = 900;
    const positions = new Float32Array(particleCount * 3);

    for (let index = 0; index < particleCount; index += 1) {
        const radius = 2.8 + Math.random() * 3.5;
        const theta = Math.random() * Math.PI * 2;
        const phi = Math.acos(2 * Math.random() - 1);

        positions[index * 3] = radius * Math.sin(phi) * Math.cos(theta);
        positions[index * 3 + 1] = radius * Math.cos(phi) * 0.7;
        positions[index * 3 + 2] = radius * Math.sin(phi) * Math.sin(theta);
    }

    particlesGeometry.setAttribute(
        'position',
        new THREE.BufferAttribute(positions, 3),
    );

    const particlesMaterial = new THREE.PointsMaterial({
        color: '#93c5fd',
        size: 0.03,
        transparent: true,
        opacity: 0.75,
        sizeAttenuation: true,
    });

    const particles = new THREE.Points(particlesGeometry, particlesMaterial);
    scene.add(particles);

    const clock = new THREE.Clock();

    const animate = () => {
        if (!renderer || !scene || !camera) {
            return;
        }

        const elapsed = clock.getElapsedTime();

        mainMesh.rotation.x = elapsed * 0.13;
        mainMesh.rotation.y = elapsed * 0.2;

        supportMesh.rotation.x = -elapsed * 0.16;
        supportMesh.rotation.y = elapsed * 0.11;

        particles.rotation.y = elapsed * 0.025;
        particles.rotation.x = Math.sin(elapsed * 0.15) * 0.06;

        camera.position.x = Math.sin(elapsed * 0.22) * 0.24;
        camera.position.y = Math.cos(elapsed * 0.18) * 0.18;
        camera.lookAt(0, 0, 0);

        renderer.render(scene, camera);
        frameId = window.requestAnimationFrame(animate);
    };

    animate();

    resizeHandler = () => {
        if (!renderer || !camera || !containerRef.value) {
            return;
        }

        const { clientWidth, clientHeight } = containerRef.value;
        const safeHeight = Math.max(clientHeight, 1);

        camera.aspect = clientWidth / safeHeight;
        camera.updateProjectionMatrix();

        renderer.setSize(clientWidth, safeHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.6));
    };

    window.addEventListener('resize', resizeHandler);
});

onBeforeUnmount(() => {
    if (resizeHandler) {
        window.removeEventListener('resize', resizeHandler);
    }

    if (frameId !== null) {
        window.cancelAnimationFrame(frameId);
    }

    if (renderer) {
        renderer.dispose();

        if (renderer.domElement.parentNode) {
            renderer.domElement.parentNode.removeChild(renderer.domElement);
        }
    }

    scene?.traverse((object: THREE.Object3D) => {
        const mesh = object as THREE.Mesh;

        if (mesh.geometry) {
            mesh.geometry.dispose();
        }

        if (Array.isArray(mesh.material)) {
            mesh.material.forEach((material: THREE.Material) => {
                material.dispose();
            });
        } else if (mesh.material) {
            mesh.material.dispose();
        }
    });

    renderer = null;
    scene = null;
    camera = null;
    frameId = null;
    resizeHandler = null;
});
</script>

<template>
    <div ref="containerRef" class="absolute inset-0" />
</template>
