<template>
	<div>
		<div
			v-if="hasQualifiers"
			class="wikibase-wbui2025-qualifiers"
		>
			<p>{{ qualifiersMessage }}</p>
			<template v-for="snak in qualifiersOrder" :key="snak">
				<template v-for="propertysnak in qualifiers[snak]" :key="propertysnak">
					<!-- eslint-disable vue/no-v-html -->
					<div
						class="wikibase-wbui2025-snak-value wikibase-wbui2025-qualifier"
						:data-snak-hash="propertysnak.hash"
						v-html="snakHtml( propertysnak )"
					></div>
					<!-- eslint-enable -->
				</template>
			</template>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { snakHtml } = require( './store/serverRenderedHtml.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025Qualifiers',
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
	setup() {
		return {
			snakHtml
		};
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
