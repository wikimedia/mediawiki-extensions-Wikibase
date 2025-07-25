<template>
	<div class="wikibase-wbui2025-statement-group">
		<div class="wikibase-wbui2025-statement-heading">
			<div class="wikibase-wbui2025-statement-heading-row">
				<wbui2025-property-name :property-id="propertyId"></wbui2025-property-name>
				<div class="wikibase-wbui2025-edit-link">
					<span class="wikibase-wbui2025-icon-edit-small"></span>
					<a href="#" class="wbui2025-link-heavy">edit</a>
				</div>
			</div>
		</div>
		<wbui2025-statement-detail
			v-for="statement in statements"
			:key="statement"
			:statement="statement"
		></wbui2025-statement-detail>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025PropertyName = require( './wikibase.wbui2025.propertyName.vue' );
const Wbui2025StatementDetail = require( './wikibase.wbui2025.statementDetailView.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025Statement',
	components: {
		Wbui2025PropertyName,
		Wbui2025StatementDetail
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
