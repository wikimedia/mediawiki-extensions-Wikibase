<template>
	<div class="wb-tr-tainted-icon" title="This statement has some potential issues" />
</template>

<script lang="ts">
import {
	Component,
	Vue,
} from 'vue-property-decorator';

@Component
export default class TaintedIcon extends Vue {}
</script>

<style lang="scss">
	.wb-tr-tainted-icon {
		min-width: 20px;
		min-height: 30px;
		width: 20px;
		height: 30px;
		background-color: transparent;
		background-image: $svg-tainted-icon;
		background-position: top left;
		background-size: 20px 30px;
		background-repeat: no-repeat;
	}
</style>
