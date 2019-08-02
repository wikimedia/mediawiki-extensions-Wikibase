<template>
	<textarea
		:value="value"
		@input="setValue"
		@keydown.enter.prevent
	/>
</template>

<script lang="ts">
import Component from 'vue-class-component';
import Vue from 'vue';
import { Prop } from 'vue-property-decorator';

interface InputEvent {
	target: InputEventTarget;
}

interface InputEventTarget {
	value: string;
}

@Component
export default class ResizingTextField extends Vue {
	@Prop()
	public value!: string;

	public mounted() {
		this.resizeTextField();
	}

	public setValue( event: InputEvent ) {
		this.$emit( 'input', this.removeNewlines( event.target.value ) );

		// make sure that even nodiff changes to the state will update our textarea
		// a nodiff could be caused by pasting newlines only
		this.$forceUpdate();
		this.$nextTick().then( () => {
			this.resizeTextField();
		} );
	}

	private removeNewlines( value: string ): string {
		return value.replace( /\r?\n/g, '' );
	}

	public resizeTextField() {
		const textarea = this.$el as HTMLTextAreaElement;

		textarea.style.height = '0';
		const border = this.getPropertyValueInPx( textarea, 'border-top-width' )
			+ this.getPropertyValueInPx( textarea, 'border-bottom-width' );
		textarea.style.height = `${this.$el.scrollHeight + border}px`;
	}

	private getPropertyValueInPx( element: HTMLElement, property: string ) {
		return parseInt( window.getComputedStyle( element ).getPropertyValue( property ) );
	}

}
</script>
