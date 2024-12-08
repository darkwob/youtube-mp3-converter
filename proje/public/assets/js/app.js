// Enhanced Three.js Background
gsap.registerPlugin(ScrollSmoother)

ScrollSmoother.create({
    smooth: 3,
    effects: true,
  });

  gsap.config({trialWarn: false});
function initializeThreeJsBackground() {
    const container = document.querySelector('#three-js-bg');
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });

    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    container.appendChild(renderer.domElement);

    // Create Instagram-themed particles
    const particlesGeometry = new THREE.BufferGeometry();
    const particlesCount = 2000;
    const posArray = new Float32Array(particlesCount * 3);

    for(let i = 0; i < particlesCount * 3; i++) {
        posArray[i] = (Math.random() - 0.5) * 5;
    }

    particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));

    const particlesMaterial = new THREE.PointsMaterial({
        size: 0.005,
        color: '#FFFFFF',
        transparent: true,
        opacity: 0.8
    });

    const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
    scene.add(particlesMesh);

    camera.position.z = 3;

    // Mouse movement effect
    let mouseX = 0;
    let mouseY = 0;

    document.addEventListener('mousemove', (event) => {
        mouseX = event.clientX / window.innerWidth - 0.5;
        mouseY = event.clientY / window.innerHeight - 0.5;
    });

    // Animation loop
    function animate() {
        requestAnimationFrame(animate);

        particlesMesh.rotation.y += 0.001;
        particlesMesh.rotation.x += 0.001;

        // Smooth camera movement
        camera.position.x += (mouseX * 0.5 - camera.position.x) * 0.05;
        camera.position.y += (-mouseY * 0.5 - camera.position.y) * 0.05;

        renderer.render(scene, camera);
    }

    animate();

    // Handle window resize
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
}

// Enhanced GSAP Animations
function initializeEnhancedAnimations() {
    gsap.registerPlugin(ScrollTrigger);

    // Hero section parallax
    gsap.to('.hero-content', {
        y: 200,
        scrollTrigger: {
            trigger: '.hero-section',
            start: 'top top',
            end: 'bottom top',
            scrub: true
        }
    });

    // Service cards stagger animation
    gsap.from('.service-card', {
        scrollTrigger: {
            trigger: '.service-card',
            start: 'top 80%'
        },
        y: 100,
        opacity: 0,
        duration: 1,
        stagger: 0.2
    });

    // Stats counter animation
    const stats = document.querySelectorAll('.stat-counter');
    stats.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-target'));
        gsap.to(stat, {
            scrollTrigger: {
                trigger: stat,
                start: 'top 80%'
            },
            innerText: target,
            duration: 2,
            snap: { innerText: 1 }
        });
    });

    // Feature items reveal
    gsap.from('.feature-item', {
        scrollTrigger: {
            trigger: '.features-grid',
            start: 'top 70%'
        },
        y: 50,
        opacity: 0,
        duration: 0.8,
        stagger: {
            amount: 1,
            from: 'random'
        }
    });
}

// Enhanced Lottie Animations
function initializeEnhancedLottieAnimations() {
    // Hero section animation
    const heroAnimation = lottie.loadAnimation({
        container: document.querySelector('#hero-lottie'),
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: 'https://lottie.host/a3436c4e-51d8-4c3e-8f91-a667c868f2b7/QhIzwn1kcr.json' // Instagram analytics themed animation
    });

    // Feature section animations
    const featureAnimations = [
        {
            container: '#feature-analytics',
            path: 'https://lottie.host/465988d9-4dea-49e2-afda-271db23b9e94/1IOPxq2W0t.json'
        },
        {
            container: '#feature-automation',
            path: 'https://lottie.host/634c493f-9fa5-4971-9607-630a041b9222/dzpAjq0Azu.json'
        },
        {
            container: '#feature-insights',
            path: 'assets/animations/insights.json'
        }
    ];

    featureAnimations.forEach(animation => {
        lottie.loadAnimation({
            container: document.querySelector(animation.container),
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: animation.path
        });
    });
}

// Initialize everything
document.addEventListener('DOMContentLoaded', () => {
    initializeThreeJsBackground();
    initializeEnhancedAnimations();
    initializeEnhancedLottieAnimations();
});

 // Lottie Animation
 lottie.loadAnimation({
    container: document.getElementById('lottie-animation-1'), // Lottie animasyonları için
    renderer: 'svg',
    loop: true,
    autoplay: true,
    path: '/assets/lottie/instagram.json' // JSON dosyasını Lottiefiles'dan yükleyin
});

lottie.loadAnimation({
    container: document.getElementById('lottie-animation-2'), // Lottie animasyonları için
    renderer: 'svg',
    loop: true,
    autoplay: true,
    path: '/assets/lottie/phone.json' // JSON dosyasını Lottiefiles'dan yükleyin
});

lottie.loadAnimation({
    container: document.getElementById('lottie-animation-3'), // Lottie animasyonları için
    renderer: 'svg',
    loop: true,
    autoplay: true,
    path: '/assets/lottie/reports.json' // JSON dosyasını Lottiefiles'dan yükleyin
});


lottie.loadAnimation({
    container: document.getElementById('demographics'), // Lottie animasyonları için
    renderer: 'svg',
    loop: true,
    autoplay: true,
    path: '/assets/lottie/piechart.json' // JSON dosyasını Lottiefiles'dan yükleyin
});

lottie.loadAnimation({
    container: document.getElementById('performance'), // Lottie animasyonları için
    renderer: 'svg',
    loop: true,
    autoplay: true,
    path: '/assets/lottie/performance.json' // JSON dosyasını Lottiefiles'dan yükleyin
});


document.addEventListener('DOMContentLoaded', () => {
    const lottieAnimation = lottie.loadAnimation({
        container: document.querySelector('#paper-plane'),
        renderer: 'svg',
        loop: false,
        autoplay: false,
        path: 'assets/lottie/paper-plane.json' // Lottie JSON dosyanız
    });

    // GSAP ve ScrollTrigger'ı kullanarak kağıt uçağın scroll boyunca hareket etmesini sağlıyoruz
    gsap.registerPlugin(ScrollTrigger);

    gsap.to("#paper-plane", {
        scrollTrigger: {
            trigger: "#smooth-content",
            start: "top top",
            end: "bottom bottom",
            scrub: true,
            onUpdate: (self) => {
                const progress = self.progress;

                // Hareket boyunca x ve y konumlarını belirlemek için trigonometri kullanabilirsiniz
                const movementX = 300 * Math.sin(progress * Math.PI * 2);
                const movementY = 200 * Math.cos(progress * Math.PI * 2);

                // Uçak nesnesinin x ve y konumlarını GSAP ile ayarlıyoruz
                gsap.set("#paper-plane", {
                    x: movementX,
                    y: movementY,
                    rotation: movementX < 0 ? -20 : 20 // Hareket yönüne göre dönüş açısı ayarla
                });
            }
        }
    });
});
