<template>
	<cdx-text-input
		ref="inputElement"
		v-model.trim="textvalue"
		:class="className"
	></cdx-text-input>
</template>

<script>
const { computed, defineComponent } = require( 'vue' );
const { mapWritableState } = require( 'pinia' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const { CdxTextInput } = require( '../../../codex.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableStringSnakValue',
	components: {
		CdxTextInput
	},
	props: {
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
		const valueStrategy = editSnakStoreGetter().valueStrategy;
		return {
			textvalue: computed( computedProperties.textvalue ),
			debouncedTriggerParse: mw.util.debounce( valueStrategy.triggerParse.bind( valueStrategy ), 300 )
		};
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.focus();
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
	} }
);
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-edit-statement-snak-value {

	.wikibase-wbui2025-snak-value {

		.cdx-text-input {
			width: 100%;
		}

	}

}
</style>
