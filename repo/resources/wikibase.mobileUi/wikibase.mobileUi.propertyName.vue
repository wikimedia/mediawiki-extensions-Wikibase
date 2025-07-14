<template>
	<div class="wikibase-mex-property-name">
		<!-- eslint-disable vue/no-v-html -->
		<p
			class="wikibase-mex-property-name-link"
			:data-property-id="propertyId"
			v-html="propertyLinkHtml"
		></p>
		<!-- eslint-enable -->
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { propertyLinkHtml } = require( './store/serverRenderedHtml.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseMexPropertyName',
	props: {
		propertyId: {
			type: String,
			required: true
		}
	},
	computed: {
		propertyLinkHtml() {
			return propertyLinkHtml( this.propertyId );
		}
	}
} );
</script>
