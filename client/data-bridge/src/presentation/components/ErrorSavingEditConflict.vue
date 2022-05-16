<template>
	<div class="wb-db-error-saving-edit-conflict">
		<h2
			class="wb-db-error-saving-edit-conflict__heading"
		>
			{{ $messages.getText( $messages.KEYS.EDIT_CONFLICT_ERROR_HEADING ) }}
		</h2>
		<IconMessageBox
			class="wb-db-error-saving-edit-conflict__message"
			type="error"
		>
			{{ $messages.getText( $messages.KEYS.EDIT_CONFLICT_ERROR_MESSAGE ) }}
		</IconMessageBox>
		<EventEmittingButton
			class="wb-db-error-saving-edit-conflict__reload"
			type="primaryProgressive"
			size="M"
			:message="$messages.getText( $messages.KEYS.ERROR_RELOAD_PAGE )"
			@click="reload"
		/>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';

/**
 * A component which gets shown if an edit conflict occurs while saving.
 * The only possible action is to reload the whole page.
 */
export default defineComponent( {
	name: 'ErrorSavingEditConflict',
	components: {
		EventEmittingButton,
		IconMessageBox,
	},
	emits: [ 'reload' ],
	methods: {
		reload(): void {
			/**
			 * An event fired when the user requested to reload the whole page.
			 * @type {Event}
			 */
			this.$emit( 'reload' );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-error-saving-edit-conflict {
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

	&__reload {
		@include marginForCenterColumn();
	}
}
</style>
