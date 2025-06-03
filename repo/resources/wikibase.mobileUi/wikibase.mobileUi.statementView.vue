<template>
	<div class="wikibase-mex-statement">
		<div class="wikibase-mex-statement-heading">
			<div class="wikibase-mex-statement-heading-row">
				<div class="wikibase-mex-property-name">
					<p><a href="#" class="mex-link">country of citizenship</a></p>
				</div>
				<div class="wikibase-mex-edit-link">
					<cdx-icon
						:icon="cdxIconEdit"
						size="small"
					></cdx-icon>
					<a href="#" class="mex-link-heavy">edit</a>
				</div>
			</div>
		</div>
		<div class="wikibase-mex-snak-value">
			<p><a href="#" class="mex-link">Barbados</a></p>
		</div>
		<div class="wikibase-mex-references">
			<p>
				<cdx-icon
					:icon="cdxIconDownTriangle"
					size="x-small"
				></cdx-icon>
				<a href="#" class="mex-link">1 reference</a>
			</p>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxIcon } = require( '@wikimedia/codex' );
const {
	cdxIconDownTriangle,
	cdxIconEdit
} = require( '../../wikibase.mobileUi.icons.json' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'ScopedTypeaheadSearch',
	components: {
		CdxIcon
	},
	setup() {
		return {
			cdxIconDownTriangle,
			cdxIconEdit
		};
	}
} );
</script>
