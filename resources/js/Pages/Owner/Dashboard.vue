
<script setup>
import { Head } from '@inertiajs/vue3'
import OwnerSidebar from '@/Components/OwnerSidebar.vue'

defineProps({
    owner: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <Head title="Owner Dashboard — MOXXER" />

    <main class="owner-dashboard">
        <OwnerSidebar :owner="owner" />

        <section class="main-content">
            <div class="page-content">
                <div class="page-heading">
                    <div>
                        <h1>
                            Good day,
                            <span>{{ owner.name }}.</span>
                        </h1>

                        <p>
                            Welcome to your MOXXER administration workspace.
                        </p>
                    </div>

                    <div class="date-label">
                        OWNER PORTAL<br />
                        <strong>{{ new Date().getFullYear() }}</strong>
                    </div>
                </div>

                <div class="welcome-panel">
                    <div>
                        <span class="panel-label">THE MOXXER IDENTITY</span>

                        <h2>
                            Made to be<br />
                            <span>remembered.</span>
                        </h2>

                        <p>
                            A refined approach to footwear.<br />
                            Designed for the modern individual.
                        </p>
                    </div>

                    <div class="panel-mark">M.</div>

                    <span class="panel-index">MOXXER — OWNER</span>
                </div>

                <section class="account-section">
                    <div class="section-heading">
                        <div>
                            <span class="eyebrow">ACCOUNT INFORMATION</span>
                            <h2>Owner profile</h2>
                        </div>
                    </div>

                    <div class="account-card">
                        <div class="account-field">
                            <span>FULL NAME</span>
                            <strong>{{ owner.name }}</strong>
                        </div>

                        <div class="account-field">
                            <span>EMAIL ADDRESS</span>
                            <strong>{{ owner.email }}</strong>
                        </div>

                        <div class="account-field">
                            <span>ACCOUNT TYPE</span>
                            <strong>Primary Owner</strong>
                        </div>

                        <div class="account-field">
                            <span>MEMBER SINCE</span>
                            <strong>
                                {{
                                    new Date(owner.created_at).toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: 'long',
                                        year: 'numeric'
                                    })
                                }}
                            </strong>
                        </div>
                    </div>
                </section>

                <footer class="dashboard-footer">
                    <span>
                        © {{ new Date().getFullYear() }} MOXXER. ALL RIGHTS RESERVED.
                    </span>

                    <span>TIMELESS BY DESIGN.</span>
                </footer>
            </div>
        </section>
    </main>
</template>

<style scoped>
.owner-dashboard {
    min-height: 100vh;
    display: flex;
    background: #fafaf8;
    color: #171717;
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.main-content {
    flex: 1;
    min-width: 0;
}

.page-content {
    max-width: 1300px;
    margin: auto;
    padding: 48px 55px 30px;
}

/* Page Heading */

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 24px;
    margin-bottom: 40px;
}

.page-heading h1 {
    margin: 10px 0 7px;
    font-size: 30px;
    font-weight: 650;
    letter-spacing: -1.2px;
}

.page-heading h1 span {
    color: #999992;
}

.page-heading p {
    margin: 0;
    color: #92928b;
    font-size: 12px;
    font-weight: 400;
}

.date-label {
    color: #999992;
    font-size: 9px;
    line-height: 1.8;
    letter-spacing: 1px;
    text-align: right;
}

.date-label strong {
    color: #171717;
    font-size: 13px;
    font-weight: 600;
}

/* Welcome Panel */

.welcome-panel {
    min-height: 300px;
    padding: 45px;
    background: #171717;
    color: white;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
}

.panel-label {
    color: #888;
    font-size: 9px;
    letter-spacing: 1.5px;
    font-weight: 600;
}

.welcome-panel h2 {
    margin: 25px 0 15px;
    font-size: clamp(38px, 5vw, 58px);
    line-height: .98;
    letter-spacing: -2.5px;
    font-weight: 500;
}

.welcome-panel h2 span {
    color: #888;
}

.welcome-panel p {
    color: #aaa;
    font-size: 11px;
    line-height: 1.9;
    font-weight: 400;
}

.panel-mark {
    position: absolute;
    right: 7%;
    top: 50%;
    transform: translateY(-50%);
    color: #292929;
    font-size: clamp(150px, 25vw, 280px);
    font-weight: 800;
    letter-spacing: -20px;
    line-height: 1;
    pointer-events: none;
}

.panel-index {
    position: absolute;
    bottom: 25px;
    right: 30px;
    color: #777;
    font-size: 8px;
    letter-spacing: 1.5px;
}

/* Account Information */

.account-section {
    margin-top: 55px;
}

.eyebrow {
    color: #999992;
    font-size: 9px;
    letter-spacing: 1.5px;
    font-weight: 600;
}

.section-heading h2 {
    margin: 12px 0 20px;
    font-size: 22px;
    font-weight: 600;
    letter-spacing: -.7px;
}

.account-card {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    border-top: 1px solid #eaeae5;
    border-left: 1px solid #eaeae5;
    background: white;
}

.account-field {
    min-height: 90px;
    padding: 22px;
    border-right: 1px solid #eaeae5;
    border-bottom: 1px solid #eaeae5;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 12px;
}

.account-field span {
    color: #999992;
    font-size: 9px;
    letter-spacing: 1.5px;
    font-weight: 600;
}

.account-field strong {
    color: #252525;
    font-size: 12px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

/* Footer */

.dashboard-footer {
    margin-top: 65px;
    padding-top: 22px;
    border-top: 1px solid #eaeae5;
    display: flex;
    justify-content: space-between;
    color: #999992;
    font-size: 8px;
    font-weight: 500;
    letter-spacing: 1px;
}

/* Responsive */

@media (max-width: 760px) {
    .page-content {
        padding: 30px 20px;
    }

    .page-heading {
        align-items: flex-start;
    }

    .page-heading h1 {
        font-size: 26px;
    }

    .date-label {
        display: none;
    }

    .welcome-panel {
        min-height: 260px;
        padding: 30px;
    }

    .panel-mark {
        right: 0;
        opacity: .7;
    }

    .account-card {
        grid-template-columns: 1fr;
    }

    .dashboard-footer {
        gap: 15px;
        flex-wrap: wrap;
    }
}
</style>
