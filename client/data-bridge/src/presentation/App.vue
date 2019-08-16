<template>
	<div id="data-bridge-app" class="wb-db-app">
		<ErrorWrapper v-if="hasError" />
		<component
			:is="isInit ? 'DataBridge' : 'Initializing'"
			v-else
		/>
	</div>
</template>

<script lang="ts">
import {
	Component,
	Vue,
} from 'vue-property-decorator';
import DataBridge from '@/presentation/components/DataBridge.vue';
import Initializing from '@/presentation/components/Initializing.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import { Getter } from 'vuex-class';

@Component( {
	components: {
		DataBridge,
		ErrorWrapper,
		Initializing,
	},
} )
export default class App extends Vue {
	@Getter( 'applicationStatus' )
	public applicationStatus!: ApplicationStatus;

	public get isInit() {
		return this.applicationStatus === ApplicationStatus.READY;
	}

	public get hasError() {
		return this.applicationStatus === ApplicationStatus.ERROR;
	}
}
</script>

<style lang="scss">
/**
 * All components' CSS selectors are prefixed by postcss-prefixwrap. This both
 * * ensures the following reset is restricted to the inside of our application
 * * allows component styles to overcome this reset
 */
@import '~reset-css/sass/_reset';

ul,
ol { // overcome very strong selector, e.g. .content ul li
	li {
		margin: 0;
	}
}

.wb-db-app {
	width: 458px;
	height: 448px;
	font-family: $font-family-system-sans;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	color: #2c3e50;

	@include media-breakpoint-up(breakpoint) {
		width: 100%;
		height: 100%;
	}
}
</style>
