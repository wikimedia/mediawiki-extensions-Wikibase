<template>
	<div id="data-bridge-app" class="wb-db-app">
		<ProcessDialogHeader class="wb-db-app__header">
			<template v-slot:title>
				<span v-html="title" />
			</template>
			<template v-slot:primaryAction>
				<EventEmittingButton
					:message="$messages.get( publishOrSave )"
					type="primaryProgressive"
					size="L"
					:squary="true"
					@click="saveAndClose"
					:disabled="!canStartSaving"
					v-if="!hasError"
				/>
			</template>
			<template v-slot:safeAction>
				<EventEmittingButton
					:message="$messages.get( $messages.KEYS.CANCEL )"
					type="cancel"
					size="L"
					:squary="true"
					:disabled="isSaving"
					@click="cancel"
				/>
			</template>
		</ProcessDialogHeader>

		<div class="wb-db-app__body">
			<ErrorWrapper v-if="hasError" />
			<Loading
				v-else
				:is-initializing="isInitializing"
				:is-saving="isSaving"
			>
				<DataBridge
					:class="[ 'wb-db-app__data-bridge', isSaving ? 'wb-db-app__data-bridge--overlayed' : '' ]"
				/>
			</Loading>
		</div>
	</div>
</template>

<script lang="ts">
import { mixins } from 'vue-class-component';
import { Component } from 'vue-property-decorator';
import Events from '@/events';
import StateMixin from '@/presentation/StateMixin';
import DataBridge from '@/presentation/components/DataBridge.vue';
import Loading from '@/presentation/components/Loading.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import TermLabel from '@/presentation/components/TermLabel.vue';

@Component( {
	components: {
		DataBridge,
		ErrorWrapper,
		Loading,
		EventEmittingButton,
		ProcessDialogHeader,
	},
} )
export default class App extends mixins( StateMixin ) {
	public get title(): string {
		return this.$messages.get(
			this.$messages.KEYS.BRIDGE_DIALOG_TITLE,
			new TermLabel( {
				propsData: {
					term: this.rootModule.getters.targetLabel,
				},
			} ).$mount().$el as HTMLElement,
		);
	}

	public get isSaving(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.SAVING;
	}

	public get isInitializing(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.INITIALIZING;
	}

	public get hasError(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.ERROR;
	}

	public get canStartSaving(): boolean {
		return this.rootModule.getters.canStartSaving;
	}

	public get publishOrSave(): string {
		return this.$bridgeConfig.usePublish ?
			this.$messages.KEYS.PUBLISH_CHANGES : this.$messages.KEYS.SAVE_CHANGES;
	}

	public saveAndClose(): void {
		this.rootModule.dispatch( 'saveBridge' )
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
	color: $wmui-color-base10;
	overflow: hidden;

	// ensure we are not affected by any font-size changes of the OOUI dialog cause by the skin
	font-size: 1rem;

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

	&__data-bridge--overlayed {
		position: relative;

		&:after {
			content: '';
			position: absolute;
			left: 0;
			right: 0;
			top: 0;
			bottom: 0;
			z-index: $default-visibility;
			background: rgba( 255, 255, 255, 0.5 );
		}
	}
}
</style>
