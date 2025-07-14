<template>
	<div class="wikibase-mex-main-snak">
		<div class="wikibase-rankselector ui-state-default">
			<span class="ui-icon ui-icon-rankselector wikibase-rankselector-normal" title="Normal rank"></span>
		</div>
		<!-- eslint-disable vue/no-v-html -->
		<div
			class="wikibase-mex-snak-value"
			:class="{ 'wikibase-mex-media-value': type === 'commonsMedia' }"
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
		html: {
			type: String,
			required: true
		}
	}
} );
</script>
