<template>
	<div id="data-bridge-app" class="wb-db-app">
		<ProcessDialogHeader class="wb-db-app__header" :title="$messages.get( $messages.KEYS.BRIDGE_DIALOG_TITLE )">
			<template v-slot:primaryAction>
				<EventEmittingButton
					:message="$messages.get( publishOrSave )"
					type="primaryProgressive"
					:squary="true"
					@click="saveAndClose"
					:disabled="!canSave"
					v-if="!hasError"
				/>
			</template>
			<template v-slot:safeAction>
				<EventEmittingButton
					:message="$messages.get( $messages.KEYS.CANCEL )"
					type="cancel"
					:squary="true"
					@click="cancel"
				/>
			</template>
		</ProcessDialogHeader>

		<div class="wb-db-app__body">
			<ErrorWrapper v-if="hasError" />
			<Initializing v-else :is-initializing="isInitializing">
				<DataBridge />
			</Initializing>
		</div>
	</div>
</template>

<script lang="ts">
import { mixins } from 'vue-class-component';
import { Component } from 'vue-property-decorator';
import Events from '@/events';
import StateMixin from '@/presentation/StateMixin';
import DataBridge from '@/presentation/components/DataBridge.vue';
import Initializing from '@/presentation/components/Initializing.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ApplicationStatus from '@/definitions/ApplicationStatus';
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
export default class App extends mixins( StateMixin ) {
	public get isInitializing(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.INITIALIZING;
	}

	public get hasError(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.ERROR;
	}

	public get canSave(): boolean {
		return this.rootModule.getters.canSave;
	}

	public get publishOrSave(): string {
		return this.$bridgeConfig.usePublish ?
			this.$messages.KEYS.PUBLISH_CHANGES : this.$messages.KEYS.SAVE_CHANGES;
	}

	public saveAndClose(): void {
		this.rootModule.dispatch( BRIDGE_SAVE )
			.then( () => {
				this.$emit( Events.onSaved );
			} )
			.catch( ( _error ) => {
				// TODO store already sets application into error state. Do we need to do anything else?
			} );
	}

	public cancel(): void {
		this.$emit( Events.onCancel );
	}
}
</script>

<style lang="scss">
.wb-db-app {
	font-family: $font-family-system-sans;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	color: #2c3e50;
	overflow: hidden;

	&__header {
		height: $size-dialog-bar--desktop;
		overflow: hidden;
	}

	&__body {
		position: absolute;
		top: $size-dialog-bar--desktop;
		left: 0;
		right: 0;
		bottom: 0;
		overflow-x: hidden;
		overflow-y: auto;
	}
}
</style>
