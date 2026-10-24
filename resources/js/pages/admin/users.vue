<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app-layout.vue';
import PaginationLinks from '@/features/user-access/pagination-links.vue';
import ActivityList from '@/features/user-access/activity-list.vue';
import { Button } from '@/components/ui/button';
import type {
    Account,
    Activity,
    Paginated,
} from '@/features/user-access/types';
defineProps<{ users: Paginated<Account>; activities: Paginated<Activity> }>();
const form = useForm({ status: 'active' });
function change(user: Account) {
    form.status = user.status === 'active' ? 'suspended' : 'active';
    form.patch('/admin/users/' + user.id, { preserveScroll: true });
}
</script>
<template>
    <AppLayout title="Accounts"
        ><div class="flex items-center justify-between border-b pb-5">
            <div>
                <p class="mb-2 text-xs text-muted-foreground">
                    System administration
                </p>
                <h1>Accounts</h1>
            </div>
            <Badge variant="outline" class="state-badge"
                >{{ users.total }} accounts</Badge
            >
        </div>
        <section class="surface">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th>Account</th>
                            <th>Status</th>
                            <th>Verification</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="user in users.data" :key="user.id">
                            <td>
                                <p class="font-medium">{{ user.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ user.email }}
                                </p>
                                <p
                                    v-if="user.is_system_admin"
                                    class="text-xs text-muted-foreground"
                                >
                                    System Admin
                                </p>
                            </td>
                            <td>
                                <span
                                    class="state-badge"
                                    :data-state="user.status"
                                    >{{ user.status }}</span
                                >
                            </td>
                            <td>
                                {{
                                    user.email_verified_at
                                        ? 'Verified'
                                        : 'Unverified'
                                }}
                            </td>
                            <td class="text-right">
                                <Button
                                    v-if="!user.is_system_admin"
                                    variant="outline"
                                    :disabled="form.processing"
                                    @click="change(user)"
                                    >{{
                                        user.status === 'active'
                                            ? 'Suspend'
                                            : 'Reactivate'
                                    }}</Button
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-5 pb-4">
                <PaginationLinks :links="users.links" />
            </div>
        </section>
        <ActivityList :activities="activities"
    /></AppLayout>
</template>
