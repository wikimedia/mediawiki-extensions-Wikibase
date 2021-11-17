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
			<div class="wb-tr-popper-content">
				<slot name="content" />
			</div>
		</div>
	</div>
</template>

<script lang="ts">
import { POPPER_HIDE } from '@/store/actionTypes';
import { defineComponent } from 'vue';

export default defineComponent( {
	name: 'Popper',
	props: {
		guid: {
			type: String,
			default: '',
		},
		title: {
			type: String,
			default: '',
		},
	},
	methods: {
		onFocusout( event: FocusEvent ): void {
			const relatedTarget = event.relatedTarget;

			if ( !relatedTarget || !this.$el.contains( ( relatedTarget as Node ) ) ) {
				this.$store.dispatch( POPPER_HIDE, this.$props.guid );
			}
		},
		closeClick( event: MouseEvent ): void {
			event.preventDefault();
			this.$store.dispatch( POPPER_HIDE, this.$props.guid );
		},
		closeKeyPress( event: KeyboardEvent ): void {
			event.preventDefault();
			this.$store.dispatch( POPPER_HIDE, this.$props.guid );
		},
		mounted(): void {
			( this.$el as HTMLElement ).focus();
		},
	},
} );
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
	margin: 8px 0 4px 16px;
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
	margin: 0 16px 0 16px;
	color: $link-blue;
	line-height: 20px;
	border-top: 1px $border-color-grey solid;
}

.wb-tr-popper-content {
	font-family: sans-serif;
	font-size: 14px;
	color: $basic-text-black;
	margin: 4px 16px 8px 16px;
	line-height: 22px;
}

.wb-tr-popper-content > :first-child {
	margin-top: 0;
}

.wb-tr-popper-content > :last-child {
	margin-bottom: 0;
}
</style>
