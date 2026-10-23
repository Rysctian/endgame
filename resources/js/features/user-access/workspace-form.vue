<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FormDialog from './form-dialog.vue';
import FormErrors from './form-errors.vue';
import type { Workspace } from './types';
const props = defineProps<{ workspace?: Workspace }>();
const open = defineModel<boolean>('open', { default: false });
const form = useForm({ name: '', slug: '' });
watch(open, (value) => {
    if (value) {
        form.name = props.workspace?.name ?? '';
        form.slug = props.workspace?.slug ?? '';
        form.clearErrors();
    }
});
function submit() {
    const options = {
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    };
    if (props.workspace)
        form.patch('/workspaces/' + props.workspace.id, options);
    else form.post('/workspaces', options);
}
</script>
<template>
    <FormDialog
        v-model:open="open"
        :title="workspace ? 'Edit workspace' : 'Create workspace'"
        ><form class="space-y-4" @submit.prevent="submit">
            <FormErrors :errors="form.errors" />
            <div class="space-y-2">
                <label for="workspace-name">Workspace name</label
                ><input
                    id="workspace-name"
                    v-model="form.name"
                    required
                    maxlength="255"
                />
            </div>
            <div class="space-y-2">
                <label for="slug">Slug</label
                ><input
                    id="slug"
                    v-model="form.slug"
                    required
                    maxlength="100"
                    pattern="[a-z0-9]+(-[a-z0-9]+)*"
                />
                <p class="text-xs text-muted-foreground">
                    Lowercase letters, numbers and hyphens.
                </p>
            </div>
            <Button type="submit" :disabled="form.processing"
                >Save workspace</Button
            >
        </form></FormDialog
    >
</template>
