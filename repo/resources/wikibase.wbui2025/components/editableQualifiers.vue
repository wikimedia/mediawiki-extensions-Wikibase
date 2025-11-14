<template>
	<div
		v-if="hasQualifiers"
		class="wikibase-wbui2025-qualifiers"
	>
		<template v-for="propertyId in qualifiersOrder" :key="propertyId">
			<div
				v-for="snakKey in qualifiers[propertyId]"
				:key="snakKey"
				class="wikibase-wbui2025-edit-qualifier"
			>
				<wbui2025-editable-snak
					:snak-key="snakKey"
					:property-id="propertyId"
					@remove-snak-from-property="removeSnakFromProperty"
				></wbui2025-editable-snak>
			</div>
		</template>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025EditableSnak = require( './editableSnak.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableQualifiers',
	components: {
		Wbui2025EditableSnak
	},
	props: {
		qualifiers: {
			type: Object,
			required: true
		},
		qualifiersOrder: {
			type: Array,
			required: true
		}
	},
	emits: [ 'remove-snak-from-property' ],
	computed: {
		qualifierCount() {
			return this.qualifiersOrder.length;
		},
		hasQualifiers() {
			return this.qualifierCount > 0;
		}
	},
	methods: {
		removeSnakFromProperty( propertyId, snakKey ) {
			this.$emit( 'remove-snak-from-property', propertyId, snakKey );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

div.wikibase-wbui2025-edit-qualifier {
	background-color: @background-color-neutral-subtle;
	padding: @spacing-25 @spacing-75 @spacing-25 @spacing-25;
	gap: @spacing-75;
	align-self: stretch;
}
</style>
