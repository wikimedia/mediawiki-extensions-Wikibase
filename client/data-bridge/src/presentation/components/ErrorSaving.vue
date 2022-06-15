<template>
	<div class="wb-db-error-saving">
		<h2
			class="wb-db-error-saving__heading"
		>
			{{ $messages.getText( $messages.KEYS.SAVING_ERROR_HEADING ) }}
		</h2>
		<IconMessageBox
			class="wb-db-error-saving__message"
			type="error"
		>
			{{ $messages.getText( $messages.KEYS.SAVING_ERROR_MESSAGE ) }}
		</IconMessageBox>
		<ReportIssue
			class="wb-db-error-saving__report"
		/>
		<div class="wb-db-error-saving__buttons">
			<EventEmittingButton
				class="wb-db-error-saving__back"
				type="neutral"
				size="M"
				:message="$messages.getText( $messages.KEYS.ERROR_GO_BACK )"
				@click="goBack"
			/><EventEmittingButton
				class="wb-db-error-saving__retry"
				type="primaryProgressive"
				size="M"
				:message="$messages.getText( $messages.KEYS.ERROR_RETRY_SAVE )"
				@click="retrySave"
			/>
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import StateMixin from '@/presentation/StateMixin';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import ReportIssue from '@/presentation/components/ReportIssue.vue';

/**
 * A component which gets shown when an error occurs while saving,
 * if no dedicated handling for the type of error which happened is configured.
 */
export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'ErrorSaving',
	components: {
		EventEmittingButton,
		IconMessageBox,
		ReportIssue,
	},
	mounted(): void {
		this.rootModule.dispatch( 'trackSavingErrorsFallingBackToGenericView' );
	},
	methods: {
		retrySave(): void {
			this.rootModule.dispatch( 'retrySave' );
		},
		goBack(): void {
			this.rootModule.dispatch( 'goBackFromErrorToReady' );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-error-saving {
	display: flex;
	align-items: center;
	justify-content: center;
	flex-direction: column;

	&__heading {
		@include h3();
		@include marginForCenterColumnHeading();
	}

	&__message {
		@include marginForCenterColumn( 3 * $base-spacing-unit );
	}

	&__buttons {
		@include marginForCenterColumn( $margin-top: 4 * $base-spacing-unit, $margin-bottom: 3 * $base-spacing-unit );
	}

	&__back {
		margin-right: $inter-button-spacing;

		@media ( max-width: $breakpoint ) {
			// show button in AppHeader instead
			display: none;
		}
	}
}
</style>
