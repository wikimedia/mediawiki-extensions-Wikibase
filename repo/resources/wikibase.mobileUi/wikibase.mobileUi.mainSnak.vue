<template>
	<div class="wikibase-mex-main-snak">
		<div class="wikibase-rankselector ui-state-default">
			<span class="ui-icon ui-icon-rankselector wikibase-rankselector-normal" title="Normal rank"></span>
		</div>
		<!-- eslint-disable vue/no-v-html -->
		<div
			class="wikibase-mex-snak-value"
			:data-snak-hash="hash"
			:class="snakValueClass"
			v-html="html"
		></div>
		<!-- eslint-enable -->
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseMexMainSnak',
	props: {
		type: {
			type: String,
			required: true
		},
		hash: {
			type: String,
			required: true
		},
		html: {
			type: String,
			required: true
		}
	},
	computed: {
		snakValueClass() {
			return {
				'wikibase-mex-media-value': this.type === 'commonsMedia',
				'wikibase-mex-time-value': this.type === 'time'
			};
		}
	}
} );
</script>
