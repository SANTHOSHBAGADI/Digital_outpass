// Show login form with animation
function showLogin(type) {
    const form = document.getElementById('login-form');
    const title = document.getElementById('login-title');
    const userType = document.getElementById('user-type');
    
    form.style.display = 'block';
    form.classList.add('animate__animated', 'animate__slideInLeft');
    userType.value = type;
    
    switch(type) {
        case 'student':
            title.textContent = 'Student Login';
            break;
        case 'admin':
            title.textContent = 'Admin Login';
            break;
        case 'security':
            title.textContent = 'Security Login';
            break;
    }
}

// Add animation to form submissions and initialize event listeners
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.classList.add('animate__animated', 'animate__pulse');
            }
        });
    });

    // Animate requests on load
    const requests = document.querySelectorAll('.request');
    requests.forEach((request, index) => {
        request.style.animationDelay = `${index * 0.1}s`;
        request.classList.add('animate__animated', 'animate__fadeInUp');
    });

    // Animate table rows on load (for log_book.php)
    const tableRows = document.querySelectorAll('.log-book-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.1}s`;
        row.classList.add('animate__animated', 'animate__fadeInUp');
    });
});
// Add animation to home page elements on load
document.addEventListener('DOMContentLoaded', () => {
    // Animate navbar
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        navbar.classList.add('animate__animated', 'animate__fadeInDown');
    }

    // Animate hero section
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
        heroContent.classList.add('animate__animated', 'animate__fadeInDown');
    }

    // Animate feature cards with staggered effect
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.2}s`;
        card.classList.add('animate__animated', 'animate__fadeInUp');
    });

    // Animate testimonial cards
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    testimonialCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.3}s`;
        card.classList.add('animate__animated', 'animate__fadeIn');
    });

    // Animate CTA button
    const ctaButton = document.querySelector('.cta-btn');
    if (ctaButton) {
        ctaButton.classList.add('animate__animated', 'animate__pulse', 'animate__infinite');
    }

    // Scroll-triggered animations for sections
    const sections = document.querySelectorAll('.features-section, .about-section, .testimonials-section, .cta-section');
    const observerOptions = {
        threshold: 0.2
    };

    const sectionObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__animated', 'animate__fadeIn');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    sections.forEach(section => {
        sectionObserver.observe(section);
    });

    // Back to Top Button
    const backToTopButton = document.getElementById('back-to-top');
    if (backToTopButton) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });

        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});
// Back to Top Button
window.addEventListener('scroll', () => {
    const backToTop = document.getElementById('back-to-top');
    if (window.scrollY > 300) {
        backToTop.style.display = 'block';
    } else {
        backToTop.style.display = 'none';
    }
});

document.getElementById('back-to-top').addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Smooth Scrolling for Anchor Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        target.scrollIntoView({ behavior: 'smooth' });
    });
});

// Testimonial Carousel Animation
const testimonialCards = document.querySelectorAll('.testimonial-card');
let currentTestimonial = 0;

function showTestimonial(index) {
    testimonialCards.forEach((card, i) => {
        card.style.display = i === index ? 'block' : 'none';
    });
}

function nextTestimonial() {
    currentTestimonial = (currentTestimonial + 1) % testimonialCards.length;
    showTestimonial(currentTestimonial);
}

showTestimonial(currentTestimonial);
setInterval(nextTestimonial, 5000); // Change testimonial every 5 seconds

// Fade In Elements on Scroll
const fadeInElements = document.querySelectorAll('.animate__animated');
const observerOptions = {
    threshold: 0.1
};

const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate__fadeIn');
            entry.target.classList.add('animate__fadeInUp'); // For workflow items
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

fadeInElements.forEach(element => {
    observer.observe(element);
});