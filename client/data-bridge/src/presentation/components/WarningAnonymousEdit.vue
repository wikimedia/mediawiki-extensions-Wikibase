<template>
	<div class="wb-db-warning-anonymous-edit">
		<h2
			class="wb-db-warning-anonymous-edit__heading"
		>
			{{ $messages.getText( $messages.KEYS.ANONYMOUS_EDIT_WARNING_HEADING ) }}
		</h2>
		<IconMessageBox
			class="wb-db-warning-anonymous-edit__message"
			type="warning"
		>
			<p
				class="wb-db-warning-anonymous-edit__message-text"
				v-html="$messages.get( $messages.KEYS.ANONYMOUS_EDIT_WARNING_MESSAGE )"
			/>
		</IconMessageBox>
		<div class="wb-db-warning-anonymous-edit__buttons">
			<EventEmittingButton
				class="wb-db-warning-anonymous-edit__proceed"
				type="primaryProgressive"
				size="M"
				:message="$messages.getText( $messages.KEYS.ANONYMOUS_EDIT_WARNING_PROCEED )"
				@click="proceed"
			/>
			<EventEmittingButton
				class="wb-db-warning-anonymous-edit__login"
				type="neutral"
				size="M"
				:message="$messages.getText( $messages.KEYS.ANONYMOUS_EDIT_WARNING_LOGIN )"
				:href="loginUrl"
				:prevent-default="false"
			/>
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';

export default defineComponent( {
	name: 'WarningAnonymousEdit',
	emits: [ 'proceed' ],
	components: {
		EventEmittingButton,
		IconMessageBox,
	},
	props: {
		loginUrl: {
			type: String,
			required: true,
		},
	},
	methods: {
		proceed(): void {
			this.$emit( 'proceed' );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-warning-anonymous-edit {
	display: flex;
	align-items: stretch;
	justify-content: center;
	flex-direction: column;
	padding: $padding-panel-form;

	&__heading {
		@include h3();
		@include marginForCenterColumnHeading();
		text-align: center;
	}

	&__message {
		@include marginForCenterColumn( 3 * $base-spacing-unit );
	}

	&__message-text {
		margin: 0;
	}

	&__buttons {
		@include marginForCenterColumn( $margin-bottom: 3 * $base-spacing-unit );
		padding: 0 3 * $base-spacing-unit-fixed;

		> * {
			width: 100%;
		}
	}

	&__proceed {
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
}
</style>
