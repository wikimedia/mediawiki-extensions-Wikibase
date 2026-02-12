<template>
	<div class="wikibase-wbui2025-references">
		<p :class="{ 'wikibase-wbui2025-clickable': hasReferences }" @click="showReferences = !showReferences">
			<span v-if="hasReferences" :class="{ 'wikibase-wbui2025-icon-expand-x-small': !showReferences, 'wikibase-wbui2025-icon-collapse-x-small': showReferences }"></span>
			<a
				v-if="hasReferences"
				href="javascript: void(0)"
				class="wikibase-wbui2025-link">{{ referencesMessage }}</a>
			<span v-else>{{ referencesMessage }}</span>
		</p>
		<div
			v-if="hasReferences"
			class="wikibase-wbui2025-reference-list"
			:class="{ 'wikibase-wbui2025-references-visible': showReferences }">
			<template v-for="reference in references" :key="reference">
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
								:statement-id="statementId"
								:reference-hash="reference.hash"
							></wbui2025-snak-value>
						</div>
					</template>
				</div>
			</template>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025PropertyName = require( './propertyName.vue' );
const Wbui2025SnakValue = require( './snakValue.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025References',
	components: {
		Wbui2025PropertyName,
		Wbui2025SnakValue
	},
	props: {
		references: {
			type: Array,
			required: true
		},
		statementId: {
			type: String,
			required: true
		}
	},
	data() {
		return { showReferences: false };
	},
	computed: {
		referenceCount() {
			return this.references.length;
		},
		hasReferences() {
			return this.references.length > 0;
		},
		referencesMessage() {
			return mw.msg( 'wikibase-statementview-references-counter', [ this.referenceCount ] );
		}
	}
} );
</script>
