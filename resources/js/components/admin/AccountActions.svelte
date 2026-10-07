<script lang="ts" module>
    export type ManagedAccount = {
        uuid: string;
        name: string;
        status: 'invited' | 'active' | 'suspended';
        /** Mono line under the name in the phone sheet. */
        subtitle?: string | null;
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { DropdownMenu } from 'bits-ui';
    import ActionSheet from '@/components/ActionSheet.svelte';
    import Button from '@/components/Button.svelte';
    import { destroy, reactivate, resendLogin, resetLink, suspend } from '@/routes/admin/accounts';
    import Block from '~icons/ms/block';
    import Delete from '~icons/ms/delete';
    import DeleteForever from '~icons/ms/delete-forever';
    import Edit from '~icons/ms/edit';
    import ForwardToInbox from '~icons/ms/forward-to-inbox';
    import LockReset from '~icons/ms/lock-reset';
    import MoreHoriz from '~icons/ms/more-horiz';
    import MoreVert from '~icons/ms/more-vert';
    import Refresh from '~icons/ms/refresh';

    type Props = {
        account: ManagedAccount;
        /** "contractor" or "service rep" — used in the delete copy. */
        noun: string;
        /** How many submitted inspections stay on record after delete. */
        inspectionsCount?: number;
        onEdit: () => void;
        /** Desktop: the table shows the inline confirmation row (AD-06). */
        onRequestDelete: () => void;
    };

    let { account, noun, inspectionsCount = 0, onEdit, onRequestDelete }: Props = $props();

    let sheetOpen = $state(false);
    let confirming = $state(false);

    const suspended = $derived(account.status === 'suspended');

    function post(url: string): void {
        sheetOpen = false;
        router.post(url, {}, { preserveScroll: true });
    }

    function suspendInstead(): void {
        confirming = false;
        post(suspend.url(account.uuid));
    }

    function remove(): void {
        sheetOpen = false;
        router.delete(destroy.url(account.uuid), { preserveScroll: true });
    }

    const menuItem =
        'flex h-10 cursor-pointer items-center gap-2.5 rounded-lg px-2.5 outline-none data-highlighted:bg-sf [&_svg]:size-[19px]';
    const sheetItem =
        'flex h-[54px] w-full items-center gap-3.5 rounded-xl px-3.5 text-left hover:bg-sf2 [&_svg]:size-6';
</script>

<!-- Desktop row menu (AD-06) -->
<DropdownMenu.Root>
    <DropdownMenu.Trigger
        class="hidden size-9 items-center justify-center rounded-lg text-mut hover:bg-sf2 hover:text-ink data-[state=open]:bg-sf2 data-[state=open]:text-ink lg:flex"
        aria-label="Actions for {account.name}"
    >
        <MoreHoriz class="size-[22px]" />
    </DropdownMenu.Trigger>
    <DropdownMenu.Portal>
        <DropdownMenu.Content
            align="end"
            sideOffset={4}
            class="z-50 flex w-[260px] flex-col rounded-[14px] border border-line bg-sf2 p-1.5 text-sm font-semibold text-ink shadow-[0_16px_40px_rgba(0,0,0,.25)] outline-none"
        >
            <DropdownMenu.Item class={menuItem} onSelect={onEdit}><Edit />Edit details</DropdownMenu.Item>
            <DropdownMenu.Item class={menuItem} onSelect={() => post(resendLogin.url(account.uuid))}>
                <ForwardToInbox />Resend login details
            </DropdownMenu.Item>
            <DropdownMenu.Item class={menuItem} onSelect={() => post(resetLink.url(account.uuid))}>
                <LockReset />Reset password
            </DropdownMenu.Item>
            {#if suspended}
                <DropdownMenu.Item class={menuItem} onSelect={() => post(reactivate.url(account.uuid))}>
                    <Refresh />Reactivate
                </DropdownMenu.Item>
            {:else}
                <DropdownMenu.Item class={menuItem} onSelect={() => post(suspend.url(account.uuid))}>
                    <Block />Suspend
                </DropdownMenu.Item>
            {/if}
            <DropdownMenu.Separator class="my-1 h-px bg-line" />
            <DropdownMenu.Item class={[menuItem, 'text-bad']} onSelect={onRequestDelete}>
                <Delete />Delete…
            </DropdownMenu.Item>
        </DropdownMenu.Content>
    </DropdownMenu.Portal>
</DropdownMenu.Root>

<!-- Phone action sheet (AM-05) with an in-sheet delete step (AM-06) -->
<button
    type="button"
    class="flex size-11 items-center justify-center rounded-xl text-mut hover:bg-sf2 lg:hidden"
    aria-label="Actions for {account.name}"
    onclick={() => {
        confirming = false;
        sheetOpen = true;
    }}
>
    <MoreVert class="size-6" />
</button>

<ActionSheet
    bind:open={sheetOpen}
    title={confirming ? `Delete ${account.name}?` : account.name}
    subtitle={account.subtitle ?? undefined}
    bare={confirming}
>
    {#if confirming}
        <div class="flex flex-col gap-4 px-5 pt-2 pb-2">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-bad-bg text-bad">
                <DeleteForever class="size-[30px]" />
            </span>
            <div class="flex flex-col gap-2">
                <b class="text-[21px]">Delete {account.name}?</b>
                <p class="text-[15px] leading-normal text-mut">
                    Their login is removed.
                    {#if inspectionsCount > 0}
                        The {inspectionsCount} inspection{inspectionsCount === 1 ? '' : 's'} they submitted stay on record.
                    {:else}
                        Anything they submitted stays on record.
                    {/if}
                    This can't be undone.
                </p>
            </div>
            {#if !suspended}
                <div class="rounded-xl bg-sf2 px-3.5 py-3 text-sm leading-[1.45]">
                    Want to keep the account?
                    <button type="button" class="font-bold text-brand underline-offset-2 hover:underline" onclick={suspendInstead}>
                        Suspend instead
                    </button>
                </div>
            {/if}
            <div class="flex flex-col gap-2">
                <Button block class="bg-bad text-white hover:brightness-110" onclick={remove}>Delete {noun}</Button>
                <Button variant="ghost" block size="md" onclick={() => (confirming = false)}>Cancel</Button>
            </div>
        </div>
    {:else}
        <div class="flex flex-col p-2 text-base font-semibold">
            <button
                type="button"
                class={sheetItem}
                onclick={() => {
                    sheetOpen = false;
                    onEdit();
                }}
            >
                <Edit />Edit details
            </button>
            <button type="button" class={sheetItem} onclick={() => post(resendLogin.url(account.uuid))}>
                <ForwardToInbox />Resend login details
            </button>
            <button type="button" class={sheetItem} onclick={() => post(resetLink.url(account.uuid))}>
                <LockReset />Reset password
            </button>
            {#if suspended}
                <button type="button" class={sheetItem} onclick={() => post(reactivate.url(account.uuid))}>
                    <Refresh />Reactivate
                </button>
            {:else}
                <button type="button" class={sheetItem} onclick={() => post(suspend.url(account.uuid))}>
                    <Block />Suspend
                </button>
            {/if}
            <button type="button" class={[sheetItem, 'text-bad']} onclick={() => (confirming = true)}>
                <Delete />Delete {noun}
            </button>
        </div>
    {/if}
</ActionSheet>
