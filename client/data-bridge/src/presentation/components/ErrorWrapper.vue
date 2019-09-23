<template>
	<section class="wb-db-error">
		<h1>An error occurred</h1>
	</section>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';

@Component
export default class ErrorWrapper extends Vue {}
</script>

<style lang="scss">
.wb-db-error {
	@include dialogBody();
}
</style>
