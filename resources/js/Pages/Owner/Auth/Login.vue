
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('owner.authenticate'))
}
</script>

<template>
    <Head title="Owner Login — MOXXER" />

    <main class="auth-page">
        <Link href="/" class="brand">
            MOXXER<span>.</span>
        </Link>

        <section class="auth-container">
            <div class="auth-header">
                <span class="eyebrow">MOXXER / OWNER PORTAL</span>
                <h1>Welcome back.</h1>
                <p>Sign in to continue to your workspace.</p>
            </div>

            <form @submit.prevent="submit" class="auth-form">
                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="name@example.com"
                        autocomplete="username"
                        required
                        autofocus
                    />
                    <span v-if="form.errors.email" class="error">
                        {{ form.errors.email }}
                    </span>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    />
                    <span v-if="form.errors.password" class="error">
                        {{ form.errors.password }}
                    </span>
                </div>

                <label class="remember">
                    <input v-model="form.remember" type="checkbox" />
                    <span>Remember me</span>
                </label>

                <button type="submit" :disabled="form.processing">
                    <span>{{ form.processing ? 'Signing in...' : 'Sign in' }}</span>
                    <span class="button-arrow">→</span>
                </button>
            </form>

            <div class="auth-bottom">
                <span>Restricted access</span>
                <span>OWNER ACCOUNT ONLY</span>
            </div>
        </section>

        <footer class="page-footer">
            <span>© {{ new Date().getFullYear() }} MOXXER</span>
            <span>TIMELESS BY DESIGN.</span>
        </footer>
    </main>
</template>

<style scoped>
.auth-page {
    min-height: 100vh;
    padding: 34px 7%;
    background: #f5f5f2;
    color: #171717;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.brand {
    align-self: flex-start;
    color: #171717;
    text-decoration: none;
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -1.5px;
}

.brand span {
    color: #92928d;
}

.auth-container {
    width: 100%;
    max-width: 420px;
    margin: auto;
    padding: 42px;
    background: #fff;
    border: 1px solid #eaeae6;
    border-radius: 24px;
}

.auth-header {
    margin-bottom: 34px;
}

.eyebrow {
    color: #999;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1.5px;
}

.auth-header h1 {
    margin: 20px 0 9px;
    font-size: 34px;
    line-height: 1.15;
    font-weight: 600;
    letter-spacing: -1.6px;
}

.auth-header p {
    margin: 0;
    color: #888;
    font-size: 12px;
    line-height: 1.7;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field label {
    font-size: 11px;
    font-weight: 600;
    color: #444;
}

.field input {
    width: 100%;
    height: 46px;
    padding: 0 14px;
    background: #f7f7f5;
    border: 1px solid #e9e9e5;
    border-radius: 11px;
    outline: none;
    font-size: 12px;
    color: #171717;
    transition: border-color .2s, background .2s, box-shadow .2s;
}

.field input::placeholder {
    color: #aaa;
}

.field input:focus {
    background: #fff;
    border-color: #777;
    box-shadow: 0 0 0 3px rgba(0, 0, 0, .04);
}

.error {
    color: #b42318;
    font-size: 11px;
}

.remember {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #777;
    font-size: 11px;
    cursor: pointer;
}

.remember input {
    width: 14px;
    height: 14px;
    accent-color: #171717;
    cursor: pointer;
}

.auth-form button {
    width: 100%;
    height: 48px;
    margin-top: 3px;
    padding: 0 17px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: none;
    border-radius: 13px;
    background: #171717;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s, transform .2s;
}

.auth-form button:hover:not(:disabled) {
    background: #363636;
    transform: translateY(-1px);
}

.auth-form button:active:not(:disabled) {
    transform: scale(.985);
}

.auth-form button:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.button-arrow {
    font-size: 17px;
    font-weight: 400;
}

.auth-bottom {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    color: #aaa;
    font-size: 8px;
    letter-spacing: .8px;
}

.page-footer {
    width: 100%;
    display: flex;
    justify-content: space-between;
    color: #999;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1px;
}

@media (max-width: 550px) {
    .auth-page {
        padding: 25px 20px;
    }

    .auth-container {
        padding: 30px 24px;
        border-radius: 20px;
    }

    .auth-header h1 {
        font-size: 30px;
    }

    .page-footer {
        font-size: 8px;
    }
}
</style>
