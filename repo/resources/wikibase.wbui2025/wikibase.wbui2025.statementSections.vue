<template>
	<div class="wikibase-wbui2025-statement-section">
		<!-- eslint-disable vue/no-v-html -->
		<div class="wikibase-wbui2025-statement-section-heading" v-html="sectionHeadingHtml"></div>
		<!-- eslint-enable -->
		<div class="wikibase-wbui2025-statement-section-content">
			<div
				v-for="propertyId in propertyList"
				:id="concat( 'wikibase-wbui2025-statementwrapper-', propertyId )"
				:key="propertyId">
				<wbui2025-statement
					:statements="propertyStatementMap[propertyId]"
					:property-id="propertyId"
				></wbui2025-statement>
			</div>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { concat } = require( './wikibase.wbui2025.utils.js' );
const Wbui2025Statement = require( './wikibase.wbui2025.statementView.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025StatementSections',
	components: {
		Wbui2025Statement
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
		propertyStatementMap: {
			type: Object,
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
