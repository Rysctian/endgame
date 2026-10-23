<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { Button } from '@/components/ui/button';
</script>

<template>
    <Head title="Welcome" />
    <main class="flex min-h-screen items-center justify-center bg-background p-8 text-foreground">
        <div class="max-w-lg space-y-6">
            <Check class="size-8" aria-hidden="true" />
            <h1 class="text-4xl font-semibold tracking-tight">ENDGAME</h1>
            <p class="text-muted-foreground">Your Laravel, Vue, and Inertia foundation is ready.</p>
            <Button as-child>
                <a href="https://laravel.com/docs/13.x">Laravel documentation</a>
            </Button>
        </div>
    </main>
</template>
