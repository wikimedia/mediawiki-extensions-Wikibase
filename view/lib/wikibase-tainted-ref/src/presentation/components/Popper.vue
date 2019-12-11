<template>
	<div class="wb-tr-popper-wrapper" tabindex="-1" @focusout="onFocusout">
		<div class="wb-tr-popper-triangle" />
		<div class="wb-tr-popper-body">
			<div class="wb-tr-title-wrapper">
				<span class="wb-tr-popper-title">{{ popperTitle }}</span>
				<a class="wb-tr-popper-close" @click="closeClick">x</a>
			</div>
			<a
				class="wb-tr-popper-help"
				:title="popperHelpLinkTitle"
				:href="helpLink"
				target="_blank"
				@click="helpClick"
			>{{ popperHelpLinkText }}</a>
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
	props: [ 'guid' ],
} )
export default class Popper extends Vue {
	@Getter( 'helpLink' )
	public helpLink!: string;
	@Getter( 'feedbackLink' )
	public feedbackLink!: string;

	public mounted(): void {
		( this.$el as HTMLElement ).focus();
	}

	public get popperText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-text' );
	}

	public get popperTitle(): string {
		return this.$message( 'wikibase-tainted-ref-popper-title' );
	}

	public get popperHelpLinkTitle(): string {
		return this.$message( 'wikibase-tainted-ref-popper-help-link-title' );
	}

	public get popperHelpLinkText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-help-link-text' );
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

	public helpClick(): void {
		this.$track( 'counter.wikibase.view.tainted-ref.helpLinkClick', 1 );
	}
}
</script>

<style lang="scss">
.wb-tr-popper-wrapper {
	z-index: 1;
	position: relative;
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
	border-top: 1px solid #a2a9b1;
	border-left: 1px solid #a2a9b1;
	background: #fff;
}

.wb-tr-popper-body {
	padding: 8px 16px 8px 16px;
	width: 415px;
	height: 150px;
	border: #a2a9b1 1px solid;
	border-radius: 2px;
	overflow: hidden;
	z-index: 2;
	position: relative;
	background-color: #fff;
}

.wb-tr-popper-title {
	font-family: sans-serif;
	font-size: 16px;
	font-weight: bold;
	color: #000;
}

.wb-tr-popper-text {
	font-family: sans-serif;
	font-size: 14px;
	margin-top: 22px;
	color: #222;
}

.wb-tr-title-wrapper {
	display: flex;
	justify-content: space-between;
	border-bottom: 1px #eaecf0 solid;
}

.wb-tr-popper-close {
	margin-top: -4px;
	margin-right: -8px;
	font-size: 20px;
	width: 32px;
	height: 32px;
	color: #4b4b4b;
	transition: background-color 100ms;
	border-radius: 2px;
	position: relative;
	text-align: center;
}

.wb-tr-popper-close:hover {
	background-color: #f8f9fa;
}

.wb-tr-popper-close:active {
	transition-property: fade_out( #eaecf0, 1 );
	transition-duration: 100ms;
}

.wb-tr-popper-help {
	font-weight: normal;
	float: right;
	font-size: 12px;
	margin-left: 1.5em;
	margin-top: 4px;
	margin-bottom: 4px;
	color: #36c;
}

.wb-tr-popper-feedback {
	font-weight: normal;
	position: absolute;
	color: #222;
	margin-top: 8px;
	bottom: 0;
}

.wb-tr-popper-feedback a {
	color: #0645ad;
}
</style>
