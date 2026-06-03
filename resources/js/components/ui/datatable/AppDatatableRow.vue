<script>
export default {
    name: "AppDatatableRow",
}
</script>

<template>
    <tr class="border-b border-line-100 last:border-b-0 transition-colors hover:bg-line-100/60">
        <slot />
    </tr>
</template>
