<template>
	<ProcessDialogHeader class="wb-db-app__header">
		<template #title>
			<span v-html="title" />
		</template>
		<template #primaryAction>
			<EventEmittingButton
				:message="$messages.getText( publishOrSave )"
				type="primaryProgressive"
				size="L"
				:squary="true"
				@click="save"
				:disabled="!canStartSaving"
				v-if="!hasWarning && !hasError && !isSaved"
			/>
		</template>
		<template #safeAction>
			<span
				:class="{ 'app-header__close-button--desktop-only': canGoBack }"
			>
				<EventEmittingButton
					:message="$messages.getText( $messages.KEYS.CANCEL )"
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
					:message="$messages.getText( $messages.KEYS.ERROR_GO_BACK )"
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
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import StateMixin from '@/presentation/StateMixin';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import TermLabel from '@/presentation/components/TermLabel.vue';
import { createApp, defineComponent } from 'vue';

export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'AppHeader',
	components: {
		EventEmittingButton,
		ProcessDialogHeader,
	},
	emits: [ 'save', 'close', 'back' ],
	computed: {
		title(): string {
			const termLabel = createApp(
				TermLabel,
				{
					term: this.rootModule.getters.targetLabel,
					inLanguage: this.$inLanguage,
				},
			).mount( document.createElement( 'span' ) ).$el;
			return this.$messages.get(
				this.$messages.KEYS.BRIDGE_DIALOG_TITLE,
				termLabel,
			);
		},
		canGoBack(): boolean {
			return this.rootModule.getters.canGoToPreviousState;
		},
		isSaved(): boolean {
			return this.rootModule.getters.applicationStatus === ApplicationStatus.SAVED;
		},
		hasError(): boolean {
			return this.rootModule.getters.applicationStatus === ApplicationStatus.ERROR;
		},
		hasWarning(): boolean {
			return this.rootModule.state.showWarningAnonymousEdit;
		},
		publishOrSave(): string {
			return this.rootModule.getters.config.usePublish ?
				this.$messages.KEYS.PUBLISH_CHANGES : this.$messages.KEYS.SAVE_CHANGES;
		},
		canStartSaving(): boolean {
			return this.rootModule.getters.canStartSaving;
		},
		isSaving(): boolean {
			return this.rootModule.getters.applicationStatus === ApplicationStatus.SAVING;
		},
	},
	methods: {
		close(): void {
			this.$emit( 'close' );
		},
		save(): void {
			this.$emit( 'save' );
		},
		back(): void {
			this.$emit( 'back' );
		},
	},
	compatConfig: { MODE: 3 },
} );
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
