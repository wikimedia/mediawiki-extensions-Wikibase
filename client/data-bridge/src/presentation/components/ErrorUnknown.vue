<template>
	<h1>
		An error occurred
	</h1>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';

/**
 * Component which gets shown if no dedicated handling for the type of error
 * which happened is configured.
 */
@Component( {
} )
export default class ErrorUnknown extends Vue {
}
</script>
