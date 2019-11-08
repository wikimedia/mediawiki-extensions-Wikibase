<template>
	<span class="wb-tr-app">
		<TaintedIcon v-if="isTainted" />
	</span>
</template>

<script lang="ts">
import {
	Component,
	Vue,
} from 'vue-property-decorator';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { Getter } from 'vuex-class';

@Component( {
	components: {
		TaintedIcon,
	},
} )
export default class App extends Vue {
	@Getter( 'statementsTaintedState' )
	public statementsTaintedStateFunction!: Function;

	public get isTainted(): boolean {
		return this.statementsTaintedStateFunction( this.$data.id );
	}
}
</script>

<style lang="scss">
	.wb-tr-app {
		margin-top: 0.7em;
		padding-left: 0.5em;
	}
</style>
