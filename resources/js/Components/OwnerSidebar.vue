
<script setup>
import { Link, router } from '@inertiajs/vue3'

defineProps({
    owner: {
        type: Object,
        required: true,
    },
})

const logout = () => {
    router.post(route('owner.logout'))
}
</script>

<template>
    <aside class="sidebar">
        <!-- Brand -->
        <div class="sidebar-header">
            <Link href="/" class="brand">
                MOXXER<span>.</span>
            </Link>

            <span class="brand-caption">OWNER WORKSPACE</span>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-navigation">
            <span class="sidebar-label">WORKSPACE</span>

            <!-- Dashboard -->
            <Link
                :href="route('owner.dashboard')"
                class="nav-item"
                :class="{ active: route().current('owner.dashboard') }"
            >
                <svg
                    class="nav-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
                    <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" />
                    <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
                    <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" />
                </svg>

                <span class="nav-text">Dashboard</span>

                <span
                    v-if="route().current('owner.dashboard')"
                    class="active-indicator"
                ></span>
            </Link>

            <!-- Products -->
            <Link
                :href="route('owner.products.index')"
                class="nav-item"
                :class="{ active: route().current('owner.products.*') }"
            >
                <svg
                    class="nav-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M3.5 7.5L12 3l8.5 4.5v9L12 21l-8.5-4.5v-9Z" />
                    <path d="M3.8 7.7L12 12l8.2-4.3" />
                    <path d="M12 12v9" />
                    <path d="M7.7 5.3l8.5 4.6" />
                </svg>

                <span class="nav-text">Produk</span>

                <span
                    v-if="route().current('owner.products.*')"
                    class="active-indicator"
                ></span>
            </Link>
        </nav>

        <!-- Bottom Section -->
        <div class="sidebar-bottom">
            <div class="sidebar-divider"></div>

            <!-- Owner Profile -->
            <div class="owner-profile">
                <div class="avatar">
                    {{ owner.name?.charAt(0)?.toUpperCase() }}
                </div>

                <div class="owner-info">
                    <strong>{{ owner.name }}</strong>
                    <span>Owner Account</span>
                </div>

                <span class="profile-status"></span>
            </div>

            <!-- Logout -->
            <button class="logout-button" @click="logout">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M10 17l5-5-5-5" />
                    <path d="M15 12H3" />
                    <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" />
                </svg>

                <span>Sign out</span>
            </button>

            <div class="sidebar-footer">
                MOXXER © {{ new Date().getFullYear() }}
            </div>
        </div>
    </aside>
</template>

<style scoped>
.sidebar {
    width: 260px;
    min-height: 100vh;
    height: 100vh;
    position: sticky;
    top: 0;
    flex-shrink: 0;

    display: flex;
    flex-direction: column;

    padding: 30px 20px 22px;

    background: #f5f5f2;
    color: #171717;
    border-right: 1px solid #e7e7e2;
}

/* Brand */

.sidebar-header {
    display: flex;
    flex-direction: column;
    padding: 0 10px;
}

.brand {
    width: fit-content;
    color: #171717;
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -1.7px;
    line-height: 1;
    text-decoration: none;
}

.brand span {
    color: #999;
}

.brand-caption {
    margin-top: 9px;
    color: #a0a09a;
    font-size: 8px;
    font-weight: 600;
    letter-spacing: 1.5px;
}

/* Navigation */

.sidebar-navigation {
    margin-top: 65px;
}

.sidebar-label {
    display: block;
    margin: 0 0 12px 11px;

    color: #a0a09a;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1.3px;
}

.nav-item {
    min-height: 43px;
    position: relative;

    display: flex;
    align-items: center;
    gap: 12px;

    padding: 0 12px;
    border-radius: 9px;

    color: #777771;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;

    transition:
        background .2s ease,
        color .2s ease;
}

.nav-item:hover {
    background: #ebebe7;
    color: #171717;
}

.nav-item.active {
    background: #e8e8e3;
    color: #171717;
    font-weight: 600;
}

.nav-icon {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

.nav-text {
    flex: 1;
}

.active-indicator {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #171717;
}

/* Bottom */

.sidebar-bottom {
    margin-top: auto;
}

.sidebar-divider {
    height: 1px;
    background: #e3e3de;
    margin: 0 0 17px;
}

/* Owner Profile */

.owner-profile {
    display: flex;
    align-items: center;
    gap: 11px;

    padding: 5px 3px 16px;
    min-width: 0;
}

.avatar {
    width: 36px;
    height: 36px;
    flex-shrink: 0;

    display: grid;
    place-items: center;

    border-radius: 11px;
    background: #171717;
    color: #fff;

    font-size: 13px;
    font-weight: 600;
}

.owner-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    flex: 1;
}

.owner-info strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;

    color: #252525;
    font-size: 11px;
    font-weight: 600;
}

.owner-info span {
    color: #999992;
    font-size: 9px;
    font-weight: 500;
}

.profile-status {
    width: 7px;
    height: 7px;
    flex-shrink: 0;

    border-radius: 50%;
    background: #6b9870;
}

/* Logout */

.logout-button {
    width: 100%;
    height: 40px;

    display: flex;
    align-items: center;
    gap: 11px;

    padding: 0 11px;

    border: none;
    border-radius: 9px;
    background: transparent;

    color: #85857e;
    font-family: inherit;
    font-size: 11px;
    font-weight: 500;
    text-align: left;

    cursor: pointer;

    transition:
        background .2s ease,
        color .2s ease;
}

.logout-button:hover {
    background: #eaeae5;
    color: #171717;
}

.logout-button svg {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

/* Footer */

.sidebar-footer {
    margin-top: 20px;
    padding: 0 11px;

    color: #b0b0a9;
    font-size: 8px;
    font-weight: 500;
    letter-spacing: 1px;
}

/* Responsive */

@media (max-width: 760px) {
    .sidebar {
        width: 76px;
        padding: 26px 10px 18px;
        align-items: center;
    }

    .sidebar-header {
        padding: 0;
        align-items: center;
    }

    .brand {
        font-size: 17px;
        letter-spacing: -1px;
    }

    .brand-caption,
    .sidebar-label,
    .nav-text,
    .active-indicator,
    .owner-info,
    .profile-status,
    .logout-button span,
    .sidebar-footer {
        display: none;
    }

    .sidebar-navigation {
        width: 100%;
        margin-top: 55px;
    }

    .nav-item {
        justify-content: center;
        padding: 0;
    }

    .nav-icon {
        width: 19px;
        height: 19px;
    }

    .sidebar-bottom {
        width: 100%;
    }

    .owner-profile {
        justify-content: center;
        padding: 12px 0;
    }

    .avatar {
        width: 36px;
        height: 36px;
    }

    .logout-button {
        justify-content: center;
        padding: 0;
    }

    .logout-button svg {
        width: 18px;
        height: 18px;
    }
}
</style>
