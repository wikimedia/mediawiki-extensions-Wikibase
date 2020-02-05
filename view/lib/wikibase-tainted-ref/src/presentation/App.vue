<template>
	<div class="wb-tr-app">
		<div v-if="isTainted && !editState ">
			<span>
				<TaintedIcon :guid="id" />
				<div class="wb-tr-float-wrapper" v-if="popperIsOpened">
					<TaintedPopper :guid="id" :title="popperTitle" />
				</div>
			</span>
		</div>
	</div>
</template>

<script lang="ts">
import {
	Component,
	Vue,
} from 'vue-property-decorator';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { Getter } from 'vuex-class';
import TaintedPopper from '@/presentation/components/TaintedPopper.vue';
import { GET_EDIT_STATE, GET_POPPER_STATE, GET_STATEMENT_TAINTED_STATE } from '@/store/getterTypes';

@Component( {
	components: {
		TaintedIcon,
		TaintedPopper,
	},
} )
export default class App extends Vue {
	@Getter( GET_STATEMENT_TAINTED_STATE )
	public statementsTaintedStateFunction!: Function;

	@Getter( GET_POPPER_STATE )
	public popperStateFunction!: Function;

	@Getter( GET_EDIT_STATE )
	public editStateFunction!: Function;

	public get isTainted(): boolean {
		return this.statementsTaintedStateFunction( this.$data.id );
	}

	public get popperIsOpened(): boolean {
		return this.popperStateFunction( this.$data.id );
	}

	public get editState(): boolean {
		return this.editStateFunction( this.$data.id );
	}
	public get popperTitle(): string {
		return this.$message( 'wikibase-tainted-ref-popper-title' );
	}

}
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
