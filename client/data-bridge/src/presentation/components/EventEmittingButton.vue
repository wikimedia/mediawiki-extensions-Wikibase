<template>
	<a
		class="wb-ui-event-emitting-button"
		:class="[
			`wb-ui-event-emitting-button--${this.type}`,
			`wb-ui-event-emitting-button--size-${this.size}`,
			{
				'wb-ui-event-emitting-button--squary': squary,
				'wb-ui-event-emitting-button--pressed': isPressed,
				'wb-ui-event-emitting-button--iconOnly': isIconOnly,
				'wb-ui-event-emitting-button--frameless': isFrameless,
				'wb-ui-event-emitting-button--disabled': disabled,
			},
		]"
		:href="href"
		:tabindex="tabindex"
		:role="href ? 'link' : 'button'"
		:aria-disabled="disabled ? 'true' : null"
		:title="message"
		:target="opensInNewTab ? '_blank' : null"
		:rel="opensInNewTab ? 'noreferrer noopener' : null"
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
import { defineComponent } from 'vue';

const validTypes = [
	'primaryProgressive',
	'close',
	'neutral',
	'back',
	'link',
];

const framelessTypes = [
	'close',
	'back',
	'link',
];

const imageOnlyTypes = [
	'close',
	'back',
];

const validSizes = [
	'M',
	'L',
	'XL',
];

export default defineComponent( {
	name: 'EventEmittingButton',
	emits: [ 'click' ],
	props: {
		type: {
			type: String,
			required: true,
			validator: ( type: string ) => validTypes.indexOf( type ) !== -1,
		},
		size: {
			type: String,
			required: true,
			validator: ( size: string ) => validSizes.includes( size ),
		},
		message: {
			required: true,
			type: String,
		},
		href: {
			type: String,
			required: false,
			default: null,
		},
		preventDefault: {
			type: Boolean,
			required: false,
			default: true,
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false,
		},
		squary: {
			type: Boolean,
			required: false,
			default: false,
		},

		/**
		 * Whether this link should open in a new tab or not.
		 * Only effective if `href` is set.
		 * `preventDefault` should usually be set to `false` as well.
		 */
		newTab: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data() {
		return {
			isPressed: false,
		};
	},
	computed: {
		isIconOnly(): boolean {
			return imageOnlyTypes.includes( this.type );
		},
		isFrameless(): boolean {
			return framelessTypes.includes( this.type );
		},
		opensInNewTab(): boolean {
			return this.href !== null && this.newTab;
		},
		tabindex(): number | null {
			if ( this.disabled ) {
				return -1;
			}

			if ( this.href ) {
				return null;
			}

			return 0;
		},
	},
	methods: {
		handleSpacePress( event: UIEvent ): void {
			if ( !this.simulateSpaceOnButton() ) {
				return;
			}
			this.preventScrollingDown( event );
			this.isPressed = true;
			this.click( event );
		},
		handleEnterPress( event: UIEvent ): void {
			this.isPressed = true;
			if ( this.thereIsNoSeparateClickEvent() ) {
				this.click( event );
			}
		},
		unpress(): void {
			this.isPressed = false;
		},
		click( event: UIEvent ): void {
			if ( this.preventDefault ) {
				this.preventOpeningLink( event );
			}
			if ( this.disabled ) {
				return;
			}
			this.$emit( 'click', event );
		},
		preventOpeningLink( event: UIEvent ): void {
			event.preventDefault();
		},
		preventScrollingDown( event: UIEvent ): void {
			event.preventDefault();
		},
		thereIsNoSeparateClickEvent(): boolean {
			return this.href === null;
		},
		simulateSpaceOnButton(): boolean {
			return this.href === null;
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>
<style lang="scss">
.wb-ui-event-emitting-button {
	font-family: $font-family-sans;
	cursor: pointer;
	white-space: nowrap;
	text-decoration: none;
	font-weight: bold;
	line-height: $line-height-text;
	align-items: center;
	justify-content: center;
	display: inline-flex;
	border-width: 1px;
	border-radius: 2px;
	border-style: solid;
	box-sizing: border-box;
	outline: 0;
	transition: background-color 100ms, color 100ms, border-color 100ms, box-shadow 100ms, filter 100ms;

	@mixin size-M {
		font-size: $font-size-bodyS;
		padding: px-to-rem( 4px ) px-to-rem( 12px ) px-to-rem( 5px );
	}

	@mixin size-L {
		font-size: $font-size-normal;
		padding: px-to-rem( 7px ) px-to-rem( 16px );
	}

	@mixin size-XL {
		font-size: $font-size-normal;
		padding: px-to-rem( 11px ) px-to-rem( 16px );
	}

	&--size-M {
		@include size-M;
	}

	&--size-L {
		@include size-L;
	}

	&--size-XL {
		@include size-XL;
	}

	@media ( max-width: $breakpoint ) {
		&--size-M {
			@include size-L;
		}

		&--size-L {
			@include size-XL;
		}
	}

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

	&--neutral {
		background-color: $background-color-framed;
		color: $color-base;
		border-color: $border-color-base;

		&:hover {
			background-color: $background-color-base;
		}

		&:active {
			background-color: $background-color-framed--active;
			color: $color-base--active;
			border-color: $border-color-base--active;
		}

		&:focus {
			background-color: $background-color-base;
			border-color: $color-primary--focus;
		}
	}

	&--disabled {
		pointer-events: none;
		cursor: default;
		background-color: $background-color-filled--disabled;
		color: $color-filled--disabled;
		border-color: $border-color-base--disabled;
	}

	&--close {
		background-image: $svg-close;
	}

	&--back {
		background-image: $svg-back;

		@at-root :root[ dir='rtl' ] & { // references dir attribute of the <html> tag
			transform: scaleX( -1 );
		}
	}

	&--link {
		color: $color-primary;

		&:hover {
			color: $color-primary--hover;
		}

		&:active {
			color: $color-primary--active;
		}

		&:focus {
			color: $color-primary--hover;
		}
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

	@mixin iconOnly-size-M {
		width: calc( #{ px-to-rem( 30px ) } + 2px );
		height: calc( #{ px-to-rem( 30px ) } + 2px );
	}

	@mixin iconOnly-size-L {
		width: $header-content-size--desktop;
		height: $header-content-size--desktop;
	}

	@mixin iconOnly-size-XL {
		width: $header-content-size--mobile;
		height: $header-content-size--mobile;
	}

	&--iconOnly {
		background-position: center;
		background-size: $button-icon-size;
		background-repeat: no-repeat;
		cursor: pointer;
		display: block;
	}

	&--iconOnly#{&}--size-M {
		@include iconOnly-size-M;
	}

	&--iconOnly#{&}--size-L {
		@include iconOnly-size-L;
	}

	&--iconOnly#{&}--size-XL {
		@include iconOnly-size-XL;
	}

	@media ( max-width: $breakpoint ) {
		&--iconOnly#{&}--size-M {
			@include iconOnly-size-L;
		}

		&--iconOnly#{&}--size-L {
			@include iconOnly-size-XL;
		}
	}

	&--iconOnly > #{&}__text {
		@include sr-only();
	}

	&--iconOnly#{&}--disabled#{&}--frameless {
		opacity: $opacity-base--disabled;
	}
	// no styles for non-frameless disabled icon button yet (currently no such type)

	&--primaryProgressive#{&}--pressed {
		background-color: $color-primary--active;
	}

	&--squary {
		border-radius: 0;
	}

	&--link#{&}--disabled {
		color: $color-base--disabled;
	}
}
</style>
