<template>
	<div
		class="wb-tr-popper-wrapper"
		tabindex="-1"
		@focusout="onFocusout"
		@keydown.esc="closeKeyPress"
	>
		<div class="wb-tr-popper-triangle" />
		<div class="wb-tr-popper-body">
			<div class="wb-tr-title-wrapper">
				<span class="wb-tr-popper-title">{{ title }}</span>
				<button class="wb-tr-popper-close" @click="closeClick" />
			</div>
			<div class="wb-tr-popper-subheading-area">
				<slot name="subheading-area" />
			</div>
			<p class="wb-tr-popper-text">
				{{ popperText }}
			</p>
			<p class="wb-tr-popper-feedback">
				{{ popperFeedbackText }}
				<a
					:title="popperFeedbackLinkTitle"
					:href="feedbackLink"
					target="_blank"
				>{{ popperFeedbackLinkText }}</a>
			</p>
		</div>
	</div>
</template>

<script lang="ts">
import { POPPER_HIDE } from '@/store/actionTypes';
import Component from 'vue-class-component';
import Vue from 'vue';
import { Getter } from 'vuex-class';

@Component( {
	props: {
		guid: String,
		title: String,
	},
} )
export default class Popper extends Vue {
	@Getter( 'feedbackLink' )
	public feedbackLink!: string;

	public mounted(): void {
		( this.$el as HTMLElement ).focus();
	}

	public get popperText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-text' );
	}

	public get popperFeedbackText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-feedback-text' );
	}

	public get popperFeedbackLinkText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-feedback-link-text' );
	}

	public get popperFeedbackLinkTitle(): string {
		return this.$message( 'wikibase-tainted-ref-popper-feedback-link-title' );
	}

	public onFocusout( event: FocusEvent ): void {
		const relatedTarget = event.relatedTarget;

		if ( !relatedTarget || !this.$el.contains( ( relatedTarget as Node ) ) ) {
			this.$store.dispatch( POPPER_HIDE, this.$props.guid );
		}
	}

	public closeClick( event: MouseEvent ): void {
		event.preventDefault();
		this.$store.dispatch( POPPER_HIDE, this.$props.guid );
	}

	public closeKeyPress( event: KeyboardEvent ): void {
		event.preventDefault();
		this.$store.dispatch( POPPER_HIDE, this.$props.guid );
	}
}
</script>

<style lang="scss">
.wb-tr-popper-wrapper {
	z-index: 1;
	position: relative;
	width: 415px;
}

.wb-tr-popper-wrapper:focus {
	outline: 0;
}

.wb-tr-popper-triangle {
	width: 27px;
	position: absolute;
	top: -14px;
	left: calc( 50% - 29px / 2 );
	overflow: hidden;
	border-bottom: 0 transparent;
	border-right: 1px solid transparent;
	border-left: 1px solid transparent;
	z-index: 3;
	height: 15px;
}

.wb-tr-popper-triangle:before {
	content: '';
	position: absolute;
	top: -5px;
	left: 0;
	width: 50px;
	height: 18px;
	-webkit-transform-origin: 0 100%;
	-ms-transform-origin: 0 100%;
	transform-origin: 0 100%;
	-webkit-transform: rotate( 45deg );
	-ms-transform: rotate( 45deg );
	transform: rotate( 45deg );
	border-top: 1px solid $border-color;
	border-left: 1px solid $border-color;
	background: $color-white;
}

.wb-tr-popper-body {
	border: $border-color 1px solid;
	border-radius: 2px;
	z-index: 2;
	position: relative;
	background-color: $color-white;
}

.wb-tr-popper-title {
	font-family: sans-serif;
	font-size: 14px;
	font-weight: bold;
	color: $color-black;
	line-height: 22px;
	margin: 8px 0 0 16px;
}

.wb-tr-popper-text {
	font-family: sans-serif;
	font-size: 14px;
	color: $basic-text-black;
	margin: 0 16px 8px 16px;
	line-height: 22px;
}

.wb-tr-title-wrapper {
	display: flex;
	justify-content: space-between;
}

.wb-tr-popper-close {
	border-color: transparent;
	background: transparent;
	margin: 1px 8px 1px 0;
	width: 32px;
	height: 32px;
	color: $color-dark-grey;
	transition: background-color 100ms;
	border-radius: 2px;
	position: relative;
	background-image: $svg-close-icon;
	background-repeat: no-repeat;
	background-position: center;
	background-size: 14px 14px;
}

.wb-tr-popper-close:hover {
	background-color: $background-color-light-grey;
}

.wb-tr-popper-close:active {
	transition-property: fade_out( $border-color-grey, 1 );
	transition-duration: 100ms;
}

.wb-tr-popper-subheading-area {
	font-weight: normal;
	text-align: right;
	font-size: 12px;
	margin: 4px 16px 0 16px;
	color: $help-link-blue;
	line-height: 20px;
	border-top: 1px $border-color-grey solid;
}

.wb-tr-popper-feedback {
	font-weight: normal;
	color: $basic-text-black;
	margin: 8px 16px 8px 16px;
	line-height: 22px;
}

.wb-tr-popper-feedback a {
	color: $feedback-link-blue;
}
</style>
