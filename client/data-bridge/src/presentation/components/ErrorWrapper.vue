<template>
	<section class="wb-db-error">
		<h1>An error occurred</h1>
		<p>
			<a
				:href="$repoRouter.getPageUrl( 'Project:About' )"
			>About the repository</a>,
			<a
				:href="$repoRouter.getPageUrl( 'Special:Log/protect', { page: 'Item:Q42' } )"
			>An interesting protection history</a>.
		</p>
	</section>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';

@Component
export default class ErrorWrapper extends Vue {}
</script>
