<template>
	<!--
		If the popper is already open this is an un-clickable div.
	-->
	<component
		:is="popperIsOpened ? 'div' : 'a'"
		class="wb-tr-tainted-icon"
		title="This statement has some potential issues"
		@click="event => !popperIsOpened && onClick( event )"
	/>
</template>

<script lang="ts">
import { POPPER_SHOW } from '@/store/actionTypes';
import Component from 'vue-class-component';
import { Getter } from 'vuex-class';
import Vue from 'vue';

@Component
export default class TaintedIcon extends Vue {
	@Getter( 'popperState' )
	public popperStateFunction!: Function;

	public onClick( event: MouseEvent ) {
		event.preventDefault();
		this.$store.dispatch( POPPER_SHOW, this.$parent.$data.id );
		this.$track( 'counter.wikibase.view.tainted-ref.taintedIconClick', 1 );
	}

	public get popperIsOpened(): boolean {
		return this.popperStateFunction( this.$parent.$data.id );
	}
}
</script>

<style lang="scss">
	.wb-tr-tainted-icon {
		display: block;
		width: 1.4em;
		height: 1.4em;
		background-color: transparent;
		background-image: $svg-tainted-icon;
		background-position: top left;
		background-size: 100%;
		background-repeat: no-repeat;
	}
</style>
