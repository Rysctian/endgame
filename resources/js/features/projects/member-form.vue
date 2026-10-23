<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FormDialog from '@/features/user-access/form-dialog.vue';
import FormErrors from '@/features/user-access/form-errors.vue';
import OptionSelect from './option-select.vue';
import type { Option } from './types';
const props = defineProps<{ url: string; options: Option[]; search: string }>();
const open = defineModel<boolean>('open', { default: false });
const searchValue = ref(props.search);
const searching = ref(false);
const form = useForm({ user_id: '' });
watch(open, (value) => {
    if (value) {
        form.reset();
        form.clearErrors();
        searchValue.value = props.search;
    }
});
function searchMembers() {
    searching.value = true;
    form.user_id = '';
    router.get(
        props.url,
        { member_search: searchValue.value },
        {
            only: ['memberOptions', 'memberSearch', 'can'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                searching.value = false;
            },
        },
    );
}
function submit() {
    form.post(props.url + '/members', {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>
<template>
    <FormDialog v-model:open="open" title="Add project member">
        <form class="mb-4 flex items-end gap-2" @submit.prevent="searchMembers">
            <div class="min-w-0 flex-1 space-y-2">
                <label for="member-search">Search workspace members</label
                ><input
                    id="member-search"
                    v-model="searchValue"
                    maxlength="255"
                />
            </div>
            <Button variant="outline" type="submit" :disabled="searching"
                >Search</Button
            >
        </form>
        <form class="space-y-4" @submit.prevent="submit">
            <FormErrors :errors="form.errors" />
            <div class="space-y-2">
                <label for="project-member">Workspace member</label
                ><OptionSelect
                    id="project-member"
                    v-model="form.user_id"
                    :options="options"
                    label="Workspace member"
                    placeholder="Choose a member"
                />
            </div>
            <p v-if="!options.length" class="text-sm text-muted-foreground">
                No eligible members found.
            </p>
            <p class="text-xs text-muted-foreground">
                Showing up to 50 matches. Search to narrow the results.
            </p>
            <Button
                type="submit"
                :disabled="form.processing || searching || !form.user_id"
                >Save member</Button
            >
        </form>
    </FormDialog>
</template>
