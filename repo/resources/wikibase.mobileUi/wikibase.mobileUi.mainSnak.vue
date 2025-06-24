<template>
	<div class="wikibase-mex-snak-value">
		<p v-if="isstring">
			{{ value }}
		</p>
		<div v-if="iscommonsmedia" class="wikibase-mex-media-value">
			<div class="wikibase-mex-media-value-row">
				<div class="wikibase-mex-media-preview">
					<div class="wikibase-rankselector ui-state-default">
						<span class="ui-icon ui-icon-rankselector wikibase-rankselector-normal" title="Normal rank"></span>
					</div>
					<img :src="mediainfo.src" :alt="mediainfo.altText">
				</div>
				<div class="wikibase-mex-media-info">
					<p><a href="#" class="mex-link-heavy">{{ mediainfo.filename }}</a></p>
					<p>{{ mediainfo.widthPx }} <span>×</span> {{ mediainfo.heightPx }}; {{ mediainfo.fileSizeKb }} KB</p>
				</div>
			</div>
		</div>
		<!-- php-vuejs-templating does not currently support v-else-if - have to make some compromise here -->
		<p v-if="isunknowntype">
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
		// When we have support for complex expressions we can remove these T396855
		isstring() {
			return this.type === 'string';
		},
		iscommonsmedia() {
			return this.type === 'commonsMedia';
		},
		isunknowntype() {
			return !this.isstring && !this.iscommonsmedia;
		},
		// When we have support for computed properties (T397223) we won't need
		// do duplicate the information in Javascript and PHP (ItemView.php)
		// and when we have real data (T396858) we can hopefully dispense with
		// hard-coding altogether.
		mediainfo() {
			return {
				src: 'https://upload.wikimedia.org/wikipedia/commons/thumb/' +
					'd/d5/Rihanna-signature.svg/250px-Rihanna-signature.svg.png',
				altText: 'Some alt text',
				filename: 'Rihanna-signature.svg',
				widthPx: 348,
				heightPx: 178,
				fileSizeKb: 9
			};
		}
	}
} );
</script>
