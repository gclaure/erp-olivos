<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import * as THREE from 'three';

const canvasContainer = ref(null);
let scene, camera, renderer, animationFrameId;
let particlesMesh, cubesGroup;
let mouseX = 0;
let mouseY = 0;
let targetX = 0;
let targetY = 0;

const handleMouseMove = (event) => {
    const windowHalfX = window.innerWidth / 4;
    const windowHalfY = window.innerHeight / 2;
    mouseX = (event.clientX - windowHalfX) * 0.0005;
    mouseY = (event.clientY - windowHalfY) * 0.0005;
};

const initThree = () => {
    if (!canvasContainer.value) return;

    const width = canvasContainer.value.clientWidth;
    const height = canvasContainer.value.clientHeight;

    // 1. Scene & Camera
    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(50, width / height, 0.1, 1000);
    camera.position.z = 30;

    // 2. Renderer
    renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    canvasContainer.value.appendChild(renderer.domElement);

    // 3. Particle System (Constellation / Supply Chain Nodes - Exclusion Zone for Logo)
    const particleCount = 100;
    const particleGeometry = new THREE.BufferGeometry();
    const positions = new Float32Array(particleCount * 3);
    const colors = new Float32Array(particleCount * 3);

    const olivePrimary = new THREE.Color('#73AC32');
    const forestSecondary = new THREE.Color('#44773C');
    const salviaAccent = new THREE.Color('#A8C98A');

    for (let i = 0; i < particleCount * 3; i += 3) {
        let posX, posY;
        // Generate positions avoiding top-left area where the logo and title are
        do {
            posX = (Math.random() - 0.3) * 45; // shifted right
            posY = (Math.random() - 0.6) * 35; // shifted downward
        } while (posX < -4 && posY > 2); // Exclude top-left

        positions[i] = posX;
        positions[i + 1] = posY;
        positions[i + 2] = (Math.random() - 0.5) * 20;

        const mixRatio = Math.random();
        const chosenColor = mixRatio < 0.55 ? olivePrimary : (mixRatio < 0.85 ? salviaAccent : forestSecondary);
        colors[i] = chosenColor.r;
        colors[i + 1] = chosenColor.g;
        colors[i + 2] = chosenColor.b;
    }

    particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    particleGeometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

    const particleMaterial = new THREE.PointsMaterial({
        size: 0.45,
        vertexColors: true,
        transparent: true,
        opacity: 0.65,
        blending: THREE.AdditiveBlending,
    });

    particlesMesh = new THREE.Points(particleGeometry, particleMaterial);
    scene.add(particlesMesh);

    // 4. Isometric Floating Geometric Wireframe Cubes (In Bottom-Right Ambient Zone)
    cubesGroup = new THREE.Group();
    const boxGeometry = new THREE.BoxGeometry(2.2, 2.2, 2.2);
    const boxMaterial = new THREE.MeshBasicMaterial({
        color: 0x73AC32,
        wireframe: true,
        transparent: true,
        opacity: 0.22,
    });

    const numCubes = 5;
    for (let i = 0; i < numCubes; i++) {
        const cube = new THREE.Mesh(boxGeometry, boxMaterial.clone());
        // Position cubes in the right / lower quadrant only
        cube.position.set(
            4 + Math.random() * 16,
            -14 + Math.random() * 12,
            -5 + Math.random() * 15
        );
        cube.rotation.set(Math.random() * Math.PI, Math.random() * Math.PI, 0);
        cube.userData = {
            rotSpeedX: (Math.random() - 0.5) * 0.006,
            rotSpeedY: (Math.random() - 0.5) * 0.006,
            floatSpeed: 0.004 + Math.random() * 0.004,
            initialY: cube.position.y,
            offset: Math.random() * Math.PI * 2,
        };
        cubesGroup.add(cube);
    }
    scene.add(cubesGroup);

    // 5. Responsive Resize
    const handleResize = () => {
        if (!canvasContainer.value || !renderer || !camera) return;
        const newWidth = canvasContainer.value.clientWidth;
        const newHeight = canvasContainer.value.clientHeight;
        camera.aspect = newWidth / newHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(newWidth, newHeight);
    };

    window.addEventListener('resize', handleResize);
    window.addEventListener('mousemove', handleMouseMove);

    // 6. Animation Loop
    let clock = new THREE.Clock();

    const animate = () => {
        animationFrameId = requestAnimationFrame(animate);
        const elapsedTime = clock.getElapsedTime();

        // Parallax smooth interpolation
        targetX += (mouseX - targetX) * 0.05;
        targetY += (mouseY - targetY) * 0.05;

        // Rotate particle galaxy
        if (particlesMesh) {
            particlesMesh.rotation.y = elapsedTime * 0.03 + targetX * 2;
            particlesMesh.rotation.x = elapsedTime * 0.015 + targetY * 2;
        }

        // Float cubes
        if (cubesGroup) {
            cubesGroup.rotation.y = elapsedTime * 0.02 + targetX;
            cubesGroup.children.forEach((cube) => {
                cube.rotation.x += cube.userData.rotSpeedX;
                cube.rotation.y += cube.userData.rotSpeedY;
                cube.position.y = cube.userData.initialY + Math.sin(elapsedTime * 1.5 + cube.userData.offset) * 0.8;
            });
        }

        renderer.render(scene, camera);
    };

    animate();
};

onMounted(() => {
    initThree();
});

onBeforeUnmount(() => {
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }
    window.removeEventListener('mousemove', handleMouseMove);
    if (renderer) {
        renderer.dispose();
        if (renderer.domElement && renderer.domElement.parentNode) {
            renderer.domElement.parentNode.removeChild(renderer.domElement);
        }
    }
});
</script>

<template>
    <div ref="canvasContainer" class="absolute inset-0 w-full h-full pointer-events-none overflow-hidden z-0"></div>
</template>
