<template>
	<div class="wikibase-wbui2025-references">
		<p :class="{ 'wikibase-wbui2025-clickable': hasReferences }" @click="showReferences = !showReferences">
			<span v-if="hasReferences" :class="{ 'wikibase-wbui2025-icon-expand-x-small': !showReferences, 'wikibase-wbui2025-icon-collapse-x-small': showReferences }"></span>
			<a
				v-if="hasReferences"
				href="javascript: void(0)"
				class="wikibase-wbui2025-link">{{ referencesMessage }}</a>
			<span v-else>{{ referencesMessage }}</span>
		</p>
		<div
			v-if="hasReferences"
			class="wikibase-wbui2025-reference-list"
			:class="{ 'wikibase-wbui2025-references-visible': showReferences }">
			<template v-for="reference in references" :key="reference">
				<div class="wikibase-wbui2025-reference">
					<template v-for="propertyId in reference['snaks-order']" :key="propertyId">
						<div
							v-for="snak in reference.snaks[propertyId]"
							:key="snak"
							class="wikibase-wbui2025-reference-snak"
						>
							<wbui2025-property-name :property-id="propertyId"></wbui2025-property-name>
							<wbui2025-snak-value
								:snak="snak"
							></wbui2025-snak-value>
							<wbui2025-indicators
								v-if="showIndicators"
								:snak-hash="snak.hash"
								:statement-id="statementId"
								:reference-hash="reference.hash"
							>
							</wbui2025-indicators>
						</div>
					</template>
				</div>
			</template>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025PropertyName = require( './propertyName.vue' );
const Wbui2025SnakValue = require( './snakValue.vue' );
const Wbui2025Indicators = require( './indicators.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025References',
	components: {
		Wbui2025PropertyName,
		Wbui2025SnakValue,
		Wbui2025Indicators
	},
	props: {
		references: {
			type: Array,
			required: true
		},
		statementId: {
			type: String,
			required: true
		}
	},
	data() {
		return {
			showReferences: false,
			showIndicators: true
		};
	},
	computed: {
		referenceCount() {
			return this.references.length;
		},
		hasReferences() {
			return this.references.length > 0;
		},
		referencesMessage() {
			return mw.msg( 'wikibase-statementview-references-counter', [ this.referenceCount ] );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-references,
.wikibase-wbui2025-editable-references-section {
	p {
		padding-top: @spacing-35;
		padding-bottom: @spacing-35;
		margin: 0;
		display: flex;
		align-items: center;

		span {
			padding: @spacing-35 @spacing-30 @spacing-35 @spacing-75;
		}

		.cdx-icon {
			vertical-align: middle;
		}

		.wikibase-wbui2025-link {
			padding-top: 0;
			padding-bottom: @spacing-35;
		}
	}

	div.wikibase-wbui2025-reference-list {
		display: none;

		&.wikibase-wbui2025-references-visible {
			display: inherit;
		}
	}

	.wikibase-wbui2025-reference {
		background-color: @background-color-neutral-subtle;

		&:not( :last-child ) {
			margin-bottom: @spacing-125;
		}
	}

	.wikibase-wbui2025-reference-snak {
		display: flex;
		padding: @spacing-75 @spacing-75 @spacing-75 @spacing-100;
		align-items: flex-start;
		gap: @spacing-75;
		align-self: stretch;

		&:has(.wikibase-wbui2025-indicators) {
			padding-right: 0;
		}

		.wikibase-wbui2025-property-name-link {
			padding: 0;
			width: @size-800;
			display: flex;
			align-items: flex-end;
			gap: 6px;

			& > a {
				overflow: hidden;
				text-overflow: @text-overflow-ellipsis;
				white-space: nowrap;
			}
		}
	}

	.wikibase-wbui2025-snak-value {
		div.wikibase-snakview div {
			display: inherit;
		}

		>a {
			display: unset;
			padding-left: unset;
		}
	}

	.wikibase-wbui2025-clickable {
		cursor: pointer;
	}

	.wikibase-wbui2025-icon-expand-x-small {
		.cdx-mixin-css-icon( @cdx-icon-expand, @param-size-icon: @size-icon-x-small );
		background-origin: content-box;
		mask-origin: content-box;
	}

	.wikibase-wbui2025-icon-collapse-x-small {
		.cdx-mixin-css-icon(@cdx-icon-collapse, @param-size-icon: @size-icon-x-small );
		background-origin: content-box;
		mask-origin: content-box;
	}
}
</style>
