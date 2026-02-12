<template>
	<div :id="statementId" class="wikibase-wbui2025-statement-view">
		<wbui2025-main-snak
			:main-snak="statement.mainsnak"
			:rank="statement.rank"
			:statement-id="statementId"
		></wbui2025-main-snak>
		<wbui2025-qualifiers
			:qualifiers="qualifiers"
			:qualifiers-order="qualifiersOrder"
			:statement-id="statementId"
		></wbui2025-qualifiers>
		<wbui2025-references
			:references="references"
			:statement-id="statementId"
		></wbui2025-references>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025MainSnak = require( './mainSnak.vue' );
const Wbui2025References = require( './references.vue' );
const Wbui2025Qualifiers = require( './qualifiers.vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025StatementView',
	components: {
		Wbui2025MainSnak,
		Wbui2025References,
		Wbui2025Qualifiers
	},
	props: {
		statementId: {
			type: String,
			required: true
		}
	},
	computed: {
		references() {
			return this.statement.references ? this.statement.references : [];
		},
		qualifiers() {
			return this.statement.qualifiers ? this.statement.qualifiers : {};
		},
		qualifiersOrder() {
			return this.statement[ 'qualifiers-order' ] ? this.statement[ 'qualifiers-order' ] : [];
		},
		statement() {
			return wbui2025.store.getStatementById( this.statementId );
		}
	}
} );
</script>
