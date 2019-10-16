<template>
	<div class="wb-tr-app">
		<TaintedIcon v-if="isTainted" />
	</div>
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
