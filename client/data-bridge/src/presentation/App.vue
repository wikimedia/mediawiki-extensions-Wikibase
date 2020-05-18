<template>
	<div id="data-bridge-app" class="wb-db-app">
		<AppHeader
			@save="save"
			@close="close"
			@back="back"
		/>
		<div class="wb-db-app__body">
			<WarningAnonymousEdit
				v-if="showWarningAnonymousEdit"
				:login-url="loginUrl"
				@proceed="dismissWarningAnonymousEdit"
			/>
			<ErrorWrapper
				v-else-if="hasError"
				@relaunch="relaunch"
				@reload="reload"
			/>
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
					:class="[ 'wb-db-app__data-bridge', { 'wb-db-app__data-bridge--overlayed': isOverlayed } ]"
				/>
			</Loading>
		</div>
	</div>
</template>

<script lang="ts">
import WarningAnonymousEdit from '@/presentation/components/WarningAnonymousEdit.vue';
import { mixins } from 'vue-class-component';
import { Component } from 'vue-property-decorator';
import Events from '@/events';
import StateMixin from '@/presentation/StateMixin';
import DataBridge from '@/presentation/components/DataBridge.vue';
import Loading from '@/presentation/components/Loading.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import License from '@/presentation/components/License.vue';
import ThankYou from '@/presentation/components/ThankYou.vue';
import AppHeader from '@/presentation/components/AppHeader.vue';

@Component( {
	components: {
		WarningAnonymousEdit,
		AppHeader,
		License,
		DataBridge,
		ErrorWrapper,
		Loading,
		ThankYou,
	},
} )
export default class App extends mixins( StateMixin ) {
	public licenseIsVisible = false;

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

	public get hasErrorRequiringReload(): boolean {
		return this.hasError &&
			this.rootModule.getters.isEditConflictError;
	}

	public get repoLink(): string {
		return this.rootModule.state.originalHref;
	}

	public get showWarningAnonymousEdit(): boolean {
		return this.rootModule.state.showWarningAnonymousEdit;
	}

	public get loginUrl(): string {
		return this.$clientRouter.getPageUrl(
			'Special:UserLogin',
			{
				returnto: this.rootModule.state.pageTitle,
			},
		);
	}

	public close(): void {
		if ( this.isSaved ) {
			this.$emit( Events.saved );
		} else if ( this.hasErrorRequiringReload ) {
			this.reload();
		} else {
			this.$emit( Events.cancel );
		}
	}

	public back(): void {
		this.rootModule.dispatch( 'goBackFromErrorToReady' );
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
		this.$emit( Events.saved );
	}

	private relaunch(): void {
		/**
		 * An event fired when it is time to relaunch the bridge (usually bubbled from a child component)
		 * @type {Event}
		 */
		this.$emit( Events.relaunch );
	}

	private reload(): void {
		/**
		 * An event fired when the user requested to reload the whole page (usually bubbled from a child component)
		 * @type {Event}
		 */
		this.$emit( Events.reload );
	}

	public dismissWarningAnonymousEdit(): void {
		this.rootModule.dispatch( 'dismissWarningAnonymousEdit' );
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
