<script setup>
defineProps({
  site: Object,
  period: String,
  activeTab: String,
  overview: Object,
  requiresPassword: Boolean,
  passwordError: String,
})
</script>

<template>
  <div>
    <h1>Public Shared Dashboard</h1>
  </div>
</template>
