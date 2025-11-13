<template>
	<span class="wikibase-wbui2025-property-name">
		<span
			class="wikibase-wbui2025-property-name-link"
			:data-property-id="propertyId"
			v-html="propertyLinkHtml"
		></span>
	</span>
</template>

<script>
const { defineComponent } = require( 'vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025PropertyName',
	props: {
		propertyId: {
			type: String,
			required: true
		}
	},
	computed: {
		propertyLinkHtml() {
			return wbui2025.store.propertyLinkHtml( this.propertyId );
		}
	}
} );
</script>
