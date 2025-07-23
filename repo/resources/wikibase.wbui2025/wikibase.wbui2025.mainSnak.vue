<template>
	<div class="wikibase-wbui2025-main-snak">
		<div class="wikibase-rankselector ui-state-default">
			<span class="ui-icon ui-icon-rankselector wikibase-rankselector-normal" title="Normal rank"></span>
		</div>
		<wbui2025-snak-value :snak="mainSnak"></wbui2025-snak-value>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025SnakValue = require( './wikibase.wbui2025.snakValue.vue' );

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
		}
	}
} );
</script>
