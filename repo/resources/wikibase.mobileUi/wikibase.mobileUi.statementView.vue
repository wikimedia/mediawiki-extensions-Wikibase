<template>
	<div class="wikibase-mex-statement">
		<div class="wikibase-mex-statement-heading">
			<div class="wikibase-mex-statement-heading-row">
				<mex-property-name :property-id="statement.mainsnak.property"></mex-property-name>
				<div class="wikibase-mex-edit-link">
					<span class="wikibase-mex-icon-edit-small"></span>
					<a href="#" class="mex-link-heavy">edit</a>
				</div>
			</div>
		</div>
		<!-- TODO: show all statements for this property T396637 -->
		<!-- TODO: support Javascript expressions instead of passing `is...` here T396855 -->
		<mex-main-snak
			:value="statement.mainsnak.datavalue.value"
			:type="statement.mainsnak.datatype"
		></mex-main-snak>
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
const MexMainSnak = require( './wikibase.mobileUi.mainSnak.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseMexStatement',
	components: {
		MexPropertyName,
		MexMainSnak
	},
	props: {
		statement: {
			type: Object,
			required: true
		}
	}
} );
</script>
