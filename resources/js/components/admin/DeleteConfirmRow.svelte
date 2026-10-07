<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import { destroy, suspend } from '@/routes/admin/accounts';
    import DeleteForever from '~icons/ms/delete-forever';

    type Props = {
        uuid: string;
        name: string;
        noun: string;
        inspectionsCount?: number;
        suspended: boolean;
        onCancel: () => void;
    };

    let { uuid, name, noun, inspectionsCount = 0, suspended, onCancel }: Props = $props();

    let busy = $state(false);

    function run(action: 'delete' | 'suspend'): void {
        busy = true;
        const options = { preserveScroll: true, onFinish: () => onCancel() };

        if (action === 'delete') {
            router.delete(destroy.url(uuid), options);
        } else {
            router.post(suspend.url(uuid), {}, options);
        }
    }
</script>

<!-- AD-06: the confirmation opens in the table itself, never a browser confirm(). -->
<div role="alertdialog" aria-label="Delete {name}?" class="flex items-center gap-3.5 border-b border-line bg-bad-bg px-5 py-4">
    <DeleteForever class="size-6 flex-none text-bad" />
    <div class="flex flex-1 flex-col gap-0.5">
        <b class="text-[15px]">Delete {name}?</b>
        <span class="text-sm text-ink">
            Their login is removed.
            {#if inspectionsCount > 0}
                The {inspectionsCount} inspection{inspectionsCount === 1 ? '' : 's'} they submitted stay on record.
            {/if}
            This can't be undone.
            {#if !suspended}
                <button type="button" class="font-bold text-brand hover:underline" disabled={busy} onclick={() => run('suspend')}>
                    Suspend instead
                </button>
            {/if}
        </span>
    </div>
    <Button variant="ghost" size="xs" class="bg-sf" disabled={busy} onclick={onCancel}>Cancel</Button>
    <Button size="xs" class="bg-bad text-bg hover:brightness-110" disabled={busy} onclick={() => run('delete')}>
        Delete {noun}
    </Button>
</div>
