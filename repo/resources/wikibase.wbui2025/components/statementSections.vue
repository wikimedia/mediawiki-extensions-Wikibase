<template>
	<div
		class="wikibase-wbui2025-statement-section"
		:data-section-key="sectionKey"
		:data-props="implode( ',', propertyList )"
	>
		<div class="wikibase-wbui2025-statement-section-heading" v-html="sectionHeadingHtml"></div>
		<div class="wikibase-wbui2025-statement-section-content">
			<div
				v-for="propertyId in propertyIds"
				:id="concat( 'wikibase-wbui2025-statementwrapper-', propertyId )"
				:key="propertyId">
				<wbui2025-statement-group-view
					:property-id="propertyId"
					:entity-id="entityId"
				></wbui2025-statement-group-view>
			</div>
			<wbui2025-add-statement-button
				v-if="javaScriptLoaded"
				:entity-id="entityId"
				:section-key="sectionKey"
			></wbui2025-add-statement-button>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025StatementGroupView = require( './statementGroupView.vue' );
const Wbui2025AddStatementButton = require( './addStatementButton.vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025StatementSections',
	components: {
		Wbui2025AddStatementButton,
		Wbui2025StatementGroupView
	},
	props: {
		sectionHeadingHtml: {
			type: String,
			required: true
		},
		sectionKey: {
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
	data() {
		return {
			javaScriptLoaded: true
		};
	},
	computed: {
		propertyIds() {
			return wbui2025.store.getPropertyIdsForStatementSection( this.sectionKey );
		}
	},
	methods: {
		concat: wbui2025.util.concat,
		implode: wbui2025.util.implode
	},
	beforeMount: function () {
		wbui2025.store.useSavedStatementsStore().setPropertyIdsForStatementSection( this.sectionKey, this.propertyList );
	}
} );
</script>
