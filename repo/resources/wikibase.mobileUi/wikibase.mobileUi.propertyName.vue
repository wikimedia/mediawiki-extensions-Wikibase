<template>
	<div class="wikibase-mex-property-name">
		<!-- eslint-disable vue/no-v-html -->
		<p
			v-if="propertyLinkHtml"
			v-html="propertyLinkHtml"
		></p>
		<!-- eslint-enable -->
		<p v-else>
			<a :href="propertyUrl" class="mex-link">{{ propertyLabel }}</a>
		</p>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

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
			return null;
		},
		propertyUrl() {
			const title = new mw.Title(
				this.propertyId,
				mw.config.get( 'wgNamespaceIds', {} ).property || 120 // TODO T396634
			);
			return title.getUrl();
		},
		propertyLabel() {
			return this.propertyId; // TODO T396634
		}
	}
} );
</script>
