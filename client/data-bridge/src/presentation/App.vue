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
					@click="save"
					:disabled="!canStartSaving"
					v-if="!hasError && !isSaved"
				/>
			</template>
			<template v-slot:safeAction>
				<EventEmittingButton
					:message="$messages.get( $messages.KEYS.CANCEL )"
					type="close"
					size="L"
					:squary="true"
					:disabled="isSaving"
					@click="close"
				/>
			</template>
		</ProcessDialogHeader>

		<div class="wb-db-app__body">
			<ErrorWrapper v-if="hasError" />
			<ThankYou
				v-else-if="isSaved"
				:repo-link="repoLink"
				@opened-reference-edit-on-repo="openedReferenceEditOnRepo"
			/>
			<Loading
				v-else
				:is-initializing="isInitializing"
				:is-saving="isSaving"
			>
				<div class="wb-db-app__license" v-if="licenseIsVisible">
					<License
						@close="dismissLicense"
					/>
				</div>
				<DataBridge
					:class="[ 'wb-db-app__data-bridge', isOverlayed ? 'wb-db-app__data-bridge--overlayed' : '' ]"
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
import License from '@/presentation/components/License.vue';
import ThankYou from '@/presentation/components/ThankYou.vue';

@Component( {
	components: {
		License,
		DataBridge,
		ErrorWrapper,
		Loading,
		EventEmittingButton,
		ProcessDialogHeader,
		ThankYou,
	},
} )
export default class App extends mixins( StateMixin ) {
	public licenseIsVisible = false;

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

	public get isOverlayed(): boolean {
		return this.isSaving || this.licenseIsVisible;
	}

	public get isSaving(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.SAVING;
	}

	public get isInitializing(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.INITIALIZING;
	}

	public get isSaved(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.SAVED;
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

	public get repoLink(): string {
		return this.rootModule.state.originalHref;
	}

	public close(): void {
		if ( this.isSaved ) {
			this.$emit( Events.onSaved );
		} else {
			this.$emit( Events.onCancel );
		}
	}

	public dismissLicense(): void {
		this.licenseIsVisible = false;
	}

	public save(): void {
		if ( !this.licenseIsVisible ) {
			this.licenseIsVisible = true;
			return;
		}

		this.licenseIsVisible = false;

		this.rootModule.dispatch( 'saveBridge' );
	}

	public openedReferenceEditOnRepo(): void {
		this.$emit( Events.onSaved );
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
	height: 100%;
	width: 100%;
	display: flex;
	flex-direction: column;
	position: relative;

	// ensure we are not affected by any font-size changes of the OOUI dialog cause by the skin
	font-size: 1rem;

	&__header {
		height: $size-dialog-bar--desktop;
		overflow: hidden;
	}

	&__body {
		flex: 1;
		overflow-x: hidden;
		overflow-y: auto;
	}

	&__license {
		position: absolute;
		width: 100%;
		background: #fff;
		box-shadow: $box-shadow-dialog;
		z-index: $stacking-height-license;
	}

	@media ( max-width: $breakpoint ) {
		&__header {
			height: $size-dialog-bar--mobile;
		}
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
			z-index: $stacking-height-overlay;
			background: rgba( 255, 255, 255, 0.5 );
		}
	}
}
</style>
