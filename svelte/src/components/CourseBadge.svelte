<!--
South African Theological Seminary
-->
<svelte:options immutable={true} />

<script lang="ts">
    import { truncate } from '../actions/truncate';
    import type { Course, Settings } from '../lib/state';
    import { formatCourseName } from '../lib/utils';

    export let course: Course;
    export let settings: Settings;

    $: text = formatCourseName(course, settings.coursebadges);
    $: length = settings.coursebadgeslength || 20;
</script>

{#if ['shortname', 'fullname'].includes(settings.coursebadges)}
    <span
        class="local-satsmail-course-badge badge px-2 mr-2 mb-2"
        use:truncate={text}
        style="min-width: 3rem; max-width: calc({length}ch + 1.5rem)"
    >
        {text}
    </span>
{/if}

<style>
    .local-satsmail-course-badge {
        font-size: inherit;
        font-weight: inherit;
        color: var(--local-satsmail-color-gray-fg);
        background-color: var(--local-satsmail-color-gray-bg);
        padding-top: 0;
        padding-bottom: 0;
        line-height: inherit;
    }
</style>

