<!--
South African Theological Seminary
-->
<svelte:options immutable={true} />

<script lang="ts">
    import type { Store } from '../lib/store';
    export let store: Store;

    const defaultValues: ReadonlyArray<number> = [5, 10, 20, 50, 100];

    $: values = defaultValues.includes($store.preferences.perpage)
        ? defaultValues
        : defaultValues.concat([$store.preferences.perpage]).sort((a, b) => a - b);

    let selected: number = $store.preferences.perpage;
</script>

<div class="form-inline justify-content-end mt-3">
    <div class="form-group">
        <label for="local-satsmail-perpage-select">{$store.strings.messagesperpage}:</label>
        <select
            id="local-satsmail-perpage-select"
            class="local-satsmail-perpage-select-select custom-select"
            bind:value={selected}
            on:change={() => store.savePreferences({ perpage: selected })}
        >
            {#each values as value}
                <option {value}>{value}</option>
            {/each}
        </select>
    </div>
</div>

<style>
    .local-satsmail-perpage-select-select {
        width: auto;
        margin-left: 0.5rem;
    }
</style>

