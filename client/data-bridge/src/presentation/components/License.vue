<template>
	<div class="wb-db-license">
		<span class="wb-db-license__button">
			<EventEmittingButton
				:message="$messages.get( $messages.KEYS.CANCEL )"
				size="M"
				type="close"
				@click="handleCloseButtonClick"
			/>
		</span>
		<div class="wb-db-license__text">
			<h2 class="wb-db-license__heading">
				{{ $messages.get( $messages.KEYS.LICENSE_HEADING ) }}
			</h2>
			<div
				class="wb-db-license__body"
				v-html="getBodyMessage"
			/>
		</div>
	</div>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';

@Component( {
	components: { EventEmittingButton },
} )
export default class License extends Vue {
	public get publishOrSave(): string {
		return this.$bridgeConfig.usePublish ?
			this.$messages.KEYS.PUBLISH_CHANGES : this.$messages.KEYS.SAVE_CHANGES;
	}

	public get getBodyMessage(): string {
		return this.$messages.get(
			this.$messages.KEYS.LICENSE_BODY,
			this.publishOrSave,
			this.$bridgeConfig.termsOfUseUrl ?? '',
			this.$bridgeConfig.dataRightsUrl ?? '',
			this.$bridgeConfig.dataRightsText ?? '',
		);
	}

	public handleCloseButtonClick( event: UIEvent ): void {
		this.$emit( 'close', event );
	}
}
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
