<template>
	<div class="wikibase-mex-property-name">
		<p><a :href="propertyUrl" class="mex-link">{{ propertyLabel }}</a></p>
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
