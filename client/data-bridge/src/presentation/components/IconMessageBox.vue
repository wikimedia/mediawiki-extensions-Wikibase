<template>
	<div
		class="wb-ui-icon-message-box"
		:class="[
			`wb-ui-icon-message-box--${ type }`,
			{ 'wb-ui-icon-message-box--block': !inline },
		]"
	>
		<div class="wb-ui-icon-message-box__content">
			<slot />
		</div>
	</div>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
const validTypes = [ 'notice' ];
@Component
export default class IconMessageBox extends Vue {
	@Prop( {
		type: String,
		required: true,
		validator: ( type ) => validTypes.includes( type ),
	} )
	public type!: string;

	@Prop( {
		type: Boolean,
		required: false,
		default: false,
	} )
	public inline!: boolean;
}
</script>

<style lang="scss">
.wb-ui-icon-message-box {
	box-sizing: border-box;
	text-align: left;
	position: relative;

	&__content {
		margin-left: 3.5 * $base-spacing-unit;

		@include body-responsive();
	}

	&--block {
		border-width: 1px;
		border-style: solid;
		padding: 16px 24px 16px 24px;
	}

	&--block:before {
		background-position: 0 16px;
	}

	&:before {
		background-repeat: no-repeat;
		background-size: contain;
		min-width: $min-size-icon;
		min-height: $min-size-icon;
		width: $size-icon;
		height: 100%;
		top: 0;
		position: absolute;
		content: '';
	}

	&--notice:before {
		background-image: $svg-notice;
	}

	&--block#{&}--notice {
		border-color: $notice-message-border;
		background-color: $notice-message-background;
	}
}
</style>
