<!--
South African Theological Seminary
-->
<svelte:options immutable={true} />

<script lang="ts">
    import type { Store } from '../lib/store';
    import ArchiveButton from './ArchiveButton.svelte';
    import DeleteButton from './DeleteButton.svelte';
    import DeleteForeverButton from './DeleteForeverButton.svelte';
    import LabelsButton from './LabelsButton.svelte';
    import MoreActionsButton from './MoreActionsButton.svelte';
    import PagingButtons from './PagingButtons.svelte';
    import RestoreButton from './RestoreButton.svelte';
    import UnarchiveButton from './UnarchiveButton.svelte';

    export let store: Store;
</script>

<div role="toolbar" class="local-satsmail-toolbar px-2 py-3 bg-light border-top border-bottom">
    <div class="d-flex justify-content-around" role="group">
        {#if $store.params.tray == 'trash'}
            <RestoreButton {store} bottom={true} />
            <DeleteForeverButton {store} bottom={true} />
        {:else if $store.params.tray == 'archive'}
            <UnarchiveButton {store} bottom={true} />
            <DeleteButton {store} bottom={true} />
        {:else}
            <LabelsButton {store} bottom={true} />
            <ArchiveButton {store} bottom={true} />
            <DeleteButton {store} bottom={true} />
        {/if}
        <MoreActionsButton {store} bottom={true} />
        <PagingButtons {store} bottom={true} />
    </div>
</div>

<style>
    .local-satsmail-toolbar {
        z-index: 1020;
        position: sticky;
        bottom: 0;
    }
</style>

