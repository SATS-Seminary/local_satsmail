<!--
South African Theological Seminary
-->
<svelte:options immutable={true} />

<script lang="ts">
    import type { Store } from '../lib/store';

    export let store: Store;
    export let bottom = false;
</script>

<button
    type="button"
    class="local-satsmail-unarchive-button btn"
    class:btn-secondary={!bottom}
    class:btn-light={bottom}
    class:disabled={!$store.selectedMessages.size}
    disabled={!$store.selectedMessages.size}
    title={$store.strings.unarchive}
    on:click={() => store.setArchived(Array.from($store.selectedMessages.keys()), false, true)}
>
    <i class="fa fa-fw fa-inbox" />
</button>
