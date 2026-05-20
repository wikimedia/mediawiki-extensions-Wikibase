<template>
	<span
		class="wikibase-wbui2025-property-name"
		:class="{ 'wikibase-wbui2025-property-name--deleted': isDeletedProperty }"
	>
		<span
			class="wikibase-wbui2025-property-name-link"
			:data-property-id="propertyId"
			:data-deleted-property="isDeletedProperty"
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
		},
		isDeletedProperty() {
			return wbui2025.store.isDeletedProperty( this.propertyId );
		}
	}
} );
</script>
