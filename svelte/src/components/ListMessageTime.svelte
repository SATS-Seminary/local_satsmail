<!--
South African Theological Seminary
-->
<svelte:options immutable={true} />

<script lang="ts">
    import { ViewportSize, type MessageSummary } from '../lib/state';
    import type { Store } from '../lib/store';

    export let message: MessageSummary;
    export let store: Store;
</script>

<span
    class="local-satsmail-list-message-time d-shrink-0 text-nowrap text-right"
    class:local-satsmail-list-message-time-md={$store.viewportSize >= ViewportSize.MD}
    title={message.fulltime}
>
    {message.shorttime}
</span>

<style>
    .local-satsmail-list-message-time-md {
        min-width: 5rem;
    }
</style>

