<template>
	<div
		class="wikibase-wbui2025-snak-value"
		:data-snak-hash="snak.hash"
		:class="snakValueClass"
		tabindex="0"
	>
		<span class="snakValue" v-html="snakValueHtmlForHash( snak.hash )"></span>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025SnakValue',
	props: {
		snak: {
			type: Object,
			required: true
		}
	},
	computed: {
		snakValueClass() {
			return {
				'wikibase-wbui2025-snak-value--error-message': wbui2025.store.snakValueHtmlForHashHasError( this.snak.hash ),
				'wikibase-wbui2025-media-value': this.snak.datatype === 'commonsMedia',
				'wikibase-wbui2025-time-value': this.snak.datatype === 'time',
				'wikibase-wbui2025-globe-coordinate-value': this.snak.datatype === 'globe-coordinate',
				'wikibase-wbui2025-tabular-data-value': this.snak.datatype === 'tabular-data',
				'wikibase-wbui2025-geo-shape-value': this.snak.datatype === 'geo-shape',
				'wikibase-wbui2025-musical-notation-value': this.snak.datatype === 'musical-notation',
				'wikibase-wbui2025-math-value': this.snak.datatype === 'math',
				'wikibase-wbui2025-quantity-value': this.snak.datatype === 'quantity'
			};
		}
	},
	methods: {
		snakValueHtmlForHash( hash ) {
			if ( wbui2025.store.snakValueHtmlForHashHasError( hash ) ) {
				return mw.message( 'wikibase-undisplayable-value' ).parse();
			}
			return wbui2025.store.snakValueHtmlForHash( hash );
		}
	},

	mounted() {
		if ( this.snak.datatype === 'globe-coordinate' ) {
			wbui2025.util.initKartographerPreview( this.$el );
		}
	},

	updated() {
		if ( this.snak.datatype === 'globe-coordinate' ) {
			wbui2025.util.initKartographerPreview( this.$el );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.mw-body-content .wikibase-wbui2025-snak-value .snakValue a.external.free {
	word-wrap: anywhere;
}

.wikibase-wbui2025-time-value {
	.wb-calendar-name {
		font-style: italic;
		padding-left: 0.5em;
	}
}

.wikibase-wbui2025-media-value {
	gap: 1rem;
	margin: 0;
	display: block;

	.thumb {
		margin: 0;
		padding: 0;
		width: 100%;

		img {
			max-height: 13.4375rem;
			max-width: 10.0625rem;
			height: auto;
			width: auto;
			margin: 0;
			padding: 0;
		}
	}

	.commons-media-caption {
		padding-top: 0.625rem;
		clear: both;
		font-size: @font-size-medium;
		line-height: 1.6rem;
		letter-spacing: -0.003rem;
	}
}

.wikibase-wbui2025-globe-coordinate-value {
	> .snakValue {
		flex: 1 1 auto;
		display: block;
		min-width: 0;
	}

	> .snakValue > div {
		display: flex;
		flex-direction: column;
	}

	.wikibase-kartographer-caption {
		order: 1;
		line-height: 1.25rem;
	}

	.mw-parser-output {
		order: 0;
	}

	a.mw-kartographer-map {
		width: 100%;
		max-width: 310px;
	}
}

.wikibase-wbui2025-main-snak:has( .wikibase-wbui2025-media-value ) {
	align-items: flex-start;
}

.wikibase-wbui2025-main-snak:has( .wikibase-wbui2025-globe-coordinate-value ) {
	align-items: flex-start;
}

.wikibase-wbui2025-snak-value {
	display: inline-flex;
	justify-content: flex-start;
	flex-grow: 1;
	align-items: center;
	margin: 0;
	padding: @spacing-0;

	// In cases of long, non-wrapping snak values, make them horizontally-scrollable
	// and contained within the parent element
	overflow-x: auto;

	// Adds a fade to the overflowing value to indicate it is scrollable
	mask-image: linear-gradient( to left, transparent 0, @color-emphasized 3em );
	mask-repeat: no-repeat;
	mask-position: 100% 0;
	padding-right: 3em;

	.wb-format-error {
		display: block;
		font-size: @font-size-small;
		margin-top: @spacing-50;
	}

	.wikibase-snakview-variation-novaluesnak,
	.wikibase-snakview-variation-somevaluesnak {
		color: @color-placeholder;
		font-family: 'Inter', sans-serif;
		font-weight: 500;
		font-size: 1rem;
		line-height: 1.25;
	}
}

.wikibase-wbui2025-snak-value--error-message {
	color: @color-placeholder;
}
</style>
