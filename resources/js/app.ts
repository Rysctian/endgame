import '../css/app.css';
import { createApp, h, Fragment, type DefineComponent } from 'vue';
import { router } from '@inertiajs/vue3';
import { Toaster, toast } from 'vue-sonner';
import 'vue-sonner/style.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    title: (title) => (title ? `${title} - ENDGAME` : 'ENDGAME'),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({
            render: () =>
                h(Fragment, [
                    h(App, props),
                    h(Toaster, {
                        closeButton: true,
                        position: 'bottom-right',
                        toastOptions: { class: 'endgame-toast' },
                    }),
                ]),
        })
            .use(plugin)
            .mount(el);
    },
});

document.addEventListener('keydown', () => {
    document.documentElement.dataset.input = 'keyboard';
});
document.addEventListener('pointerdown', () => {
    document.documentElement.dataset.input = 'pointer';
});

let submission: { local: boolean } | null = null;
router.on('start', (event) => {
    const visit = event.detail.visit;
    submission =
        visit.method === 'get'
            ? null
            : { local: /\/(tickets|workflow)(\/|$)/.test(visit.url.pathname) };
});
router.on('success', (event) => {
    if (
        submission &&
        !submission.local &&
        typeof event.detail.page.props.status === 'string'
    )
        toast.success(event.detail.page.props.status);
    submission = null;
});
router.on('error', (event) => {
    if (submission && !submission.local)
        toast.error(
            Object.values(event.detail.errors)[0] ||
                'Unable to save. Check the highlighted fields.',
        );
    submission = null;
});
