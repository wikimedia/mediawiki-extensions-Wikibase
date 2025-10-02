<template>
	<div>
		<wbui2025-property-name :property-id="propertyId"></wbui2025-property-name>
	</div>
	<div class="wikibase-wbui2025-editable-snak-holder">
		<wbui2025-editable-snak-value
			:snak-key="snakKey"
			:property-id="propertyId"
			:removable="true"
			@remove-snak="removeSnak"
		></wbui2025-editable-snak-value>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025PropertyName = require( './wikibase.wbui2025.propertyName.vue' );
const Wbui2025EditableSnakValue = require( './wikibase.wbui2025.editableSnakValue.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableSnak',
	components: {
		Wbui2025EditableSnakValue,
		Wbui2025PropertyName
	},
	props: {
		propertyId: {
			type: String,
			required: true
		},
		snakKey: {
			type: String,
			required: true
		}
	},
	emits: [ 'remove-snak-from-property' ],
	methods: {
		removeSnak( snakKey ) {
			this.$emit( 'remove-snak-from-property', this.propertyId, snakKey );
		}
	}
} );
</script>

<style lang="less">
div.wikibase-wbui2025-editable-snak-holder {
	display: flex;
}
</style>
