/**
 * PayslipMax Main JavaScript - Modern UX & Gamification
 * Implements theme switching, animations, gamification, and enhanced user experience
 */

class PayslipMaxApp {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'dark';
        this.userStats = this.loadUserStats();
        this.achievements = this.loadAchievements();
        this.isLoading = true;
        
        this.init();
    }

    /**
     * Initialize the application
     */
    init() {
        this.showLoadingScreen();
        this.setupTheme();
        this.setupNavigation();
        this.setupAnimations();
        this.setupGamification();
        this.setupInteractiveElements();
        this.setupPerformanceOptimizations();
        this.hideLoadingScreen();
    }

    /**
     * Show loading screen with animation
     */
    showLoadingScreen() {
        const loadingScreen = document.querySelector('.loading-screen');
        if (loadingScreen) {
            loadingScreen.style.display = 'flex';
        }
    }

    /**
     * Hide loading screen with animation
     */
    hideLoadingScreen() {
        setTimeout(() => {
            const loadingScreen = document.querySelector('.loading-screen');
            if (loadingScreen) {
                loadingScreen.classList.add('hidden');
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                }, 500);
            }
            this.isLoading = false;
            this.triggerPageAnimations();
        }, 2000);
    }

    /**
     * Setup theme switching functionality
     */
    setupTheme() {
        document.documentElement.setAttribute('data-theme', this.theme);
        
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            this.updateThemeIcon(themeToggle);
            
            themeToggle.addEventListener('click', () => {
                this.toggleTheme();
                this.updateThemeIcon(themeToggle);
                this.showAchievement('Theme Master', 'You discovered the theme toggle!');
            });
        }
    }

    /**
     * Toggle between light and dark themes
     */
    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', this.theme);
        localStorage.setItem('theme', this.theme);
        
        // Add theme transition effect
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    /**
     * Update theme toggle icon
     */
    updateThemeIcon(toggle) {
        const icon = toggle.querySelector('i');
        if (icon) {
            icon.className = this.theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    /**
     * Setup navigation functionality
     */
    setupNavigation() {
        const navbar = document.querySelector('.navbar');
        const navToggle = document.querySelector('.nav-toggle');
        const navMenu = document.querySelector('.nav-menu');
        
        // Navbar scroll effect
        if (navbar) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
        }

        // Mobile menu toggle
        if (navToggle && navMenu) {
            navToggle.addEventListener('click', () => {
                navToggle.classList.toggle('active');
                navMenu.classList.toggle('active');
            });
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Active navigation highlighting
        this.setupActiveNavigation();
    }

    /**
     * Setup active navigation highlighting
     */
    setupActiveNavigation() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${id}`) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        }, { threshold: 0.5 });

        sections.forEach(section => observer.observe(section));
    }

    /**
     * Setup scroll animations
     */
    setupAnimations() {
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    
                    // Trigger specific animations based on element type
                    if (entry.target.classList.contains('stat-number')) {
                        this.animateCounter(entry.target);
                    }
                    
                    if (entry.target.classList.contains('feature-card')) {
                        this.animateFeatureCard(entry.target);
                    }
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.hero-content, .hero-visual, .section-header, .feature-card, .stat-item, .upload-card').forEach(el => {
            observer.observe(el);
        });
    }

    /**
     * Animate counter numbers
     */
    animateCounter(element) {
        const target = parseInt(element.textContent.replace(/[^\d]/g, ''));
        const duration = 2000;
        const start = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(progress * target);
            element.textContent = this.formatNumber(current);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    /**
     * Format numbers with appropriate suffixes
     */
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M+';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K+';
        }
        return num.toString();
    }

    /**
     * Animate feature cards
     */
    animateFeatureCard(card) {
        const icon = card.querySelector('.feature-icon');
        if (icon) {
            setTimeout(() => {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
                setTimeout(() => {
                    icon.style.transform = 'scale(1) rotate(0deg)';
                }, 300);
            }, 200);
        }
    }

    /**
     * Trigger page animations after loading
     */
    triggerPageAnimations() {
        // Stagger animation for hero elements
        const heroElements = document.querySelectorAll('.hero-content > *');
        heroElements.forEach((el, index) => {
            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 200);
        });

        // Floating elements animation
        this.setupFloatingElements();
    }

    /**
     * Setup floating elements animation
     */
    setupFloatingElements() {
        const floatingElements = document.querySelectorAll('.floating-icon');
        floatingElements.forEach((el, index) => {
            el.style.setProperty('--delay', `${index * 0.5}s`);
            el.style.animation = `float 3s ease-in-out infinite ${index * 0.5}s`;
        });
    }

    /**
     * Setup gamification features
     */
    setupGamification() {
        this.setupAchievementSystem();
        this.setupProgressTracking();
        this.setupInteractionRewards();
        this.displayUserStats();
    }

    /**
     * Setup achievement system
     */
    setupAchievementSystem() {
        // Check for achievements on page load
        this.checkAchievements();
        
        // Create achievement notification container
        if (!document.querySelector('.achievement-container')) {
            const container = document.createElement('div');
            container.className = 'achievement-container';
            document.body.appendChild(container);
        }
    }

    /**
     * Check for achievements
     */
    checkAchievements() {
        const achievements = [
            {
                id: 'first_visit',
                name: 'Welcome Explorer',
                description: 'Welcome to PayslipMax!',
                condition: () => !this.achievements.includes('first_visit')
            },
            {
                id: 'scroll_master',
                name: 'Scroll Master',
                description: 'You explored the entire page!',
                condition: () => window.scrollY > document.body.scrollHeight * 0.8
            },
            {
                id: 'feature_explorer',
                name: 'Feature Explorer',
                description: 'You checked out our features!',
                condition: () => document.querySelector('.features-section')?.getBoundingClientRect().top < window.innerHeight
            }
        ];

        achievements.forEach(achievement => {
            if (achievement.condition() && !this.achievements.includes(achievement.id)) {
                this.unlockAchievement(achievement);
            }
        });
    }

    /**
     * Unlock achievement
     */
    unlockAchievement(achievement) {
        this.achievements.push(achievement.id);
        this.saveAchievements();
        this.showAchievement(achievement.name, achievement.description);
        this.updateUserStats('achievements', this.achievements.length);
    }

    /**
     * Show achievement notification
     */
    showAchievement(title, description) {
        const container = document.querySelector('.achievement-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = 'achievement-notification';
        notification.innerHTML = `
            <div class="achievement-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="achievement-content">
                <div class="achievement-title">${title}</div>
                <div class="achievement-description">${description}</div>
            </div>
            <div class="achievement-close">
                <i class="fas fa-times"></i>
            </div>
        `;

        container.appendChild(notification);

        // Show animation
        setTimeout(() => notification.classList.add('show'), 100);

        // Auto hide after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);

        // Manual close
        notification.querySelector('.achievement-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });

        // Play achievement sound (if enabled)
        this.playAchievementSound();
    }

    /**
     * Play achievement sound
     */
    playAchievementSound() {
        // Create a simple achievement sound using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
            oscillator.frequency.setValueAtTime(1200, audioContext.currentTime + 0.2);
            
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            // Audio not supported or blocked
        }
    }

    /**
     * Setup progress tracking
     */
    setupProgressTracking() {
        // Track page interactions
        let interactionCount = 0;
        
        document.addEventListener('click', () => {
            interactionCount++;
            this.updateUserStats('interactions', interactionCount);
            
            if (interactionCount === 10) {
                this.showAchievement('Click Master', 'You\'ve made 10 interactions!');
            }
        });

        // Track scroll progress
        window.addEventListener('scroll', () => {
            const scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
            this.updateUserStats('maxScroll', Math.max(this.userStats.maxScroll || 0, scrollPercent));
            
            if (scrollPercent > 90 && !this.achievements.includes('scroll_master')) {
                this.unlockAchievement({
                    id: 'scroll_master',
                    name: 'Scroll Master',
                    description: 'You explored the entire page!'
                });
            }
        });
    }

    /**
     * Setup interaction rewards
     */
    setupInteractionRewards() {
        // Add hover effects with rewards
        document.querySelectorAll('.btn, .feature-card, .nav-link').forEach(element => {
            element.addEventListener('mouseenter', () => {
                this.addParticleEffect(element);
            });
        });

        // Add click rewards
        document.querySelectorAll('.btn-primary').forEach(button => {
            button.addEventListener('click', () => {
                this.addClickEffect(button);
                this.updateUserStats('buttonClicks', (this.userStats.buttonClicks || 0) + 1);
            });
        });
    }

    /**
     * Add particle effect to element
     */
    addParticleEffect(element) {
        const rect = element.getBoundingClientRect();
        const particle = document.createElement('div');
        particle.className = 'particle-effect';
        particle.style.cssText = `
            position: fixed;
            left: ${rect.left + rect.width / 2}px;
            top: ${rect.top + rect.height / 2}px;
            width: 4px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            animation: particle-float 1s ease-out forwards;
        `;
        
        document.body.appendChild(particle);
        
        setTimeout(() => particle.remove(), 1000);
    }

    /**
     * Add click effect to element
     */
    addClickEffect(element) {
        const ripple = document.createElement('div');
        ripple.className = 'click-ripple';
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        `;
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    }

    /**
     * Setup interactive elements
     */
    setupInteractiveElements() {
        // Enhanced button interactions
        this.setupButtonEffects();
        
        // Interactive phone mockup
        this.setupPhoneMockup();
        
        // Parallax effects
        this.setupParallaxEffects();
        
        // Cursor effects
        this.setupCursorEffects();
    }

    /**
     * Setup button effects
     */
    setupButtonEffects() {
        document.querySelectorAll('.btn').forEach(button => {
            // Add particles container
            const particles = document.createElement('div');
            particles.className = 'btn-particles';
            button.appendChild(particles);
            
            // Magnetic effect
            button.addEventListener('mousemove', (e) => {
                const rect = button.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                
                button.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px)`;
            });
            
            button.addEventListener('mouseleave', () => {
                button.style.transform = '';
            });
        });
    }

    /**
     * Setup phone mockup interactions
     */
    setupPhoneMockup() {
        const phoneMockup = document.querySelector('.phone-mockup');
        if (!phoneMockup) return;
        
        // Tilt effect
        phoneMockup.addEventListener('mousemove', (e) => {
            const rect = phoneMockup.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            const rotateX = (y / rect.height) * 20;
            const rotateY = (x / rect.width) * -20;
            
            phoneMockup.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });
        
        phoneMockup.addEventListener('mouseleave', () => {
            phoneMockup.style.transform = '';
        });
        
        // Animate app interface
        this.animateAppInterface();
    }

    /**
     * Animate app interface
     */
    animateAppInterface() {
        const payslipCards = document.querySelectorAll('.payslip-card');
        const insightChart = document.querySelector('.insight-chart');
        
        // Animate payslip cards
        payslipCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 500 + 1000);
        });
        
        // Animate insight chart
        if (insightChart) {
            setTimeout(() => {
                insightChart.style.opacity = '1';
                insightChart.style.transform = 'scale(1)';
            }, 2000);
        }
    }

    /**
     * Setup parallax effects
     */
    setupParallaxEffects() {
        const parallaxElements = document.querySelectorAll('.hero-particles, .floating-elements');
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const rate = scrolled * -0.5;
                element.style.transform = `translateY(${rate}px)`;
            });
        });
    }

    /**
     * Setup cursor effects
     */
    setupCursorEffects() {
        // Custom cursor for interactive elements
        const cursor = document.createElement('div');
        cursor.className = 'custom-cursor';
        cursor.style.cssText = `
            position: fixed;
            width: 20px;
            height: 20px;
            background: var(--primary-color);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            opacity: 0;
            transition: all 0.3s ease;
            mix-blend-mode: difference;
        `;
        document.body.appendChild(cursor);
        
        document.addEventListener('mousemove', (e) => {
            cursor.style.left = e.clientX - 10 + 'px';
            cursor.style.top = e.clientY - 10 + 'px';
        });
        
        document.querySelectorAll('.btn, .nav-link, .feature-card').forEach(element => {
            element.addEventListener('mouseenter', () => {
                cursor.style.opacity = '1';
                cursor.style.transform = 'scale(1.5)';
            });
            
            element.addEventListener('mouseleave', () => {
                cursor.style.opacity = '0';
                cursor.style.transform = 'scale(1)';
            });
        });
    }

    /**
     * Setup performance optimizations
     */
    setupPerformanceOptimizations() {
        // Lazy loading for images
        this.setupLazyLoading();
        
        // Debounced scroll events
        this.setupDebouncedEvents();
        
        // Preload critical resources
        this.preloadResources();
    }

    /**
     * Setup lazy loading for images
     */
    setupLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }

    /**
     * Setup debounced events
     */
    setupDebouncedEvents() {
        let scrollTimeout;
        let resizeTimeout;
        
        window.addEventListener('scroll', () => {
            if (scrollTimeout) clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                this.handleScroll();
            }, 16); // ~60fps
        });
        
        window.addEventListener('resize', () => {
            if (resizeTimeout) clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.handleResize();
            }, 250);
        });
    }

    /**
     * Handle scroll events
     */
    handleScroll() {
        // Update scroll progress
        const scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
        document.documentElement.style.setProperty('--scroll-progress', `${scrollPercent}%`);
    }

    /**
     * Handle resize events
     */
    handleResize() {
        // Recalculate animations and layouts
        this.setupFloatingElements();
    }

    /**
     * Preload critical resources
     */
    preloadResources() {
        const criticalResources = [
            '/css/style.css',
            '/js/upload.js'
        ];
        
        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource;
            link.as = resource.endsWith('.css') ? 'style' : 'script';
            document.head.appendChild(link);
        });
    }

    /**
     * Load user statistics
     */
    loadUserStats() {
        const defaultStats = {
            visits: 0,
            interactions: 0,
            achievements: 0,
            maxScroll: 0,
            buttonClicks: 0
        };
        
        const saved = localStorage.getItem('payslipmax_stats');
        return saved ? { ...defaultStats, ...JSON.parse(saved) } : defaultStats;
    }

    /**
     * Save user statistics
     */
    saveUserStats() {
        localStorage.setItem('payslipmax_stats', JSON.stringify(this.userStats));
    }

    /**
     * Update user statistics
     */
    updateUserStats(key, value) {
        this.userStats[key] = value;
        this.saveUserStats();
        this.displayUserStats();
    }

    /**
     * Display user statistics
     */
    displayUserStats() {
        // Update visit count
        this.userStats.visits++;
        this.saveUserStats();
        
        // Show stats in console for debugging
        if (location.hostname === 'localhost') {
            console.log('User Stats:', this.userStats);
        }
    }

    /**
     * Load achievements
     */
    loadAchievements() {
        const saved = localStorage.getItem('payslipmax_achievements');
        return saved ? JSON.parse(saved) : [];
    }

    /**
     * Save achievements
     */
    saveAchievements() {
        localStorage.setItem('payslipmax_achievements', JSON.stringify(this.achievements));
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    new PayslipMaxApp();
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes particle-float {
        0% { transform: translateY(0) scale(1); opacity: 1; }
        100% { transform: translateY(-50px) scale(0); opacity: 0; }
    }
    
    @keyframes ripple {
        to { transform: scale(4); opacity: 0; }
    }
    
    .achievement-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--bg-glass);
        backdrop-filter: blur(10px);
        border: 1px solid var(--border-accent);
        border-radius: var(--radius-lg);
        padding: var(--space-lg);
        display: flex;
        align-items: center;
        gap: var(--space-md);
        max-width: 350px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        z-index: 10000;
        box-shadow: var(--shadow-lg);
    }
    
    .achievement-notification.show {
        transform: translateX(0);
    }
    
    .achievement-icon {
        width: 40px;
        height: 40px;
        background: var(--gradient-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: var(--text-lg);
        animation: bounce 0.6s ease;
    }
    
    .achievement-content {
        flex: 1;
    }
    
    .achievement-title {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .achievement-description {
        font-size: var(--text-sm);
        color: var(--text-secondary);
    }
    
    .achievement-close {
        cursor: pointer;
        color: var(--text-muted);
        transition: color 0.3s ease;
    }
    
    .achievement-close:hover {
        color: var(--text-primary);
    }
    
    .upload-error-inline {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: var(--radius-md);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    
    .error-content {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        color: var(--error-color);
    }
    
    .error-close {
        margin-left: auto;
        background: none;
        border: none;
        color: var(--error-color);
        cursor: pointer;
        padding: var(--space-xs);
    }
    
    .upload-progress-container {
        background: var(--bg-glass);
        backdrop-filter: blur(10px);
        border: 1px solid var(--border-primary);
        border-radius: var(--radius-lg);
        padding: var(--space-xl);
        text-align: center;
    }
    
    .progress-bar {
        width: 100%;
        height: 8px;
        background: var(--bg-secondary);
        border-radius: var(--radius-full);
        overflow: hidden;
        margin-bottom: var(--space-lg);
    }
    
    .progress-fill {
        height: 100%;
        background: var(--gradient-primary);
        border-radius: var(--radius-full);
        transition: width 0.3s ease;
        width: 0%;
    }
    
    .progress-text {
        font-size: var(--text-lg);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }
    
    .progress-details {
        font-size: var(--text-sm);
        color: var(--text-secondary);
    }
    
    .upload-success {
        text-align: center;
    }
    
    .success-icon {
        font-size: 4rem;
        color: var(--success-color);
        margin-bottom: var(--space-lg);
        animation: bounce 0.6s ease;
    }
    
    .success-details {
        background: var(--bg-secondary);
        border-radius: var(--radius-md);
        padding: var(--space-lg);
        margin: var(--space-lg) 0;
    }
    
    .detail-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: var(--space-sm);
    }
    
    .detail-item:last-child {
        margin-bottom: 0;
    }
    
    .label {
        color: var(--text-secondary);
    }
    
    .value {
        color: var(--text-primary);
        font-weight: 500;
    }
    
    .qr-section {
        margin: var(--space-xl) 0;
    }
    
    .qr-code {
        width: 200px;
        height: 200px;
        margin: var(--space-lg) auto;
        background: white;
        border-radius: var(--radius-md);
        padding: var(--space-md);
    }
    
    .qr-code img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    .qr-help {
        font-size: var(--text-sm);
        color: var(--text-secondary);
    }
    
    .success-actions {
        display: flex;
        gap: var(--space-md);
        justify-content: center;
        margin-top: var(--space-xl);
    }
    
    .upload-error {
        text-align: center;
    }
    
    .error-icon {
        font-size: 4rem;
        color: var(--error-color);
        margin-bottom: var(--space-lg);
    }
`;
document.head.appendChild(style); 