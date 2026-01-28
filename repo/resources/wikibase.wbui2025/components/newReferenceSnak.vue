<template>
	<div class="wikibase-wbui2025-new-reference-snak-property">
		<wikibase-wbui2025-property-lookup
			ref="propertyLookup"
			@update:selected="onPropertySelection"
		></wikibase-wbui2025-property-lookup>
		<cdx-button
			weight="quiet"
			:aria-label="$i18n( 'wikibase-remove' )"
			@click="removeSnak"
		>
			<cdx-icon :icon="cdxIconTrash"></cdx-icon>
		</cdx-button>
	</div>
	<wikibase-wbui2025-editable-any-datatype-snak-value
		:snak-key="snakKey"
		:removable="true"
		:disabled="snakValueDisabled"
		@remove-snak="removeSnak"
	>
	</wikibase-wbui2025-editable-any-datatype-snak-value>
</template>

<script>
const { defineComponent, nextTick } = require( 'vue' );
const { cdxIconTrash } = require( '../icons.json' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const WikibaseWbui2025PropertyLookup = require( './propertyLookup.vue' );
const WikibaseWbui2025EditableAnyDatatypeSnakValue = require( './editableAnyDatatypeSnakValue.vue' );
const { CdxButton, CdxIcon } = require( '../../../codex.js' );

module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025NewReferenceSnak',
	components: {
		CdxButton,
		CdxIcon,
		WikibaseWbui2025PropertyLookup,
		WikibaseWbui2025EditableAnyDatatypeSnakValue
	},
	props: {
		snakKey: {
			type: String,
			required: true
		}
	},
	emits: [ 'remove-snak' ],
	data() {
		return {
			cdxIconTrash,
			snakValueDisabled: true
		};
	},
	methods: {
		onPropertySelection( propertyId, propertyData ) {
			const snakStore = wbui2025.store.useEditSnakStore( this.snakKey )();
			snakStore.setNewPropertyAndDatatype( propertyId, ( propertyData && propertyData.datatype ) );
			this.snakValueDisabled = !propertyId;
		},
		removeSnak() {
			this.$emit( 'remove-snak', this.snakKey );
		}
	},
	mounted() {
		nextTick( () => {
			this.$refs.propertyLookup.focus();
		} );
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-new-reference-snak-property {
	display: flex;
	flex-direction: row;
}
</style>
