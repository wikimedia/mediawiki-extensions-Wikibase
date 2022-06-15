<template>
	<div class="wb-db-error-saving-assertuser">
		<h2
			class="wb-db-error-saving-assertuser__heading"
		>
			{{ $messages.getText( $messages.KEYS.SAVING_ERROR_ASSERTUSER_HEADING ) }}
		</h2>
		<IconMessageBox
			class="wb-db-error-saving-assertuser__message"
			type="warning"
		>
			{{ $messages.getText( $messages.KEYS.SAVING_ERROR_ASSERTUSER_MESSAGE ) }}
		</IconMessageBox>
		<div class="wb-db-error-saving-assertuser__buttons">
			<EventEmittingButton
				class="wb-db-error-saving-assertuser__proceed"
				type="primaryProgressive"
				size="M"
				:message="$messages.getText( publishOrSave )"
				@click="proceed"
			/>
			<EventEmittingButton
				class="wb-db-error-saving-assertuser__login"
				type="neutral"
				size="M"
				:href="loginUrl"
				:message="$messages.getText( $messages.KEYS.SAVING_ERROR_ASSERTUSER_LOGIN )"
				:new-tab="true"
				:prevent-default="false"
				@click="back"
			/>
			<EventEmittingButton
				class="wb-db-error-saving-assertuser__back"
				type="link"
				size="M"
				:message="$messages.getText( $messages.KEYS.SAVING_ERROR_ASSERTUSER_KEEP_EDITING )"
				@click="back"
			/>
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import StateMixin from '@/presentation/StateMixin';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';

/**
 * A component which gets shown when an error occurs while saving and the user is logged out.
 */
export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'ErrorSavingAssertUser',
	components: {
		EventEmittingButton,
		IconMessageBox,
	},
	props: {
		loginUrl: { required: true, type: String },
	},
	computed: {
		publishOrSave(): string {
			return this.rootModule.getters.config.usePublish ?
				this.$messages.KEYS.SAVING_ERROR_ASSERTUSER_PUBLISH : this.$messages.KEYS.SAVING_ERROR_ASSERTUSER_SAVE;
		},
	},
	methods: {
		async proceed(): Promise<void> {
			await this.rootModule.dispatch( 'stopAssertingUserWhenSaving' );
			await this.rootModule.dispatch( 'retrySave' );
		},
		back(): void {
			this.rootModule.dispatch( 'goBackFromErrorToReady' );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-error-saving-assertuser {
	display: flex;
	align-items: stretch;
	justify-content: center;
	flex-direction: column;

	&__heading {
		@include h3();
		@include marginForCenterColumnHeading();
		text-align: center;
	}

	&__message {
		@include marginForCenterColumn( 3 * $base-spacing-unit );
	}

	&__buttons {
		@include marginForCenterColumn( $margin-bottom: 3 * $base-spacing-unit );
		padding: 0 3 * $base-spacing-unit-fixed;

		> * {
			width: 100%;
		}
	}

	&__proceed,
	&__login {
		margin-bottom: $base-spacing-unit;
	}

	@media ( max-width: $breakpoint ) {
		&__buttons {
			margin-bottom: $base-spacing-unit;
			padding: 0;
		}

		&__proceed {
			margin-bottom: 2 * $base-spacing-unit;
		}
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
