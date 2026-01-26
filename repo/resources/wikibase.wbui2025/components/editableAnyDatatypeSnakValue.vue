<template>
	<component
		:is="valueStrategy.getEditableSnakComponent()"
		ref="inputElement"
		:snak-key="snakKey"
		:removable="removable"
		:class-name="className"
		:disabled="disabled"
		@remove-snak="$emit( 'remove-snak', snakKey )"
	></component>
</template>

<script>
const { computed, defineComponent } = require( 'vue' );
const { mapWritableState } = require( 'pinia' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025EditableQuantitySnakValue = require( './editableQuantitySnakValue.vue' );
const Wbui2025EditableStringSnakValue = require( './editableStringSnakValue.vue' );
const Wbui2025EditableTimeSnakValue = require( './editableTimeSnakValue.vue' );
const Wbui2025EditableLookupSnakValue = require( './editableLookupSnakValue.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableAnyDatatypeSnakValue',
	components: {
		Wbui2025EditableQuantitySnakValue,
		Wbui2025EditableStringSnakValue,
		Wbui2025EditableTimeSnakValue,
		Wbui2025EditableLookupSnakValue
	},
	props: {
		removable: {
			type: Boolean,
			required: false,
			default: false
		},
		snakKey: {
			type: String,
			required: true
		},
		className: {
			type: String,
			required: false,
			default: 'wikibase-wbui2025-editable-snak-value-input'
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false
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
			'valueStrategy'
		] );
		return {
			valueStrategy: computed( computedProperties.valueStrategy )
		};
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.focus();
		}
	}
} );
</script>
