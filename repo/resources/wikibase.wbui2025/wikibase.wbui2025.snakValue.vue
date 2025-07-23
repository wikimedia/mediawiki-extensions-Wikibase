<template>
	<div
		class="wikibase-wbui2025-snak-value"
		:data-snak-hash="snak.hash"
		:class="snakValueClass"
		v-html="snakValueHtml( snak )"
	></div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { snakValueHtml } = require( './store/serverRenderedHtml.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025SnakValue',
	props: {
		snak: {
			type: Object,
			required: true
		}
	},
	setup() {
		return {
			snakValueHtml
		};
	},
	computed: {
		snakValueClass() {
			return {
				'wikibase-wbui2025-media-value': this.snak.datatype === 'commonsMedia',
				'wikibase-wbui2025-time-value': this.snak.datatype === 'time'
			};
		}
	}
} );
</script>
