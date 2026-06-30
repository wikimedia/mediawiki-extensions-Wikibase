<template>
	<wikibase-wbui2025-editable-no-value-some-value-snak-value
		:snak-key="snakKey"
		:removable="removable"
		:disabled="disabled"
		@remove-snak="$emit( 'remove-snak', snakKey )"
	>
		<cdx-text-area
			ref="inputElement"
			v-model="textvalue"
			autocapitalize="off"
			autosize
			rows="1"
			:disabled="disabled"
			:class="className"
			:status="status"
			@blur="onBlur"
			@focus="resizeTextarea"
			@input="removeNewLines"
			@keydown.enter.prevent
		>
		</cdx-text-area>
	</wikibase-wbui2025-editable-no-value-some-value-snak-value>
</template>

<script>
const { computed, defineComponent, ref } = require( 'vue' );
const { mapState, mapWritableState } = require( 'pinia' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const { CdxTextArea } = require( '../../../codex.js' );
const WikibaseWbui2025EditableNoValueSomeValueSnakValue = require( './editableNoValueSomeValueSnakValue.vue' );
const resizeTextareaMixin = require( '../mixins/resizeTextareaMixin.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableStringSnakValue',
	components: {
		CdxTextArea,
		WikibaseWbui2025EditableNoValueSomeValueSnakValue
	},
	mixins: [ resizeTextareaMixin ],
	props: {
		removable: {
			type: Boolean,
			required: false,
			default: false
		},
		disabled: {
			type: Boolean,
			required: true
		},
		snakKey: {
			type: String,
			required: true
		},
		className: {
			type: String,
			required: false,
			default: 'wikibase-wbui2025-editable-snak-value-input'
		}
	},
	emits: [ 'remove-snak' ],
	setup( props ) {
		/*
		 * Usually we use the Options API to map state and actions. In this case, we need a parameterised
		 * store - we pass in the snakHash to make a snak-specific store. This forces us to use
		 * the Composition API to initialise the component.
		 */
		const editSnakStoreGetter = wbui2025.store.useEditSnakStore( props.snakKey );
		const computedProperties = mapWritableState( editSnakStoreGetter, [
			'textvalue'
		] );
		const computedEditSnakStoreGetters = mapState( editSnakStoreGetter, [
			'isIncomplete'
		] );
		const valueStrategy = editSnakStoreGetter().valueStrategy;
		const inputHadFocus = ref( false );
		return {
			textvalue: computed( computedProperties.textvalue ),
			debouncedTriggerParse: mw.util.debounce( valueStrategy.triggerParse.bind( valueStrategy ), 300 ),
			isIncomplete: computed( computedEditSnakStoreGetters.isIncomplete ),
			inputHadFocus
		};
	},
	computed: {
		status() {
			return this.inputHadFocus && this.isIncomplete ? 'error' : 'default';
		}
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.focus();
		},

		onBlur() {
			this.inputHadFocus = true;
		},

		removeNewLines( inputEvent ) {
			this.textvalue = inputEvent.target.value.replace( /\r?\n/g, '' );
			// eslint-disable-next-line vue/no-undef-properties
			this.resizeTextarea();
		}
	},
	watch: {
		textvalue: {
			handler( newValue ) {
				if ( newValue === undefined ) {
					return;
				}

				this.debouncedTriggerParse( newValue );
			},
			immediate: true
		}
	},
	mounted() {
		if ( this.textvalue ) {
			this.resizeTextarea();
		}
	}
} );
</script>
