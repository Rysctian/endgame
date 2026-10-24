<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Layers2, ArrowUpRight, Check } from '@lucide/vue';
import type { SharedProps } from '@/features/user-access/types';
defineProps<{ title: string }>();
const page = usePage<SharedProps>();
</script>
<template>
    <Head :title="title" />
    <main
        class="auth-page grid min-h-dvh bg-background lg:grid-cols-[1fr_1.05fr]"
    >
        <aside
            class="auth-story relative hidden flex-col justify-between overflow-hidden border-r bg-sidebar p-12 lg:flex"
        >
            <Link
                href="/"
                class="flex w-fit items-center gap-2.5 text-lg font-semibold tracking-tight"
                ><span class="brand-mark"><Layers2 class="size-4" /></span
                >Endgame</Link
            >
            <div class="relative z-10 max-w-lg">
                <p
                    class="mb-6 text-xs font-medium uppercase tracking-[0.16em] text-primary"
                >
                    Clarity, from start to done.
                </p>
                <h2
                    class="text-5xl font-semibold leading-[1.1] tracking-[-0.04em]"
                >
                    Good work.<br /><span class="text-muted-foreground"
                        >Beautifully in sync.</span
                    >
                </h2>
                <p
                    class="mt-6 max-w-sm text-base leading-7 text-muted-foreground"
                >
                    A considered space for your team, your projects, and the
                    details that move them forward.
                </p>
                <div
                    class="auth-preview mt-10 max-w-sm rounded-2xl border bg-card p-5"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-xs font-medium"
                            >From a little focus</span
                        ><span class="avatar-token size-6"
                            ><Check class="size-3"
                        /></span>
                    </div>
                    <div class="flex gap-2" aria-hidden="true">
                        <span class="h-1 flex-1 rounded-full bg-primary" /><span
                            class="h-1 flex-1 rounded-full bg-primary/50"
                        /><span class="h-1 flex-1 rounded-full bg-primary/20" />
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        to a lot of progress.
                    </p>
                </div>
            </div>
            <p class="relative z-10 text-xs text-muted-foreground">
                Made for the way teams work.
            </p>
            <span class="auth-orbit" aria-hidden="true" />
        </aside>
        <section class="flex min-w-0 flex-col">
            <div class="flex items-center justify-between px-6 py-6 sm:px-10">
                <Link
                    href="/"
                    class="flex items-center gap-2 font-semibold tracking-tight lg:invisible"
                    ><span class="brand-mark size-7"
                        ><Layers2 class="size-3.5" /></span
                    >Endgame</Link
                ><Link
                    href="/workspaces"
                    class="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                    >Your workspace<ArrowUpRight class="size-3.5"
                /></Link>
            </div>
            <div class="flex flex-1 items-center justify-center px-6 py-10">
                <div class="auth-form w-full max-w-[370px] space-y-6">
                    <div class="mb-8">
                        <p
                            class="mb-2 text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground"
                        >
                            Endgame account
                        </p>
                        <h1
                            class="text-[28px] font-semibold leading-tight tracking-[-0.025em]"
                        >
                            {{ title }}
                        </h1>
                    </div>
                    <p
                        v-if="page.props.status"
                        role="status"
                        class="notice-success"
                    >
                        {{ page.props.status }}
                    </p>
                    <slot />
                </div>
            </div>
            <p class="px-6 pb-7 text-center text-[10px] text-muted-foreground">
                A calmer place to get things done.
            </p>
        </section>
    </main>
</template>
