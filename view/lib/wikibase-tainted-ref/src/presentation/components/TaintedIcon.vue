<!--
	If the popper is already open this is an un-clickable div.
-->
<template>
	<component
		:is="popperIsOpened ? 'div' : 'a'"
		class="wb-tr-tainted-icon"
		:title="iconTitle"
		@click="event => !popperIsOpened && onClick( event )"
	/>
</template>

<script lang="ts">
import { POPPER_SHOW } from '@/store/actionTypes';
import { GET_POPPER_STATE } from '@/store/getterTypes';
import { defineComponent } from 'vue';

export default defineComponent( {
	name: 'TaintedIcon',
	props: {
		guid: {
			type: String,
			default: '',
		},
	},
	computed: {
		iconTitle(): string {
			return this.$message( 'wikibase-tainted-ref-tainted-icon-title' );
		},
		popperIsOpened(): boolean {
			return this.$store.getters[ GET_POPPER_STATE ]( this.$props.guid );
		},
	},
	methods: {
		onClick( event: MouseEvent ): void {
			event.preventDefault();
			this.$track( 'counter.wikibase.view.tainted-ref.taintedIconClick', 1 );
			this.$store.dispatch( POPPER_SHOW, this.$props.guid );
		},
	},
} );
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
