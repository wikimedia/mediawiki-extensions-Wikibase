<template>
	<wikibase-wbui2025-editable-no-value-some-value-snak-value
		:snak-key="snakKey"
		:removable="removable"
		:disabled="disabled"
		@remove-snak="$emit( 'remove-snak', snakKey )"
	>
		<wikibase-wbui2025-api-item-lookup
			ref="inputElement"
			:lookup-source="lookupSource"
			:class-name="className"
		></wikibase-wbui2025-api-item-lookup>
	</wikibase-wbui2025-editable-no-value-some-value-snak-value>
</template>

<script>
const { defineComponent } = require( 'vue' );
const WikibaseWbui2025EditableNoValueSomeValueSnakValue = require( './editableNoValueSomeValueSnakValue.vue' );
const WikibaseWbui2025ApiItemLookup = require( './apiItemLookup.vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableLookupSnakValue',
	components: {
		WikibaseWbui2025EditableNoValueSomeValueSnakValue,
		WikibaseWbui2025ApiItemLookup
	},
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
		const editSnakStoreGetter = wbui2025.store.useEditSnakStore( props.snakKey );
		const lookupSource = new wbui2025.api.SnakLookupSource( editSnakStoreGetter() );
		return {
			lookupSource
		};
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.focus();
		}
	} }
);
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

div.wikibase-wbui2025-edit-statement-snak-value {

	div.cdx-lookup {
		width: 100%;
	}

}
</style>
