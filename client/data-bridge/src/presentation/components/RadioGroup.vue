<template>
	<div class="wb-db-radio-group">
		<slot />
	</div>
</template>

<script lang="ts">
import Vue from 'vue';

export default Vue.extend( {
	name: 'RadioGroup',
} );
</script>

<style lang="scss">
.wb-db-radio-group > * {
	margin-top: $binary-input-inter-component-spacing;

	&:first-child {
		margin-top: 0;
	}
}
</style>
