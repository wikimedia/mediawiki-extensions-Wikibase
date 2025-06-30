<template>
	<div class="wikibase-mex-snak-value">
		<p v-if="type === 'string'">
			{{ loadedValue }}
		</p>
		<div v-else-if="type === 'commonsMedia'" class="wikibase-mex-media-value">
			<div class="wikibase-mex-media-value-row">
				<div class="wikibase-mex-media-preview">
					<div class="wikibase-rankselector ui-state-default">
						<span class="ui-icon ui-icon-rankselector wikibase-rankselector-normal" title="Normal rank"></span>
					</div>
					<img :src="loadedValue.src" :alt="loadedValue.altText">
				</div>
				<div class="wikibase-mex-media-info">
					<p><a href="#" class="mex-link-heavy">{{ loadedValue.filename }}</a></p>
					<p>{{ loadedValue.widthPx }} <span>×</span> {{ loadedValue.heightPx }}; {{ loadedValue.fileSizeKb }} KB</p>
				</div>
			</div>
		</div>
		<p v-else>
			Unable to render datatype {{ type }}
		</p>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseMexMainSnak',
	props: {
		value: {
			type: [ String, Object ],
			required: true
		},
		type: {
			type: String,
			required: true
		}
	},
	computed: {
		loadedValue() {
			if ( this.type === 'commonsMedia' ) {
				// The CommonsInlineImageFormatter loads additional metadata about commonsMedia objects.
				// We will need similar such functionality here - T398314
				return {
					src: 'https://upload.wikimedia.org/wikipedia/commons/thumb/' +
						'd/d5/Rihanna-signature.svg/250px-Rihanna-signature.svg.png',
					altText: 'Some alt text',
					filename: this.value,
					widthPx: 348,
					heightPx: 178,
					fileSizeKb: 9
				};
			} else {
				return this.value;
			}
		}
	}
} );
</script>
