<template>
	<div class="wikibase-wbui2025-main-snak">
		<div class="wikibase-wbui2025-rankselector">
			<span :class="concat( 'wikibase-rankselector-', rank )" :title="rankTitleString"></span>
		</div>
		<wbui2025-snak-value :snak="mainSnak"></wbui2025-snak-value>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025SnakValue = require( './snakValue.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025MainSnak',
	components: {
		Wbui2025SnakValue
	},
	props: {
		mainSnak: {
			type: Object,
			required: true
		},
		rank: {
			type: String,
			required: true
		}
	},
	setup() {
		return {
			concat: wbui2025.util.concat
		};
	},
	computed: {
		rankTitleString() {
			// messages that can be used here:
			// * wikibase-statementview-rank-normal
			// * wikibase-statementview-rank-preferred
			// * wikibase-statementview-rank-deprecated
			return mw.msg( 'wikibase-statementview-rank-' + this.rank );
		}
	}
} );
</script>
