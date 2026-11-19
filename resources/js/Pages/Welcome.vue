
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Navbar from '@/Components/Navbar.vue'

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
})

const collections = [
    {
        number: '01',
        name: 'The Oxford',
        description: 'A timeless expression of formal elegance.',
        category: 'FORMAL FOOTWEAR',
    },
    {
        number: '02',
        name: 'The Essential',
        description: 'Refined essentials for everyday confidence.',
        category: 'DAILY ESSENTIALS',
    },
    {
        number: '03',
        name: 'The Signature',
        description: 'Distinctive details. Lasting impressions.',
        category: 'SIGNATURE SERIES',
    },
]
</script>

<template>
    <Head title="MOXXER — Timeless Footwear" />

    <main class="site-wrapper">

        <!-- NAVBAR -->
        <Navbar
            :can-login="canLogin"
            :can-register="canRegister"
        />

        <!-- HERO -->
        <section id="home" class="hero">
            <div class="hero-topline">
                <span>INDEPENDENT FOOTWEAR BRAND</span>
                <span>EST. MOXXER — INDONESIA</span>
            </div>

            <div class="hero-main">
                <p class="hero-overline">TIMELESS FOOTWEAR</p>

                <h1>
                    LESS,<br />
                    <span>BUT BETTER.</span>
                </h1>

                <div class="hero-bottom">
                    <p>
                        A refined approach to footwear.<br />
                        Designed for the modern individual.
                    </p>

                    <a href="#collection" class="discover-link">
                        <span>DISCOVER COLLECTION</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- BRAND INTRO -->
        <section id="philosophy" class="philosophy">
            <div class="section-meta">
                <span>THE MOXXER PHILOSOPHY</span>
                <span>DESIGNED WITH INTENTION</span>
            </div>

            <div class="philosophy-content">
                <p class="small-heading">BEYOND TRENDS.</p>

                <h2>
                    Style fades.<br />
                    <span>Character remains.</span>
                </h2>

                <div class="philosophy-description">
                    <div class="description-line"></div>

                    <p>
                        We believe true elegance is found in simplicity.
                        Every detail matters, every choice has purpose,
                        and every design is made to stand beyond passing trends.
                    </p>
                </div>
            </div>
        </section>

        <!-- STATEMENT -->
        <section class="statement">
            <div class="statement-top">
                <span>OUR BELIEF</span>
                <span>MOXXER — 2026</span>
            </div>

            <div class="statement-content">
                <p>NOT MADE TO FOLLOW.</p>

                <h2>
                    Made to<br />
                    <span>be remembered.</span>
                </h2>

                <div class="statement-bottom">
                    <span>TIMELESS FOOTWEAR FOR MODERN CONFIDENCE.</span>
                    <span>EST. MOXXER</span>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer id="contact" class="footer">
            <div class="footer-main">
                <div class="footer-brand">
                    <Link href="/" class="footer-logo">
                        MOXXER<span>.</span>
                    </Link>

                    <p>
                        Timeless footwear<br />
                        for modern confidence.
                    </p>
                </div>

                <div class="footer-column">
                    <span class="footer-heading">EXPLORE</span>
                    <a href="#home">Home</a>
                    <a href="#collection">Collection</a>
                    <a href="#philosophy">Our Philosophy</a>
                </div>

                <div class="footer-column">
                    <span class="footer-heading">ACCOUNT</span>

                    <Link v-if="canLogin" :href="route('login')">
                        Sign in
                    </Link>

                    <Link v-if="canRegister" :href="route('register')">
                        Create account
                    </Link>
                </div>

                <div class="footer-column footer-contact">
                    <span class="footer-heading">THE MOXXER IDENTITY</span>
                    <p>Made to be remembered.</p>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© {{ new Date().getFullYear() }} MOXXER. ALL RIGHTS RESERVED.</span>
                <span>TIMELESS BY DESIGN.</span>
                <a href="#home">BACK TO TOP ↑</a>
            </div>
        </footer>

    </main>
</template>
