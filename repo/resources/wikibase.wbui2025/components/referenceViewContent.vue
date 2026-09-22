<template>
	<div class="wikibase-wbui2025-reference">
		<template v-for="propertyId in reference['snaks-order']" :key="propertyId">
			<div
				v-for="snak in reference.snaks[propertyId]"
				:key="snak"
				class="wikibase-wbui2025-reference-snak"
			>
				<wbui2025-property-name :property-id="propertyId"></wbui2025-property-name>
				<wbui2025-snak-value
					:snak="snak"
				></wbui2025-snak-value>
				<wbui2025-indicators
					v-if="showIndicators"
					:snak-hash="snak.hash"
					:statement-id="statementId"
					:reference-hash="reference.hash"
				>
				</wbui2025-indicators>
			</div>
		</template>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

const Wbui2025PropertyName = require( './propertyName.vue' );
const Wbui2025SnakValue = require( './snakValue.vue' );
const Wbui2025Indicators = require( './indicators.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025ReferenceViewContent',
	components: {
		Wbui2025PropertyName,
		Wbui2025SnakValue,
		Wbui2025Indicators
	},
	props: {
		reference: {
			type: Object,
			required: true
		},
		showIndicators: {
			type: Boolean,
			required: true
		},
		statementId: {
			type: String,
			required: false,
			default: ''
		}
	}
} );
</script>
