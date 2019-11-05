<template>
	<a
		class="wb-tr-tainted-icon"
		title="This statement has some potential issues"
		@click="onClick"
	/>
</template>

<script lang="ts">
import { POPPER_SHOW } from '@/store/actionTypes';
import Component from 'vue-class-component';
import Vue from 'vue';

@Component
export default class TaintedIcon extends Vue {
	public onClick( event: MouseEvent ) {
		event.preventDefault();
		this.$store.dispatch( POPPER_SHOW, this.$parent.$data.id );
	}
}
</script>

<style lang="scss">
	.wb-tr-tainted-icon {
		display: block;
		min-width: 20px;
		min-height: 23px;
		width: 20px;
		height: 25px;
		background-color: transparent;
		background-image: $svg-tainted-icon;
		background-position: top left;
		background-size: 20px 30px;
		background-repeat: no-repeat;
	}
</style>
