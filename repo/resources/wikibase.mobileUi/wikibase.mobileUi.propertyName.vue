<template>
	<div class="wikibase-mex-property-name">
		<p><a :href="url" class="mex-link">{{ label }}</a></p>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseMexPropertyName',
	props: {
		url: {
			type: String,
			required: true
		},
		label: {
			type: String,
			required: true
		}
	}
} );
</script>
