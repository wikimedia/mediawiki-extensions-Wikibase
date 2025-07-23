<template>
	<div>
		<div
			v-if="hasQualifiers"
			class="wikibase-wbui2025-qualifiers"
		>
			<p class="wikibase-wbui2025-qualifiers-header">
				{{ qualifiersMessage }}
			</p>
			<template v-for="snak in qualifiersOrder" :key="snak">
				<div
					v-for="propertysnak in qualifiers[snak]"
					:key="propertysnak"
					class="wikibase-wbui2025-qualifier"
				>
					<wbui2025-property-name :property-id="snak"></wbui2025-property-name>
					<!-- eslint-disable-next-line vue/no-useless-mustaches -->
					{{ ' ' }}
					<wbui2025-snak-value :snak="propertysnak"></wbui2025-snak-value>
				</div>
			</template>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025PropertyName = require( './wikibase.wbui2025.propertyName.vue' );
const Wbui2025SnakValue = require( './wikibase.wbui2025.snakValue.vue' );

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
		},
		qualifiersMessage() {
			return mw.msg( 'wikibase-statementview-qualifiers-counter', [ this.qualifierCount ] );
		}
	}
} );
</script>
