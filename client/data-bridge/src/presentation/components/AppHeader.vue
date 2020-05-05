<template>
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
				v-if="!hasWarning && !hasError && !isSaved"
			/>
		</template>
		<template v-slot:safeAction>
			<span
				:class="{ 'app-header__close-button--desktop-only': canGoBack }"
			>
				<EventEmittingButton
					:message="$messages.get( $messages.KEYS.CANCEL )"
					type="close"
					size="L"
					:squary="true"
					:disabled="isSaving"
					@click="close"
				/>
			</span>
			<span
				v-if="canGoBack"
				class="app-header__back-button"
			>
				<EventEmittingButton
					:message="$messages.get( $messages.KEYS.ERROR_GO_BACK )"
					type="back"
					size="L"
					:squary="true"
					@click="back"
				/>
			</span>
		</template>
	</ProcessDialogHeader>
</template>

<script lang="ts">
import Component, { mixins } from 'vue-class-component';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import StateMixin from '@/presentation/StateMixin';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import TermLabel from '@/presentation/components/TermLabel.vue';

@Component( {
	components: {
		EventEmittingButton,
		ProcessDialogHeader,
	},
} )
export default class AppHeader extends mixins( StateMixin ) {

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

	public get canGoBack(): boolean {
		return this.rootModule.getters.canGoToPreviousState;
	}

	public get isSaved(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.SAVED;
	}

	public get hasError(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.ERROR;
	}

	public get hasWarning(): boolean {
		return this.rootModule.state.showWarningAnonymousEdit;
	}

	public get publishOrSave(): string {
		return this.$bridgeConfig.usePublish ?
			this.$messages.KEYS.PUBLISH_CHANGES : this.$messages.KEYS.SAVE_CHANGES;
	}

	public get canStartSaving(): boolean {
		return this.rootModule.getters.canStartSaving;
	}

	public get isSaving(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.SAVING;
	}

	public close(): void {
		this.$emit( 'close' );
	}

	public save(): void {
		this.$emit( 'save' );
	}

	public back(): void {
		this.$emit( 'back' );
	}

}
</script>

<style lang="scss">
.app-header {
	@media not screen and ( max-width: $breakpoint ) {
		&__back-button {
			display: none;
		}
	}

	@media ( max-width: $breakpoint ) {
		&__close-button--desktop-only {
			display: none;
		}
	}
}
</style>
