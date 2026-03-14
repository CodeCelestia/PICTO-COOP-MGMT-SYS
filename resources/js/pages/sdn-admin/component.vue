<template>
	<div class="simple-card">
		<h1>{{ title }}</h1>
		<p>{{ message }}</p>
	</div>
</template>

<script setup>
const title = 'Simple Vue Component'
const message = 'This is a basic Vue single-file component.'
</script>

<style scoped>
.simple-card {
	max-width: 520px;
	margin: 2rem auto;
	padding: 1.25rem;
	border: 1px solid #d1d5db;
	border-radius: 0.75rem;
	background: #ffffff;
}

h1 {
	margin: 0 0 0.5rem;
	font-size: 1.5rem;
}

p {
	margin: 0;
	color: #4b5563;
}
</style>
