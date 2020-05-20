<template>
	<div class="wb-db-error-saving">
		<h2
			class="wb-db-error-saving__heading"
		>
			{{ $messages.get( $messages.KEYS.SAVING_ERROR_HEADING ) }}
		</h2>
		<IconMessageBox
			class="wb-db-error-saving__message"
			type="error"
		>
			{{ $messages.get( $messages.KEYS.SAVING_ERROR_MESSAGE ) }}
		</IconMessageBox>
		<ReportIssue
			class="wb-db-error-saving__report"
		/>
		<div class="wb-db-error-saving__buttons">
			<EventEmittingButton
				class="wb-db-error-saving__back"
				type="neutral"
				size="M"
				:message="$messages.get( $messages.KEYS.ERROR_GO_BACK )"
				@click="goBack"
			/><EventEmittingButton
				class="wb-db-error-saving__retry"
				type="primaryProgressive"
				size="M"
				:message="$messages.get( $messages.KEYS.ERROR_RETRY_SAVE )"
				@click="retrySave"
			/>
		</div>
	</div>
</template>

<script lang="ts">
import { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';
import Component from 'vue-class-component';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import ReportIssue from '@/presentation/components/ReportIssue.vue';

/**
 * A component which gets shown when an error occurs while saving,
 * if no dedicated handling for the type of error which happened is configured.
 */
@Component( {
	components: {
		EventEmittingButton,
		IconMessageBox,
		ReportIssue,
	},
} )
export default class ErrorSaving extends mixins( StateMixin ) {
	public mounted(): void {
		this.rootModule.dispatch( 'trackSavingErrorsFallingBackToGenericView' );
	}

	public retrySave(): void {
		this.rootModule.dispatch( 'retrySave' );
	}

	public goBack(): void {
		this.rootModule.dispatch( 'goBackFromErrorToReady' );
	}
}
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
