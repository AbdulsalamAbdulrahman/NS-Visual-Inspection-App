<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import Call from '~icons/ms/call';
    import Mail from '~icons/ms/mail';

    const nsd = $derived(page.props.nsd);
    const tel = $derived(`tel:${nsd.phone.replace(/[^\d+]/g, '')}`);
</script>

<!-- AU-04 "No access to that email?" -->
<div class="flex flex-col gap-2.5 rounded-2xl border border-line bg-sf p-[18px]">
    <div class="text-base font-bold">No access to that email?</div>
    <p class="text-[15px] leading-[1.45] text-mut">
        Ask the New Service Department admin to reset your password. They'll email you new login details.
    </p>
    <div class="flex gap-2">
        <a
            href={tel}
            class="flex h-12 flex-1 items-center justify-center gap-1.5 rounded-xl bg-sf2 text-[15px] font-bold text-ink no-underline hover:brightness-95"
        >
            <Call class="size-5" />Call NSD
        </a>
        <a
            href="mailto:{nsd.email}"
            class="flex h-12 flex-1 items-center justify-center gap-1.5 rounded-xl bg-sf2 text-[15px] font-bold text-ink no-underline hover:brightness-95"
        >
            <Mail class="size-5" />Email NSD
        </a>
    </div>
</div>
