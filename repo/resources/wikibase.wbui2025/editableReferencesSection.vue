<template>
	<div class="wikibase-wbui2025-editable-references-section">
		<cdx-accordion
			v-if="hasReferences"
			v-model="showReferences"
			separation="minimal"
		>
			<template #title>
				{{ referencesMessage }}
			</template>
			<wbui2025-editable-reference
				v-for="reference in references"
				:key="reference"
				:reference="reference"
				@remove-reference="removeReference"
				@remove-reference-snak="removeReferenceSnak"
			></wbui2025-editable-reference>
		</cdx-accordion>
		<p v-else>
			{{ referencesMessage }}
		</p>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025EditableReference = require( './editableReference.vue' );
const { CdxAccordion } = require( '../../codex.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableReferencesSection',
	components: {
		CdxAccordion,
		Wbui2025EditableReference
	},
	props: {
		references: {
			type: Array,
			required: true
		}
	},
	emits: [ 'remove-reference', 'remove-reference-snak' ],
	data() {
		return { showReferences: false };
	},
	computed: {
		referenceCount() {
			return this.references.length;
		},
		hasReferences() {
			return this.referenceCount > 0;
		},
		referencesMessage() {
			return mw.msg( 'wikibase-statementview-references-counter', [ this.referenceCount ] );
		}
	},
	methods: {
		removeReference( reference ) {
			this.$emit( 'remove-reference', reference );
		},
		removeReferenceSnak( reference, propertyId, snakKey ) {
			this.$emit( 'remove-reference-snak', reference, propertyId, snakKey );
		}
	},
	watch: {
		referenceCount() {
			this.showReferences = true;
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-editable-references-section {
	display: flex;
	flex-direction: column;
	gap: @spacing-125;

	summary {
		color: @color-progressive;
		&::before {
			background-color: @color-progressive;
		}
	}

	.cdx-accordion__content {
		display: flex;
		flex-direction: column;
		gap: @spacing-125;
		padding-top: @spacing-125;
	}
}

</style>
