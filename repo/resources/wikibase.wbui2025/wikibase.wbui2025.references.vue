<template>
	<div class="wikibase-wbui2025-references">
		<p :class="{ 'wikibase-wbui2025-clickable': hasReferences }" @click="showReferences = !showReferences">
			<span v-if="hasReferences" :class="{ 'wikibase-wbui2025-icon-expand-x-small': !showReferences, 'wikibase-wbui2025-icon-collapse-x-small': showReferences }"></span>
			<a
				v-if="hasReferences"
				href="javascript: void(0)"
				class="wbui2025-link">{{ referencesMessage }}</a>
			<span v-else>{{ referencesMessage }}</span>
		</p>
		<div
			v-if="hasReferences"
			class="wikibase-wbui2025-reference-list"
			:class="{ 'wikibase-wbui2025-references-visible': showReferences }">
			<template v-for="reference in references" :key="reference">
				<template v-for="snak in reference['snaks-order']" :key="snak">
					<template v-for="propertysnak in reference.snaks[snak]" :key="propertysnak">
						<!-- eslint-disable vue/no-v-html -->
						<div
							class="wikibase-wbui2025-snak-value"
							:data-snak-hash="propertysnak.hash"
							v-html="snakHtml( propertysnak )"
						></div>
						<!-- eslint-enable -->
					</template>
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
	name: 'WikibaseWbui2025References',
	props: {
		references: {
			type: Array,
			required: true
		}
	},
	setup() {
		return {
			snakHtml
		};
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
