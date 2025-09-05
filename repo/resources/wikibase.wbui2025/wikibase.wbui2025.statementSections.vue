<template>
	<div class="wikibase-wbui2025-statement-section">
		<div class="wikibase-wbui2025-statement-section-heading" v-html="sectionHeadingHtml"></div>
		<div class="wikibase-wbui2025-statement-section-content">
			<div
				v-for="propertyId in propertyList"
				:id="concat( 'wikibase-wbui2025-statementwrapper-', propertyId )"
				:key="propertyId">
				<wbui2025-statement-group-view
					:property-id="propertyId"
					:entity-id="entityId"
				></wbui2025-statement-group-view>
			</div>
			<div class="wikibase-wbui2025-statement-section-add-wrapper"></div>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { concat } = require( './wikibase.wbui2025.utils.js' );
const Wbui2025StatementGroupView = require( './wikibase.wbui2025.statementGroupView.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025StatementSections',
	components: {
		Wbui2025StatementGroupView
	},
	props: {
		sectionHeadingHtml: {
			type: String,
			required: true
		},
		propertyList: {
			type: Array,
			required: true
		},
		entityId: {
			type: String,
			required: true
		}
	},
	setup() {
		return {
			concat
		};
	}
} );
</script>
