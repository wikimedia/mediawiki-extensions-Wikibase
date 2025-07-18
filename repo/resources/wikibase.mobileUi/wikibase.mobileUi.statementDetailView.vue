<template>
	<!-- TODO: Remove this debugging element T399286 -->
	<div class="wikibase-wbui2025-statement-detail-view">
		<p class="statement_data_debug">
			{{ statementDump }}
		</p>
		<mex-main-snak
			v-if="statement.mainsnak.snaktype === 'value'"
			:type="statement.mainsnak.datatype"
			:hash="statement.mainsnak.hash"
			:html="snakHtml( statement.mainsnak )"
		></mex-main-snak>
		<div v-else>
			Unsupported snak type {{ statement.mainsnak.snaktype }}
		</div>
		<mex-qualifiers
			:qualifiers="qualifiers"
			:qualifiers-order="qualifiersOrder">
		</mex-qualifiers>
		<mex-references
			:references="references"
			:show-references="false"
		></mex-references>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const MexMainSnak = require( './wikibase.mobileUi.mainSnak.vue' );
const { snakHtml } = require( './store/serverRenderedHtml.js' );
const MexReferences = require( './wikibase.mobileUi.references.vue' );
const MexQualifiers = require( './wikibase.mobileUi.qualifiers.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseMexStatementDetail',
	components: {
		MexMainSnak,
		MexReferences,
		MexQualifiers
	},
	props: {
		statement: {
			type: Object,
			required: true
		}
	},
	setup() {
		return {
			snakHtml
		};
	},
	computed: {
		references() {
			return ( this.statement.references ? this.statement.references : [] );
		},
		qualifiers() {
			return ( this.statement.qualifiers ? this.statement.qualifiers : [] );
		},
		qualifiersOrder() {
			return ( this.statement[ 'qualifiers-order' ] ? this.statement[ 'qualifiers-order' ] : [] );
		},
		statementDump() {
			return JSON.stringify( this.statement );
		}
	}
} );
</script>
