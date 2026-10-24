<script setup lang="ts">
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FormDialog from '@/features/user-access/form-dialog.vue';
import FormErrors from '@/features/user-access/form-errors.vue';
import type { ProjectFormData } from './types';
const props = defineProps<{
    workspaceId: number;
    projectId?: number;
    contract: ProjectFormData;
}>();
const open = defineModel<boolean>('open', { default: false });
const form = useForm({ name: '', key: '', description: '' });
watch(open, (value) => {
    if (value) {
        form.name = props.contract.defaults.name;
        form.key = props.contract.defaults.key;
        form.description = props.contract.defaults.description ?? '';
        form.clearErrors();
    }
});
function submit() {
    const url = '/workspaces/' + props.workspaceId + '/projects';
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };
    if (props.projectId) {
        form.transform((data) => ({
            name: data.name,
            description: data.description,
        })).patch(url + '/' + props.projectId, options);
    } else {
        form.transform((data) => data).post(url, options);
    }
}
</script>
<template>
    <FormDialog
        v-model:open="open"
        :title="projectId ? 'Edit project' : 'Create project'"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <FormErrors :errors="form.errors" />
            <div class="space-y-2">
                <label for="project-name">{{
                    contract.fieldLabels.name
                }}</label>
                <Input
                    id="project-name"
                    v-model="form.name"
                    :required="contract.fieldStates.name.required"
                    :readonly="contract.fieldStates.name.readOnly"
                    :maxlength="contract.validationRules.name.maxLength"
                />
            </div>
            <div class="space-y-2">
                <label for="project-key">{{ contract.fieldLabels.key }}</label>
                <Input
                    id="project-key"
                    :model-value="form.key"
                    @update:model-value="
                        (value) => (form.key = String(value).toUpperCase())
                    "
                    :required="contract.fieldStates.key.required"
                    :readonly="contract.fieldStates.key.readOnly"
                    :minlength="contract.validationRules.key.minLength"
                    :maxlength="contract.validationRules.key.maxLength"
                    :pattern="contract.validationRules.key.pattern"
                />
            </div>
            <div class="space-y-2">
                <label for="project-description">{{
                    contract.fieldLabels.description
                }}</label>
                <Textarea
                    id="project-description"
                    v-model="form.description"
                    rows="4"
                    :required="contract.fieldStates.description.required"
                    :readonly="contract.fieldStates.description.readOnly"
                    :maxlength="contract.validationRules.description.maxLength"
                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                />
            </div>
            <Button type="submit" :disabled="form.processing"
                >Save project</Button
            >
        </form>
    </FormDialog>
</template>
