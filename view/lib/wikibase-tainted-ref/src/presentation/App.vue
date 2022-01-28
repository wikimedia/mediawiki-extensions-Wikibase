<template>
	<div class="wb-tr-app">
		<div v-if="isTainted && !editState ">
			<span>
				<TaintedIcon :guid="id" />
				<div class="wb-tr-float-wrapper" v-if="popperIsOpened">
					<TaintedPopper :guid="id" />
				</div>
			</span>
		</div>
	</div>
</template>

<script lang="ts">
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import TaintedPopper from '@/presentation/components/TaintedPopper.vue';
import { GET_EDIT_STATE, GET_POPPER_STATE, GET_STATEMENT_TAINTED_STATE } from '@/store/getterTypes';
import { defineComponent } from 'vue';

export default defineComponent( {
	name: 'App',
	props: {
		id: {
			type: String,
			required: true,
		},
	},
	components: {
		TaintedIcon,
		TaintedPopper,
	},
	computed: {
		isTainted(): boolean {
			return this.$store.getters[ GET_STATEMENT_TAINTED_STATE ]( this.$props.id );
		},
		popperIsOpened(): boolean {
			return this.$store.getters[ GET_POPPER_STATE ]( this.$props.id );
		},
		editState(): boolean {
			return this.$store.getters[ GET_EDIT_STATE ]( this.$props.id );
		},
	},
} );
</script>

<style lang="scss">
	.wb-tr-app {
		display: inline-block;
		margin-top: 0.7em;
		padding-left: 0.5em;
	}

	.wb-tr-float-wrapper {
		display: block;
		position: absolute;
		margin-top: 15px;
		// Move left by 50% of its width, minus half the icon width
		transform: translateX( calc( -50% + 1.4em / 2 ) );
		z-index: 999;
	}
</style>
