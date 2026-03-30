<script setup lang="ts">
import * as THREE from 'three';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const host = ref<HTMLElement | null>(null);

let renderer: THREE.WebGLRenderer | null = null;
let scene: THREE.Scene | null = null;
let camera: THREE.PerspectiveCamera | null = null;
let frameId = 0;
let clock: THREE.Clock | null = null;
let visibilityPaused = false;

let networkGroup: THREE.Group | null = null;
let structures: THREE.InstancedMesh | null = null;
let rings: THREE.Group | null = null;

const geometryDisposables: THREE.BufferGeometry[] = [];
const materialDisposables: THREE.Material[] = [];
let mediaQueryList: MediaQueryList | null = null;
let mediaChangeHandler: ((event: MediaQueryListEvent) => void) | null = null;
let visibilityHandler: (() => void) | null = null;

const structurePositions: Array<{ x: number; y: number; z: number; scale: number; phase: number }> = [];

const addDisposableGeometry = <T extends THREE.BufferGeometry>(geometry: T): T => {
    geometryDisposables.push(geometry);
    return geometry;
};

const addDisposableMaterial = <T extends THREE.Material>(material: T): T => {
    materialDisposables.push(material);
    return material;
};

const makeNetwork = (nodeCount: number) => {
    const pointsGeometry = addDisposableGeometry(new THREE.BufferGeometry());
    const positions = new Float32Array(nodeCount * 3);
    const connectionCap = 4;
    const connectionThreshold = 5.4;
    const connectionThresholdSq = connectionThreshold * connectionThreshold;
    const perNodeConnectionCount = new Uint8Array(nodeCount);

    for (let i = 0; i < nodeCount; i += 1) {
        const i3 = i * 3;
        positions[i3] = (Math.random() - 0.5) * 20;
        positions[i3 + 1] = (Math.random() - 0.5) * 12;
        positions[i3 + 2] = (Math.random() - 0.5) * 24;
    }

    const linePositions: number[] = [];

    for (let i = 0; i < nodeCount; i += 1) {
        for (let j = i + 1; j < nodeCount; j += 1) {
            if (perNodeConnectionCount[i] >= connectionCap || perNodeConnectionCount[j] >= connectionCap) {
                continue;
            }

            const i3 = i * 3;
            const j3 = j * 3;
            const dx = positions[i3] - positions[j3];
            const dy = positions[i3 + 1] - positions[j3 + 1];
            const dz = positions[i3 + 2] - positions[j3 + 2];
            const distanceSq = dx * dx + dy * dy + dz * dz;

            if (distanceSq < connectionThresholdSq) {
                perNodeConnectionCount[i] += 1;
                perNodeConnectionCount[j] += 1;
                linePositions.push(
                    positions[i3],
                    positions[i3 + 1],
                    positions[i3 + 2],
                    positions[j3],
                    positions[j3 + 1],
                    positions[j3 + 2],
                );
            }
        }
    }

    pointsGeometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));

    const pointsMaterial = addDisposableMaterial(
        new THREE.PointsMaterial({
            color: 0x68d8ff,
            size: 0.08,
            sizeAttenuation: true,
            transparent: true,
            opacity: 0.9,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        }),
    );

    const points = new THREE.Points(pointsGeometry, pointsMaterial);

    const linesGeometry = addDisposableGeometry(new THREE.BufferGeometry());
    linesGeometry.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));

    const linesMaterial = addDisposableMaterial(
        new THREE.LineBasicMaterial({
            color: 0x2ea8d6,
            transparent: true,
            opacity: 0.34,
            depthWrite: false,
        }),
    );

    const lines = new THREE.LineSegments(linesGeometry, linesMaterial);

    const group = new THREE.Group();
    group.add(points);
    group.add(lines);

    return { group, pointsMaterial, linesMaterial };
};

const makeIndustrialStructures = (count: number) => {
    const boxGeometry = addDisposableGeometry(new THREE.BoxGeometry(0.4, 1, 0.4));
    const boxMaterial = addDisposableMaterial(
        new THREE.MeshStandardMaterial({
            color: 0x255f75,
            roughness: 0.55,
            metalness: 0.4,
            emissive: 0x09232f,
            emissiveIntensity: 0.7,
        }),
    );

    const mesh = new THREE.InstancedMesh(boxGeometry, boxMaterial, count);
    mesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);

    const matrix = new THREE.Matrix4();
    const quaternion = new THREE.Quaternion();

    for (let i = 0; i < count; i += 1) {
        const x = (Math.random() - 0.5) * 22;
        const y = -5 + Math.random() * 5;
        const z = -16 + Math.random() * 24;
        const scale = 0.6 + Math.random() * 2.1;
        const phase = Math.random() * Math.PI * 2;

        structurePositions.push({ x, y, z, scale, phase });

        matrix.compose(
            new THREE.Vector3(x, y, z),
            quaternion,
            new THREE.Vector3(1, scale, 1),
        );
        mesh.setMatrixAt(i, matrix);
    }

    return mesh;
};

const makeRings = () => {
    const group = new THREE.Group();

    const torusA = new THREE.Mesh(
        addDisposableGeometry(new THREE.TorusGeometry(7.5, 0.09, 16, 120)),
        addDisposableMaterial(
            new THREE.MeshBasicMaterial({
                color: 0x87dcff,
                transparent: true,
                opacity: 0.24,
            }),
        ),
    );

    const torusB = new THREE.Mesh(
        addDisposableGeometry(new THREE.TorusGeometry(10, 0.06, 16, 120)),
        addDisposableMaterial(
            new THREE.MeshBasicMaterial({
                color: 0x26a5c7,
                transparent: true,
                opacity: 0.2,
            }),
        ),
    );

    torusA.rotation.x = Math.PI * 0.48;
    torusA.rotation.y = Math.PI * 0.16;
    torusB.rotation.x = Math.PI * 0.52;
    torusB.rotation.z = Math.PI * 0.15;

    group.add(torusA);
    group.add(torusB);

    return group;
};

const onResize = () => {
    if (!renderer || !camera || !host.value) {
        return;
    }

    const width = host.value.clientWidth;
    const height = host.value.clientHeight;

    camera.aspect = width / height;
    camera.updateProjectionMatrix();

    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
};

const animate = () => {
    if (!renderer || !scene || !camera || !clock) {
        return;
    }

    if (visibilityPaused) {
        frameId = window.requestAnimationFrame(animate);
        return;
    }

    const elapsed = clock.getElapsedTime();

    if (networkGroup) {
        networkGroup.rotation.y = elapsed * 0.055;
        networkGroup.rotation.x = Math.sin(elapsed * 0.23) * 0.08;
    }

    if (rings) {
        rings.rotation.z = elapsed * 0.07;
        rings.rotation.x = Math.sin(elapsed * 0.16) * 0.04;
    }

    if (structures) {
        const matrix = new THREE.Matrix4();
        const quaternion = new THREE.Quaternion();

        for (let i = 0; i < structurePositions.length; i += 1) {
            const base = structurePositions[i];
            const pulse = Math.sin(elapsed * 1.4 + base.phase) * 0.25;
            const y = base.y + pulse;
            const yScale = Math.max(0.35, base.scale + pulse * 0.35);

            matrix.compose(
                new THREE.Vector3(base.x, y, base.z),
                quaternion,
                new THREE.Vector3(1, yScale, 1),
            );
            structures.setMatrixAt(i, matrix);
        }

        structures.instanceMatrix.needsUpdate = true;
    }

    renderer.render(scene, camera);
    frameId = window.requestAnimationFrame(animate);
};

onMounted(() => {
    if (!host.value) {
        return;
    }

    scene = new THREE.Scene();
    scene.fog = new THREE.Fog(0x030910, 10, 44);

    camera = new THREE.PerspectiveCamera(58, 1, 0.1, 100);
    camera.position.set(0, 1.6, 19);

    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        powerPreference: 'high-performance',
    });

    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.setClearColor(0x000000, 0);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
    host.value.appendChild(renderer.domElement);

    const ambientLight = new THREE.AmbientLight(0x77b5cb, 0.75);
    const keyLight = new THREE.DirectionalLight(0x9fe7ff, 0.95);
    keyLight.position.set(4, 7, 8);
    const rimLight = new THREE.DirectionalLight(0xff9d57, 0.6);
    rimLight.position.set(-7, -1, 5);

    scene.add(ambientLight, keyLight, rimLight);

    const viewportWidth = host.value.clientWidth;
    const nodeCount = viewportWidth < 640 ? 90 : viewportWidth < 1024 ? 140 : 200;
    const structureCount = viewportWidth < 640 ? 14 : viewportWidth < 1024 ? 24 : 32;

    const network = makeNetwork(nodeCount);
    networkGroup = network.group;
    scene.add(networkGroup);

    structures = makeIndustrialStructures(structureCount);
    scene.add(structures);

    rings = makeRings();
    scene.add(rings);

    clock = new THREE.Clock();
    onResize();

    visibilityHandler = () => {
        visibilityPaused = document.hidden;
    };

    document.addEventListener('visibilitychange', visibilityHandler);
    window.addEventListener('resize', onResize, { passive: true });

    mediaQueryList = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (mediaQueryList.matches) {
        visibilityPaused = true;
    }

    mediaChangeHandler = (event: MediaQueryListEvent) => {
        visibilityPaused = event.matches;
    };

    mediaQueryList.addEventListener('change', mediaChangeHandler);
    frameId = window.requestAnimationFrame(animate);
});

onBeforeUnmount(() => {
    if (mediaQueryList && mediaChangeHandler) {
        mediaQueryList.removeEventListener('change', mediaChangeHandler);
    }

    if (visibilityHandler) {
        document.removeEventListener('visibilitychange', visibilityHandler);
    }

    window.removeEventListener('resize', onResize);

    if (frameId) {
        window.cancelAnimationFrame(frameId);
        frameId = 0;
    }

    geometryDisposables.forEach((geometry) => geometry.dispose());
    materialDisposables.forEach((material) => material.dispose());

    if (renderer) {
        renderer.dispose();

        if (renderer.domElement.parentNode) {
            renderer.domElement.parentNode.removeChild(renderer.domElement);
        }
    }

    structurePositions.length = 0;

    renderer = null;
    scene = null;
    camera = null;
    clock = null;
    networkGroup = null;
    structures = null;
    rings = null;
    mediaQueryList = null;
    mediaChangeHandler = null;
    visibilityHandler = null;
});
</script>

<template>
    <div ref="host" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(40,145,180,0.28),transparent_35%),radial-gradient(circle_at_80%_22%,rgba(255,132,64,0.2),transparent_32%),radial-gradient(circle_at_55%_80%,rgba(58,117,140,0.22),transparent_40%)]"
        />
        <div
            class="absolute inset-0 opacity-30"
            style="background-image: linear-gradient(rgba(143, 192, 214, 0.12) 1px, transparent 1px), linear-gradient(90deg, rgba(143, 192, 214, 0.12) 1px, transparent 1px); background-size: 46px 46px;"
        />
        <div class="absolute inset-0 bg-[linear-gradient(to_bottom,rgba(2,9,16,0.24),rgba(2,9,16,0.84))]" />
    </div>
</template>
