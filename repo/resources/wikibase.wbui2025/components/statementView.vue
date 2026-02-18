<template>
	<div :id="statementId" :class="activeClasses">
		<wbui2025-main-snak
			:main-snak="statement.mainsnak"
			:rank="statement.rank"
		></wbui2025-main-snak>
		<wbui2025-qualifiers
			:qualifiers="qualifiers"
			:qualifiers-order="qualifiersOrder">
		</wbui2025-qualifiers>
		<wbui2025-references
			:references="references"
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
		},
		activeClasses() {
			return [
				'wikibase-wbui2025-statement-view',
				{ 'wb-preferred': this.statement.rank === 'preferred', 'wb-deprecated': this.statement.rank === 'deprecated' }
			];
		}
	}
} );
</script>
