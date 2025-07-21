<!--
South African Theological Seminary
-->
<svelte:options immutable={true} />

<script lang="ts">
    import type { Store } from '../lib/store';

    export let store: Store;
</script>

<button
    type="button"
    class="local-satsmail-back-button btn btn-secondary"
    title={$store.strings.messagelist}
    on:click={() => store.navigateToList()}
>
    <i class="fa fa fa-fw fa-arrow-left" />
</button>

<style>
    .local-satsmail-back-button {
        margin-right: 13px;
    }
</style>

