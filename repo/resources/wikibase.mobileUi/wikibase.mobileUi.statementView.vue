<template>
	<div class="wikibase-wbui2025-statement-group">
		<div class="wikibase-mex-statement-heading">
			<div class="wikibase-mex-statement-heading-row">
				<mex-property-name :property-id="propertyId"></mex-property-name>
				<div class="wikibase-mex-edit-link">
					<span class="wikibase-mex-icon-edit-small"></span>
					<a href="#" class="mex-link-heavy">edit</a>
				</div>
			</div>
		</div>
		<mex-statement-detail
			v-for="statement in statements"
			:key="statement"
			:statement="statement"
		></mex-statement-detail>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const MexPropertyName = require( './wikibase.mobileUi.propertyName.vue' );
const MexStatementDetail = require( './wikibase.mobileUi.statementDetailView.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseMexStatement',
	components: {
		MexPropertyName,
		MexStatementDetail
	},
	props: {
		statements: {
			type: Array,
			required: true
		},
		propertyId: {
			type: String,
			required: true
		}
	}
} );
</script>
