<template>
	<div
		class="wb-ui-radio-input"
	>
		<input
			:name="name"
			:value="htmlValue"
			type="radio"
			:id="id"
			:checked="initiallyChecked"
			:disabled="disabled"
			@change="$emit('input', $event.target.value)"
		>
		<span />
		<label :for="id">
			<span class="wb-ui-radio-input__main-label">
				<!-- @slot This slot is required. It expects the label text -->
				<slot name="label" />
			</span>
			<span class="wb-ui-radio-input__description">
				<!-- @slot This slot is optional. It can hold additional help text -->
				<slot name="description" />
			</span>
		</label>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent( {
	name: 'RadioInput',
	data() {
		return {
			id: `wb-ui-radio-input-${Math.floor( Math.random() * 1000000 )}`,
		};
	},
	props: {
		name: {
			type: String,
			required: true,
		},
		htmlValue: {
			type: String,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		value: {
			type: String,
			default: '',
		},
	},
	computed: {
		initiallyChecked(): boolean {
			return this.value === this.htmlValue;
		},
	},
	compatConfig: { MODE: 3 },
} );

</script>

<style lang="scss">
$size-input-binary: 1.5rem;
$font-size-label-input-binary: 1em;
$font-size-description-input-binary: ( 14 / 16 ) * $font-size-label-input-binary;
$line-height-label-input-binary: 1.5;
$line-height-description-input-binary: 1.6;
$color-label-input-binary: $color-base;
$color-label-input-binary--disabled: $color-base--disabled;
$background-color-input-binary--active: $color-primary--active;
$border-color-input-binary: $border-color-base--active;
$border-color-input-binary--active: $color-primary--active;
$border-color-input-binary--checked: $color-primary;
$border-color-input-binary--hover: $color-primary--hover;
$border-color-input-binary--focus: $color-primary--focus;
$border-width-radio--checked: 7px;
$box-shadow-input-binary--active: inset 0 0 0 1px $color-primary--active;

/* stylelint-disable no-descending-specificity  */
.wb-ui-radio-input {
	// Create a stacking context, so that we can use `z-index` below without leaking out
	z-index: 0;
	position: relative;
	// Prevent the fake span from jumping to the next line of text
	white-space: nowrap;
	display: flex;
	align-items: flex-start;
	// `<input type=radio>` element is visually replaced by `span` that follows
	input[ type='radio' ] {
		position: relative;
		// Support: Firefox mobile to override user-agent stylesheet, see T73750
		max-width: none;
		/**
		* Ensure the invisible input takes up the required width
		*/
		flex-shrink: 0;
		width: $size-input-binary;
		height: $size-input-binary;
		// Center the input relatively to the main label
		font-size: 1em;
		$main-label-height: $font-size-label-input-binary * $line-height-label-input-binary;
		$top-calc: calc( #{ $main-label-height / 2 } - #{ $size-input-binary / 2 } );
		top: $top-calc;
		// Having margin would offset the input from where the span is absolutely positioned,
		// making only the overlap region receive events
		margin: 0;
		// Use opacity so that VoiceOver can still identify it
		opacity: 0;
		// Render "on top of" the span, so that it's still clickable
		z-index: 1;

		& + span {
			background-color: $background-color-base;
			position: absolute;
			left: 0;
			top: $top-calc;
			box-sizing: border-box;
			width: $size-input-binary;
			height: $size-input-binary;
			border: $border-width-base $border-style-base $border-color-input-binary;
			border-radius: 100%;
			// Needed for `:focus` state's inner white circle
			&:before {
				content: ' ';
				position: absolute;
				/**
					$focusCirclePosition describes where the :before is positioned
					relative to the edge of the inner white circle
					negative values mean "outside of"
				*/
				$focusCirclePosition: -5px;
				top: $focusCirclePosition;
				left: $focusCirclePosition;
				right: $focusCirclePosition;
				bottom: $focusCirclePosition;
				border: $border-width-base $border-style-base transparent;
				border-radius: 100%;
			}
		}

		&:disabled {
			& + span {
				background-color: $background-color-filled--disabled;
				border-color: $border-color-base--disabled;
			}

			&:checked + span {
				background-color: $background-color-base;
			}
		}

		&:checked {
			& + span,
			&:hover + span,
			&:focus:hover + span {
				border-width: $border-width-radio--checked;
			}
		}

		&:not( :disabled ) {
			cursor: pointer;

			& + span {
				cursor: pointer;
				/* stylelint-disable value-list-comma-newline-after  */
				transition: background-color $transition-base,
					border-color $transition-base,
					border-width $transition-base;
				/* stylelint-enable value-list-comma-newline-after */
			}

			// `:focus` has to come before `:hover` so we get `:hover`’s border-color for `:focus:hover`
			&:focus + span {
				border-color: $border-color-input-binary--focus;
			}

			&:focus:not( :checked ) + span {
				border-width: 2px;
			}

			&:hover + span {
				border-color: $border-color-input-binary--hover;
			}

			&:active + span {
				background-color: $background-color-input-binary--active;
				border-color: $border-color-input-binary--active;
			}

			&:checked {
				& + span {
					border-color: $border-color-input-binary--checked;
				}

				// `:focus` has to come first, otherwise a specificity race with `:hover:focus` etc is necessary
				&:focus + span {
					&:before {
						border-color: $background-color-base;
					}
				}

				&:hover + span {
					border-color: $border-color-input-binary--hover;
				}

				&:active + span {
					border-color: $border-color-input-binary--active;
					box-shadow: $box-shadow-input-binary--active;

					&:before {
						border-color: $border-color-input-binary--active;
					}
				}
			}
		}
	}

	label {
		padding-left: $base-spacing-unit;
		display: flex;
		flex-direction: column;
		white-space: normal;
		color: $color-label-input-binary;
		cursor: pointer;

		.wb-ui-radio-input__main-label {
			font-size: $font-size-label-input-binary;
			line-height: $line-height-label-input-binary;
		}

		.wb-ui-radio-input__description {
			font-size: $font-size-description-input-binary;
			line-height: $line-height-description-input-binary;
		}
	}

	input[ type='radio' ]:disabled ~ label {
		color: $color-label-input-binary--disabled;
		cursor: default;
	}
}
</style>
