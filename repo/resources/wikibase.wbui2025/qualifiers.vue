<template>
	<div
		v-if="hasQualifiers"
		class="wikibase-wbui2025-qualifiers"
	>
		<template v-for="propertyId in qualifiersOrder" :key="propertyId">
			<div
				v-for="snak in qualifiers[propertyId]"
				:key="snak"
				class="wikibase-wbui2025-qualifier"
			>
				<wbui2025-property-name :property-id="propertyId"></wbui2025-property-name>
				<wbui2025-snak-value :snak="snak"></wbui2025-snak-value>
			</div>
		</template>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025PropertyName = require( './propertyName.vue' );
const Wbui2025SnakValue = require( './snakValue.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025Qualifiers',
	components: {
		Wbui2025PropertyName,
		Wbui2025SnakValue
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
	computed: {
		qualifierCount() {
			return this.qualifiersOrder.length;
		},
		hasQualifiers() {
			return this.qualifierCount > 0;
		}
	}
} );
</script>
