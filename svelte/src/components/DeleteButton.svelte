<!--
South African Theological Seminary
-->
<svelte:options immutable={true} />

<script lang="ts">
    import { DeletedStatus } from '../lib/state';
    import type { Store } from '../lib/store';

    export let store: Store;
    export let bottom = false;
</script>

<button
    type="button"
    class="local-mail-delete-button btn"
    class:btn-secondary={!bottom}
    class:btn-light={bottom}
    class:disabled={!$store.selectedMessages.size}
    disabled={!$store.selectedMessages.size}
    title={$store.strings.movetotrash}
    on:click={() =>
        store.setDeleted(Array.from($store.selectedMessages.keys()), DeletedStatus.Deleted, true)}
>
    <i class="fa fa-fw fa-trash" />
</button>

