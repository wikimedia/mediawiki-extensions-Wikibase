<template>
	<div class="wb-db-radio-group">
		<slot />
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent( {
	name: 'RadioGroup',
	compatConfig: { MODE: 3 },
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
