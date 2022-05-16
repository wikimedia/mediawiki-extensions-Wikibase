<template>
	<div class="wb-db-license">
		<span class="wb-db-license__button">
			<EventEmittingButton
				:message="$messages.getText( $messages.KEYS.CANCEL )"
				size="M"
				type="close"
				@click="handleCloseButtonClick"
			/>
		</span>
		<div class="wb-db-license__text">
			<h2 class="wb-db-license__heading">
				{{ $messages.getText( $messages.KEYS.LICENSE_HEADING ) }}
			</h2>
			<div
				class="wb-db-license__body"
				v-html="getBodyMessage"
			/>
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import StateMixin from '@/presentation/StateMixin';

export default defineComponent( {
	// eslint-disable-next-line vue/multi-word-component-names
	name: 'License',
	mixins: [ StateMixin ],
	components: { EventEmittingButton },
	emits: [ 'close' ],
	computed: {
		publishOrSave(): string {
			return this.rootModule.getters.config.usePublish ?
				this.$messages.KEYS.PUBLISH_CHANGES : this.$messages.KEYS.SAVE_CHANGES;
		},
		getBodyMessage(): string {
			const config = this.rootModule.getters.config;
			return this.$messages.get(
				this.$messages.KEYS.LICENSE_BODY,
				this.publishOrSave,
				config.termsOfUseUrl ?? '',
				config.dataRightsUrl ?? '',
				config.dataRightsText ?? '',
			);
		},
	},
	methods: {
		handleCloseButtonClick( event: UIEvent ): void {
			this.$emit( 'close', event );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-license {
	&__text {
		padding: $padding-panel-form;
	}

	&__heading {
		margin-bottom: $heading-margin-bottom;
		margin-top: 0; // override default, we only want the space from the __text padding

		@include h5();
	}

	&__body {
		@include body-responsive();

		p {
			margin: $base-spacing-unit-fixed 0;

			&:last-child {
				margin-bottom: 0;
			}
		}
	}

	&__button {
		float: right;
	}
}
</style>
