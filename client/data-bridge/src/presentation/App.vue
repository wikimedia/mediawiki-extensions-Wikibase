<template>
	<div id="data-bridge-app" class="wb-db-app">
		<ProcessDialogHeader :title="$messages.get( $messages.KEYS.BRIDGE_DIALOG_TITLE )">
			<template v-slot:primaryAction>
				<EventEmittingButton
					:message="$messages.get( publishOrSave )"
					type="primaryProgressive"
					:squary="true"
					@click="saveAndClose"
				/>
			</template>
		</ProcessDialogHeader>

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
import Events from '@/events';
import DataBridge from '@/presentation/components/DataBridge.vue';
import Initializing from '@/presentation/components/Initializing.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import { Getter, Action } from 'vuex-class';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import { BRIDGE_SAVE } from '@/store/actionTypes';

@Component( {
	components: {
		DataBridge,
		ErrorWrapper,
		Initializing,
		EventEmittingButton,
		ProcessDialogHeader,
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

	public get publishOrSave() {
		return this.$bridgeConfig.usePublish ?
			this.$messages.KEYS.PUBLISH_CHANGES : this.$messages.KEYS.SAVE_CHANGES;
	}

	@Action( BRIDGE_SAVE )
	public save!: () => Promise<void>;

	public saveAndClose(): void {
		this.save()
			.then( () => {
				this.$emit( Events.onSaved );
			} )
			.catch( ( _error ) => {
				// TODO store already sets application into error state. Do we need to do anything else?
			} );
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
	height: 100%;
	font-family: $font-family-system-sans;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	color: #2c3e50;

	@include media-breakpoint-up(breakpoint) {
		height: 448px;
	}
}
</style>
