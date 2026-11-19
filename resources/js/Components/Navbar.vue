
<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
})

const page = usePage()

const isHome = computed(() => page.url.split('?')[0] === '/')

const isCatalog = computed(() => {
    return page.url.split('?')[0] === '/catalog'
})
</script>

<template>
    <header class="navbar">
        <Link href="/" class="brand-logo">
            MOXXER<span>.</span>
        </Link>

        <nav class="nav-center">
            <Link href="/" :class="{ active: isHome }">
                Home
            </Link>

            <Link
                :href="route('catalog')"
                :class="{ active: isCatalog }"
            >
                Catalog
            </Link>
        </nav>

        <div class="nav-right">
            <Link v-if="canLogin" :href="route('login')">
                Account
            </Link>

            <Link
                v-if="canRegister"
                :href="route('register')"
                class="nav-register"
            >
                Join us
            </Link>
        </div>
    </header>
</template>

<style scoped>
.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    width: 100%;
    height: 86px;
    padding: 0 7%;
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    align-items: center;
    border-bottom: 1px solid #e5e5e1;
    background: #f5f5f2;
}

.brand-logo {
    color: #171717;
    text-decoration: none;
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -1.7px;
}

.brand-logo span {
    color: #999;
}

.nav-center {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 39px;
}

.nav-center a,
.nav-right a {
    position: relative;
    color: #555;
    text-decoration: none;
    font-size: 11px;
    font-weight: 500;
    transition: color .2s ease;
}

.nav-center a:hover,
.nav-right > a:not(.nav-register):hover {
    color: #111;
}

.nav-center a.active {
    color: #111;
}

.nav-center a.active::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: -9px;
    height: 1px;
    background: #171717;
}

.nav-right {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 25px;
}

.nav-right .nav-register {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-width: 104px;
    height: 38px;
    padding: 0 21px;
    background: #171717;
    border: 1px solid #171717;
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .3px;
    text-decoration: none;
    transition:
        background .25s ease,
        color .25s ease,
        border-color .25s ease;
}

.nav-right .nav-register:hover {
    background: transparent;
    border-color: #171717;
    color: #171717;
}

@media (max-width: 900px) {
    .navbar {
        padding: 0 5%;
    }

    .nav-center {
        gap: 20px;
    }
}

@media (max-width: 600px) {
    .navbar {
        height: 72px;
        padding: 0 22px;
        grid-template-columns: 1fr auto;
    }

    .brand-logo {
        font-size: 23px;
    }

    .nav-center {
        display: none;
    }

    .nav-right {
        gap: 15px;
    }

    .nav-right a {
        font-size: 10px;
    }

    .nav-right .nav-register {
        min-width: 88px;
        height: 35px;
        padding: 0 15px;
    }
}
</style>
