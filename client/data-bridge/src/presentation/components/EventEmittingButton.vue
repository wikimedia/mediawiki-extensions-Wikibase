<template>
	<a
		class="wb-ui-event-emitting-button"
		:class="[
			`wb-ui-event-emitting-button--${this.type}`,
			{ 'wb-ui-event-emitting-button--squary': squary },
			{ 'wb-ui-event-emitting-button--pressed': isPressed },
			{ 'wb-ui-event-emitting-button--iconOnly': isIconOnly },
			{ 'wb-ui-event-emitting-button--frameless': isFrameless },
			{ 'wb-ui-event-emitting-button--disabled': disabled },
		]"
		:href="href"
		:tabindex="tabindex"
		:role="href ? 'link' : 'button'"
		:aria-disabled="disabled ? 'true' : null"
		:title="message"
		@click="click"
		@keydown.enter="handleEnterPress"
		@keydown.space="handleSpacePress"
		@keyup.enter="unpress"
		@keyup.space="unpress"
	>
		<span
			class="wb-ui-event-emitting-button__text"
		>{{ message }}</span>
	</a>
</template>
<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

const validTypes = [
	'primaryProgressive',
	'cancel',
];

const framelessTypes = [
	'cancel',
];

const imageOnlyTypes = [
	'cancel',
];

@Component
export default class EventEmittingButton extends Vue {
	@Prop( {
		required: true,
		validator: ( type ) => validTypes.indexOf( type ) !== -1,
	} )
	public type!: string;

	@Prop( { required: true, type: String } )
	public message!: string;

	@Prop( { required: false, default: null, type: String } )
	public href!: string|null;

	@Prop( { required: false, default: true, type: Boolean } )
	public preventDefault!: boolean;

	@Prop( { required: false, default: false, type: Boolean } )
	public disabled!: boolean;

	@Prop( { required: false, default: false, type: Boolean } )
	public squary!: boolean;

	public isPressed = false;

	public get isIconOnly(): boolean {
		return imageOnlyTypes.includes( this.type );
	}

	public get isFrameless(): boolean {
		return framelessTypes.includes( this.type );
	}

	public handleSpacePress( event: UIEvent ): void {
		if ( !this.simulateSpaceOnButton() ) {
			return;
		}
		this.preventScrollingDown( event );
		this.isPressed = true;
		this.click( event );
	}

	public handleEnterPress( event: UIEvent ): void {
		this.isPressed = true;
		if ( this.thereIsNoSeparateClickEvent() ) {
			this.click( event );
		}
	}

	public unpress(): void {
		this.isPressed = false;
	}

	public click( event: UIEvent ): void {
		if ( this.preventDefault ) {
			this.preventOpeningLink( event );
		}
		if ( this.disabled ) {
			return;
		}
		this.$emit( 'click', event );
	}

	public get tabindex(): number|null {
		if ( this.disabled ) {
			return -1;
		}

		if ( this.href ) {
			return null;
		}

		return 0;
	}

	private preventOpeningLink( event: UIEvent ): void {
		event.preventDefault();
	}

	private preventScrollingDown( event: UIEvent ): void {
		event.preventDefault();
	}

	private thereIsNoSeparateClickEvent(): boolean {
		return this.href === null;
	}

	private simulateSpaceOnButton(): boolean {
		return this.href === null;
	}
}
</script>
<style lang="scss">
.wb-ui-event-emitting-button {
	font-family: $font-family-sans;
	cursor: pointer;
	white-space: nowrap;
	text-decoration: none;
	font-weight: bold;
	align-items: center;
	display: inline-flex;
	border-width: 1px;
	border-radius: 2px;
	border-style: solid;
	box-sizing: border-box;
	outline: 0;
	padding: $padding-vertical-base $padding-horizontal-base;
	transition: background-color 100ms, color 100ms, border-color 100ms, box-shadow 100ms, filter 100ms;

	&--primaryProgressive {
		background-color: $color-primary;
		color: $color-base--inverted;
		border-color: $color-primary;

		&:hover {
			background-color: $color-primary--hover;
			border-color: $color-primary--hover;
		}

		&:active {
			background-color: $color-primary--active;
			border-color: $color-primary--active;
		}

		&:focus {
			box-shadow: $box-shadow-primary--focus;
		}

		&:active:focus {
			box-shadow: none;
		}
	}

	&--disabled {
		pointer-events: none;
		cursor: default;
		background-color: $background-color-filled--disabled;
		color: $color-filled--disabled;
		border-color: $border-color-base--disabled;
	}

	&--cancel {
		background-image: $svg-cancel;
	}

	&--frameless {
		border-color: transparent;
		background-color: $wmui-color-base100;

		&:hover,
		&:active,
		:not( &:hover:focus ) {
			box-shadow: none;
		}

		&:hover {
			background-color: $wmui-color-base90;
		}

		&:active {
			background-color: $wmui-color-base80;
		}

		&:focus {
			border-color: $color-primary;
			box-shadow: $box-shadow-base--focus;
		}

		&:active:focus {
			box-shadow: none;
			border-color: transparent;
		}
	}

	&--iconOnly {
		background-position: center;
		background-size: 26px;
		background-repeat: no-repeat;
		width: 46px;
		height: 40px;
		cursor: pointer;
		display: block;
	}

	&--iconOnly > #{&}__text {
		@include sr-only();
	}

	&--primaryProgressive#{&}--pressed {
		background-color: $color-primary--active;
	}

	&--squary {
		border-radius: 0;
	}
}
</style>
