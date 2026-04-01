<script setup lang="ts">
import * as THREE from 'three';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const host = ref<HTMLElement | null>(null);
type Runtime = {
    host: HTMLElement | null;
    renderer: THREE.WebGLRenderer | null;
    scene: THREE.Scene | null;
    camera: THREE.PerspectiveCamera | null;
    frameId: number;
    clock: THREE.Clock | null;
    visibilityPaused: boolean;
    networkGroup: THREE.Group | null;
    structures: THREE.InstancedMesh | null;
    rings: THREE.Group | null;
    geometryDisposables: THREE.BufferGeometry[];
    materialDisposables: THREE.Material[];
    mediaQueryList: MediaQueryList | null;
    mediaChangeHandler: ((event: MediaQueryListEvent) => void) | null;
    visibilityHandler: (() => void) | null;
    resizeHandler: (() => void) | null;
    structurePositions: Array<{ x: number; y: number; z: number; scale: number; phase: number }>;
    initialized: boolean;
};

type GlobalRuntime = typeof globalThis & {
    __landingBackgroundRuntime__?: Runtime;
    __landingBackgroundCleanup__?: boolean;
};

const globalRuntime = globalThis as GlobalRuntime;

const createRuntime = (): Runtime => ({
    host: null,
    renderer: null,
    scene: null,
    camera: null,
    frameId: 0,
    clock: null,
    visibilityPaused: false,
    networkGroup: null,
    structures: null,
    rings: null,
    geometryDisposables: [],
    materialDisposables: [],
    mediaQueryList: null,
    mediaChangeHandler: null,
    visibilityHandler: null,
    resizeHandler: null,
    structurePositions: [],
    initialized: false,
});

const getRuntime = () => {
    if (!globalRuntime.__landingBackgroundRuntime__) {
        globalRuntime.__landingBackgroundRuntime__ = createRuntime();
    }

    return globalRuntime.__landingBackgroundRuntime__;
};

const addDisposableGeometry = <T extends THREE.BufferGeometry>(runtime: Runtime, geometry: T): T => {
    runtime.geometryDisposables.push(geometry);
    return geometry;
};

const addDisposableMaterial = <T extends THREE.Material>(runtime: Runtime, material: T): T => {
    runtime.materialDisposables.push(material);
    return material;
};

const makeNetwork = (runtime: Runtime, nodeCount: number) => {
    const pointsGeometry = addDisposableGeometry(runtime, new THREE.BufferGeometry());
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
        runtime,
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

    const linesGeometry = addDisposableGeometry(runtime, new THREE.BufferGeometry());
    linesGeometry.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));

    const linesMaterial = addDisposableMaterial(
        runtime,
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

const makeIndustrialStructures = (runtime: Runtime, count: number) => {
    const boxGeometry = addDisposableGeometry(runtime, new THREE.BoxGeometry(0.4, 1, 0.4));
    const boxMaterial = addDisposableMaterial(
        runtime,
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

        runtime.structurePositions.push({ x, y, z, scale, phase });

        matrix.compose(
            new THREE.Vector3(x, y, z),
            quaternion,
            new THREE.Vector3(1, scale, 1),
        );
        mesh.setMatrixAt(i, matrix);
    }

    return mesh;
};

const makeRings = (runtime: Runtime) => {
    const group = new THREE.Group();

    const torusA = new THREE.Mesh(
        addDisposableGeometry(runtime, new THREE.TorusGeometry(7.5, 0.09, 16, 120)),
        addDisposableMaterial(
            runtime,
            new THREE.MeshBasicMaterial({
                color: 0x87dcff,
                transparent: true,
                opacity: 0.24,
            }),
        ),
    );

    const torusB = new THREE.Mesh(
        addDisposableGeometry(runtime, new THREE.TorusGeometry(10, 0.06, 16, 120)),
        addDisposableMaterial(
            runtime,
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
    const runtime = getRuntime();
    if (!runtime.renderer || !runtime.camera) {
        return;
    }

    const width = runtime.host?.clientWidth ?? window.innerWidth;
    const height = runtime.host?.clientHeight ?? window.innerHeight;

    runtime.camera.aspect = width / height;
    runtime.camera.updateProjectionMatrix();

    runtime.renderer.setSize(width, height);
    runtime.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
};

const animate = () => {
    const runtime = getRuntime();
    if (!runtime.renderer || !runtime.scene || !runtime.camera || !runtime.clock) {
        return;
    }

    runtime.frameId = window.requestAnimationFrame(animate);

    if (runtime.visibilityPaused) {
        return;
    }

    if (!runtime.host) {
        return;
    }

    const elapsed = runtime.clock.getElapsedTime();

    if (runtime.networkGroup) {
        runtime.networkGroup.rotation.y = elapsed * 0.055;
        runtime.networkGroup.rotation.x = Math.sin(elapsed * 0.23) * 0.08;
    }

    if (runtime.rings) {
        runtime.rings.rotation.z = elapsed * 0.07;
        runtime.rings.rotation.x = Math.sin(elapsed * 0.16) * 0.04;
    }

    if (runtime.structures) {
        const matrix = new THREE.Matrix4();
        const quaternion = new THREE.Quaternion();

        for (let i = 0; i < runtime.structurePositions.length; i += 1) {
            const base = runtime.structurePositions[i];
            const pulse = Math.sin(elapsed * 1.4 + base.phase) * 0.25;
            const y = base.y + pulse;
            const yScale = Math.max(0.35, base.scale + pulse * 0.35);

            matrix.compose(
                new THREE.Vector3(base.x, y, base.z),
                quaternion,
                new THREE.Vector3(1, yScale, 1),
            );
            runtime.structures.setMatrixAt(i, matrix);
        }

        runtime.structures.instanceMatrix.needsUpdate = true;
    }

    runtime.renderer.render(runtime.scene, runtime.camera);
};

const attachRendererToHost = (runtime: Runtime, container: HTMLElement) => {
    if (!runtime.renderer) {
        return;
    }

    const canvas = runtime.renderer.domElement;
    if (canvas.parentElement !== container) {
        canvas.parentElement?.removeChild(canvas);
        container.appendChild(canvas);
    }

    onResize();
};

const disposeRuntime = (runtime: Runtime) => {
    if (runtime.mediaQueryList && runtime.mediaChangeHandler) {
        runtime.mediaQueryList.removeEventListener('change', runtime.mediaChangeHandler);
    }

    if (runtime.visibilityHandler) {
        document.removeEventListener('visibilitychange', runtime.visibilityHandler);
    }

    if (runtime.resizeHandler) {
        window.removeEventListener('resize', runtime.resizeHandler);
    }

    if (runtime.frameId) {
        window.cancelAnimationFrame(runtime.frameId);
        runtime.frameId = 0;
    }

    runtime.geometryDisposables.forEach((geometry) => geometry.dispose());
    runtime.materialDisposables.forEach((material) => material.dispose());

    if (runtime.renderer) {
        runtime.renderer.dispose();
        runtime.renderer.domElement.parentElement?.removeChild(runtime.renderer.domElement);
    }

    runtime.structurePositions.length = 0;
    runtime.geometryDisposables.length = 0;
    runtime.materialDisposables.length = 0;

    runtime.renderer = null;
    runtime.scene = null;
    runtime.camera = null;
    runtime.clock = null;
    runtime.networkGroup = null;
    runtime.structures = null;
    runtime.rings = null;
    runtime.mediaQueryList = null;
    runtime.mediaChangeHandler = null;
    runtime.visibilityHandler = null;
    runtime.resizeHandler = null;
    runtime.host = null;
    runtime.initialized = false;
};

const initializeRuntime = (runtime: Runtime) => {
    if (runtime.initialized) {
        return;
    }

    runtime.scene = new THREE.Scene();
    runtime.scene.fog = new THREE.Fog(0x030910, 10, 44);

    runtime.camera = new THREE.PerspectiveCamera(58, 1, 0.1, 100);
    runtime.camera.position.set(0, 1.6, 19);

    runtime.renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        powerPreference: 'high-performance',
    });

    runtime.renderer.outputColorSpace = THREE.SRGBColorSpace;
    runtime.renderer.setClearColor(0x000000, 0);
    runtime.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));

    const ambientLight = new THREE.AmbientLight(0x77b5cb, 0.75);
    const keyLight = new THREE.DirectionalLight(0x9fe7ff, 0.95);
    keyLight.position.set(4, 7, 8);
    const rimLight = new THREE.DirectionalLight(0xff9d57, 0.6);
    rimLight.position.set(-7, -1, 5);

    runtime.scene.add(ambientLight, keyLight, rimLight);

    const viewportWidth = runtime.host?.clientWidth ?? window.innerWidth;
    const nodeCount = viewportWidth < 640 ? 90 : viewportWidth < 1024 ? 140 : 200;
    const structureCount = viewportWidth < 640 ? 14 : viewportWidth < 1024 ? 24 : 32;

    const network = makeNetwork(runtime, nodeCount);
    runtime.networkGroup = network.group;
    runtime.scene.add(runtime.networkGroup);

    runtime.structures = makeIndustrialStructures(runtime, structureCount);
    runtime.scene.add(runtime.structures);

    runtime.rings = makeRings(runtime);
    runtime.scene.add(runtime.rings);

    runtime.clock = new THREE.Clock();
    onResize();

    runtime.visibilityHandler = () => {
        runtime.visibilityPaused = document.hidden;
    };

    runtime.resizeHandler = () => onResize();

    document.addEventListener('visibilitychange', runtime.visibilityHandler);
    window.addEventListener('resize', runtime.resizeHandler, { passive: true });

    runtime.mediaQueryList = window.matchMedia('(prefers-reduced-motion: reduce)');
    runtime.visibilityPaused = runtime.mediaQueryList.matches || document.hidden;

    runtime.mediaChangeHandler = (event: MediaQueryListEvent) => {
        runtime.visibilityPaused = event.matches || document.hidden;
    };

    runtime.mediaQueryList.addEventListener('change', runtime.mediaChangeHandler);

    runtime.initialized = true;
    animate();

    if (!globalRuntime.__landingBackgroundCleanup__) {
        window.addEventListener('beforeunload', () => disposeRuntime(runtime), {
            once: true,
        });
        globalRuntime.__landingBackgroundCleanup__ = true;
    }
};

onMounted(() => {
    if (!host.value) {
        return;
    }

    const runtime = getRuntime();
    runtime.host = host.value;

    initializeRuntime(runtime);
    attachRendererToHost(runtime, host.value);
});

onBeforeUnmount(() => {
    const runtime = getRuntime();

    if (runtime.host === host.value) {
        runtime.host = null;
    }

    if (host.value && runtime.renderer?.domElement.parentElement === host.value) {
        host.value.removeChild(runtime.renderer.domElement);
    }
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
