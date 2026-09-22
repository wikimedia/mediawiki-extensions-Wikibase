<template>
	<div class="wikibase-wbui2025-references">
		<cdx-accordion v-if="hasReferences">
			<template #title>
				{{ referencesMessage }}
			</template>
			<div class="wikibase-wbui2025-reference-list wikibase-wbui2025-references-visible">
				<template v-for="reference in references" :key="reference">
					<wbui2025-reference-view-content
						:reference="reference"
						:show-indicators="showIndicators"
						:statement-id="statementId"
					>
					</wbui2025-reference-view-content>
				</template>
			</div>
		</cdx-accordion>
		<p v-else>
			<span>{{ referencesMessage }}</span>
		</p>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxAccordion } = require( '../../../codex.js' );
const Wbui2025ReferenceViewContent = require( './referenceViewContent.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025HydratedReferences',
	components: {
		CdxAccordion,
		Wbui2025ReferenceViewContent
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
		return {
			showIndicators: true
		};
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
