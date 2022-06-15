<template>
	<div class="wb-db-error-unknown">
		<h2
			class="wb-db-error-unknown__heading"
		>
			{{ $messages.getText( $messages.KEYS.UNKNOWN_ERROR_HEADING ) }}
		</h2>
		<IconMessageBox
			class="wb-db-error-unknown__message"
			type="error"
		>
			{{ $messages.getText( $messages.KEYS.UNKNOWN_ERROR_MESSAGE ) }}
		</IconMessageBox>
		<ReportIssue
			class="wb-db-error-unknown__report"
		/>
		<EventEmittingButton
			class="wb-db-error-unknown__relaunch"
			type="primaryProgressive"
			size="M"
			:message="$messages.getText( $messages.KEYS.ERROR_RELOAD_BRIDGE )"
			@click="relaunch"
		/>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import StateMixin from '@/presentation/StateMixin';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import ReportIssue from '@/presentation/components/ReportIssue.vue';

/**
 * A component which gets shown if no dedicated handling for the type of
 * error which happened is configured.
 */
export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'ErrorUnknown',
	components: {
		EventEmittingButton,
		IconMessageBox,
		ReportIssue,
	},
	emits: [ 'relaunch' ],
	methods: {
		relaunch(): void {
			/**
			 * An event fired when the user clicks the CTA to relaunch the bridge
			 * @type {Event}
			 */
			this.$emit( 'relaunch' );
		},
	},
	mounted(): void {
		this.rootModule.dispatch( 'trackErrorsFallingBackToGenericView' );
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-error-unknown {
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

	&__relaunch {
		@include marginForCenterColumn( $margin-top: 4 * $base-spacing-unit, $margin-bottom: 3 * $base-spacing-unit );
	}
}
</style>
