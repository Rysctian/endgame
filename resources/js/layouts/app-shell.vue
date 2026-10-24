<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Sheet,
    SheetTrigger,
    SheetContent,
    SheetTitle,
    SheetDescription,
    SheetClose,
} from '@/components/ui/sheet';
import { Menu, X, ChevronRight } from '@lucide/vue';
import AppSidebar from '@/components/app-sidebar.vue';
import type { SharedProps } from '@/features/user-access/types';
const props = defineProps<{
    title: string;
    workspace?: { id: number; name: string };
    project?: { id: number; name: string; key: string };
    canProjects?: boolean;
    projectNav?: {
        overview: boolean;
        tickets: boolean;
        workflow: boolean;
        active: string;
    };
}>();
const page = usePage<SharedProps>();
const mobileOpen = ref(false);
watch(
    () => page.url,
    () => (mobileOpen.value = false),
);

let desktop: MediaQueryList | undefined;
function restoreNavigationFocus(event: Event) {
    event.preventDefault();
    nextTick(() => {
        const target = desktop?.matches
            ? document.getElementById('main-content')
            : document.getElementById('navigation-trigger');
        target?.focus();
    });
}
function closeOnDesktop(event: MediaQueryListEvent) {
    if (event.matches) mobileOpen.value = false;
}
onMounted(() => {
    desktop = window.matchMedia('(min-width: 1024px)');
    desktop.addEventListener('change', closeOnDesktop);
});
onBeforeUnmount(() => desktop?.removeEventListener('change', closeOnDesktop));
</script>
<template>
    <Head :title="title" />
    <div class="app-shell min-h-dvh bg-background text-foreground">
        <a href="#main-content" class="skip-link">Skip to content</a>
        <aside
            class="shell-sidebar fixed inset-y-0 left-0 z-30 hidden w-[var(--workspace-sidebar-width)] flex-col lg:flex"
            aria-label="Sidebar"
        >
            <AppSidebar
                :workspace="workspace"
                :project="project"
                :can-projects="canProjects"
                :project-nav="projectNav"
            />
        </aside>
        <div class="min-w-0 lg:ml-[var(--workspace-sidebar-width)]">
            <header
                class="shell-toolbar sticky top-0 z-20 flex h-[var(--workspace-topbar-height)] items-center gap-3 px-4 sm:px-7"
            >
                <Sheet v-model:open="mobileOpen"
                    ><SheetTrigger as-child
                        ><button
                            id="navigation-trigger"
                            class="icon-control lg:hidden"
                            aria-label="Open navigation"
                        >
                            <Menu class="size-4" /></button></SheetTrigger
                    ><SheetContent
                        :show-close-button="false"
                        @close-auto-focus="restoreNavigationFocus"
                        side="left"
                        class="mobile-sidebar fixed inset-y-0 left-0 z-50 w-[min(18rem,calc(100vw-2.5rem))] border-r bg-sidebar lg:hidden"
                        ><SheetTitle class="sr-only">Navigation</SheetTitle
                        ><SheetDescription class="sr-only"
                            >Workspaces, projects and account
                            settings.</SheetDescription
                        ><SheetClose
                            class="icon-control absolute right-3 top-4"
                            aria-label="Close navigation"
                            ><X class="size-4" /></SheetClose
                        ><AppSidebar
                            :workspace="workspace"
                            :project="project"
                            :can-projects="canProjects"
                            :project-nav="projectNav"
                            @navigate="mobileOpen = false" /></SheetContent
                ></Sheet>
                <nav
                    aria-label="Breadcrumb"
                    class="flex min-w-0 items-center gap-2 text-xs"
                >
                    <slot name="breadcrumbs"
                        ><Link
                            href="/workspaces"
                            aria-label="All workspaces"
                            class="hidden text-muted-foreground hover:text-foreground sm:block"
                            >Workspace</Link
                        ><ChevronRight
                            class="hidden size-3 text-muted-foreground/50 sm:block"
                        /><Link
                            v-if="workspace"
                            :href="'/workspaces/' + workspace.id"
                            aria-label="Current workspace"
                            class="max-w-48 truncate text-muted-foreground hover:text-foreground"
                            >{{ workspace.name }}</Link
                        ><ChevronRight
                            v-if="workspace"
                            class="size-3 shrink-0 text-muted-foreground/50"
                        /><span class="truncate font-medium">{{
                            project?.name ?? title
                        }}</span></slot
                    >
                </nav>
                <Link
                    href="/profile"
                    aria-label="Your profile"
                    class="avatar-token ml-auto size-7 shrink-0"
                    >{{
                        page.props.auth.user?.name.slice(0, 2).toUpperCase()
                    }}</Link
                >
            </header>
            <main id="main-content" tabindex="-1" class="min-w-0 outline-none">
                <p
                    v-if="
                        page.props.status &&
                        !page.component.startsWith('tickets/')
                    "
                    role="status"
                    class="sr-only"
                >
                    {{ page.props.status }}
                </p>
                <slot />
            </main>
        </div>
    </div>
</template>
