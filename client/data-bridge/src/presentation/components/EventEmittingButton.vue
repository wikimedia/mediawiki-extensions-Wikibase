<template>
	<a
		class="wb-ui-event-emitting-button"
		:class="[ `wb-ui-event-emitting-button--${this.type}`, { 'wb-ui-event-emitting-button--squary': squary } ]"
		:href="href"
		:title="message"
		@click="click"
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

	@Prop( { required: false, default: '#', type: String } )
	public href!: string;

	@Prop( { required: false, default: true, type: Boolean } )
	public preventDefault!: boolean;

	@Prop( { required: false, default: false, type: Boolean } )
	public squary!: boolean;

	public click( event: MouseEvent ) {
		if ( this.preventDefault ) {
			event.preventDefault();
		}
		this.$emit( 'click', event );
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

	&--squary {
		border-radius: 0;
	}
}
</style>
