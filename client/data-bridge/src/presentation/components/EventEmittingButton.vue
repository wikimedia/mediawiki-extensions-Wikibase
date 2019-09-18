<template>
	<a
		class="wb-ui-event-emitting-button"
		:class="[
			`wb-ui-event-emitting-button--${this.type}`,
			{ 'wb-ui-event-emitting-button--squary': squary },
			{ 'wb-ui-event-emitting-button--pressed': isPressed },
		]"
		:href="href"
		:tabindex="href ? null : 0"
		:role="href ? 'link' : 'button'"
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
	public squary!: boolean;

	public isPressed = false;

	public handleSpacePress( event: UIEvent ) {
		if ( !this.simulateSpaceOnButton() ) {
			return;
		}
		this.preventScrollingDown( event );
		this.isPressed = true;
		this.click( event );
	}

	public handleEnterPress( event: UIEvent ) {
		this.isPressed = true;
		if ( this.thereIsNoSeparateClickEvent() ) {
			this.click( event );
		}
	}

	public unpress() {
		this.isPressed = false;
	}

	public click( event: UIEvent ) {
		if ( this.preventDefault ) {
			this.preventOpeningLink( event );
		}
		this.$emit( 'click', event );
	}

	private preventOpeningLink( event: UIEvent ) {
		event.preventDefault();
	}

	private preventScrollingDown( event: UIEvent ) {
		event.preventDefault();
	}

	private thereIsNoSeparateClickEvent() {
		return this.href === null;
	}

	private simulateSpaceOnButton() {
		return this.href === null;
	}
}
</script>
<style lang="scss">
$block: '.wb-ui-event-emitting-button';

%textButton {
	font-family: $font-family-sans;
	display: inline-flex;
	cursor: pointer;
	white-space: nowrap;
	text-decoration: none;
	font-weight: bold;
	align-items: center;
}

%framed {
	border-width: 1px;
	border-radius: 2px;
	border-style: solid;
	box-sizing: border-box;
	padding: $padding-vertical-base $padding-horizontal-base;
}

#{$block} {
	transition: background-color 100ms, color 100ms, border-color 100ms, box-shadow 100ms, filter 100ms;

	&--primaryProgressive {
		@extend %textButton;
		@extend %framed;
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

	&--primaryProgressive#{&}--pressed {
		background-color: $color-primary--active;
	}

	&--squary {
		border-radius: 0;
	}
}
</style>
