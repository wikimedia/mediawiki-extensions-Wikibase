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
				<wbui2025-snak-value
					:snak="snak"
					:statement-id="statementId"
					:is-qualifier="true"
				></wbui2025-snak-value>
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
		},
		statementId: {
			type: String,
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

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-qualifiers {
	display: flex;
	padding: @spacing-30 @spacing-0 @spacing-65 @spacing-0;
	flex-direction: column;
	gap: @spacing-25;
}

.wikibase-wbui2025-qualifier {
	background-color: @background-color-neutral-subtle;
	display: flex;
	padding: @spacing-25 @spacing-75 @spacing-25 @spacing-200;
	gap: @spacing-75;
	align-self: stretch;

	.wikibase-wbui2025-property-name {
		width: @size-800;
		white-space: nowrap;
		text-overflow: ellipsis;
		overflow: hidden;
	}

	.wikibase-wbui2025-snak-value {
		flex: 1 0 0;
	}
}
</style>
