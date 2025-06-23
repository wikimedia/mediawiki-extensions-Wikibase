<template>
	<div class="wikibase-mex-statement">
		<div class="wikibase-mex-statement-heading">
			<div class="wikibase-mex-statement-heading-row">
				<mex-property-name :url="propertyUrl" :label="propertyLabel"></mex-property-name>
				<div class="wikibase-mex-edit-link">
					<span class="wikibase-mex-icon-edit-small"></span>
					<a href="#" class="mex-link-heavy">edit</a>
				</div>
			</div>
		</div>
		<!-- TODO: show all statements for this property T396637 -->
		<div class="wikibase-mex-snak-value">
			<p>{{ statement.mainsnak.datavalue.value }}</p>
		</div>
		<div class="wikibase-mex-references">
			<p>
				<span class="wikibase-mex-icon-down-triangle-x-small"></span>
				<a
					v-i18n-html:wikibase-statementview-references-counter="[ statement.references ? statement.references.length : 0 ]"
					href="#"
					class="mex-link"></a>
			</p>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const MexPropertyName = require( './wikibase.mobileUi.propertyName.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseMexStatement',
	components: {
		MexPropertyName
	},
	props: {
		statement: {
			type: Object,
			required: true
		}
	},
	computed: {
		propertyUrl() {
			const title = new mw.Title(
				this.statement.mainsnak.property,
				mw.config.get( 'wgNamespaceIds', {} ).property || 120 // TODO T396634
			);
			return title.getUrl();
		},
		propertyLabel() {
			return this.statement.mainsnak.property; // TODO T396634
		}
	}
} );
</script>
