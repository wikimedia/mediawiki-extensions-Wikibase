<template>
	<textarea
		:value="value"
		@input="setValue"
		@keydown.enter.prevent
		:maxlength="maxLength"
	/>
</template>
<script lang="ts">
import debounce from 'lodash/debounce';
import { defineComponent } from 'vue';

interface InputEventTarget {
	value: string;
}

interface InputEvent {
	target: InputEventTarget;
}

export default defineComponent( {
	name: 'ResizingTextField',
	emits: [ 'input' ],
	props: {
		value: {
			type: String,
			required: false,
			default: '',
		},
		maxLength: {
			type: Number,
			default: null,
		},
	},
	data() {
		return {
			windowResizeHandler: undefined as ( ( ( this: Window, event: UIEvent ) => void ) | undefined ),
		};
	},
	mounted(): void {
		this.windowResizeHandler = debounce( () => this.resizeTextField(), 100 );
		window.addEventListener( 'resize', this.windowResizeHandler );
		this.resizeTextField();
	},
	unmounted(): void {
		if ( this.windowResizeHandler !== undefined ) {
			window.removeEventListener( 'resize', this.windowResizeHandler );
			this.windowResizeHandler = undefined;
		}
	},
	methods: {
		setValue( event: InputEvent ): void {
			this.$emit( 'input', this.removeNewlines( event.target.value ) );
			// make sure that even nodiff changes to the state will update our textarea
			// a nodiff could be caused by pasting newlines only
			this.$forceUpdate();
			this.$nextTick().then( () => {
				this.resizeTextField();
			} );
		},
		removeNewlines( value: string ): string {
			return value.replace( /\r?\n/g, '' );
		},
		resizeTextField(): void {
			const textarea = this.$el as HTMLTextAreaElement;
			textarea.style.height = '0';
			const border = this.getPropertyValueInPx( textarea, 'border-top-width' )
					+ this.getPropertyValueInPx( textarea, 'border-bottom-width' );
			textarea.style.height = `${this.$el.scrollHeight + border}px`;
		},
		getPropertyValueInPx( element: HTMLElement, property: string ): number {
			return parseInt( window.getComputedStyle( element ).getPropertyValue( property ) );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>
