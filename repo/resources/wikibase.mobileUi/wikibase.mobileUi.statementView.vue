<template>
	<div class="wikibase-mex-statement">
		<div class="wikibase-mex-statement-heading">
			<div class="wikibase-mex-statement-heading-row">
				<div class="wikibase-mex-property-name">
					<!-- TODO: Use the property label the link text, and correctly derive the URL (T396633, T396634) -->
					<p><a :href="'/wiki/Property:' + statement.mainsnak.property" class="mex-link">{{ statement.mainsnak.property }}</a></p>
				</div>
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

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseMexStatement',
	props: {
		statement: {
			type: Object,
			required: true
		}
	}
} );
</script>
