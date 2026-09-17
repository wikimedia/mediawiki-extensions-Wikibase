<template>
	<div
		class="wikibase-wbui2025-references"
		:data-references="referencesDataString"
		:data-statement-id="statementId"
	>
		<details v-if="hasReferences" class="cdx-accordion">
			<summary>
				<h3 class="cdx-accordion__header">
					<span class="cdx-accordion__header__title">
						{{ referencesMessage }}
					</span>
				</h3>
			</summary>
			<div class="cdx-accordion__content">
				<template v-for="reference in references" :key="reference">
					<wbui2025-reference-view-content :reference="reference" :show-indicators="false">
					</wbui2025-reference-view-content>
				</template>
			</div>
		</details>
		<p v-else>
			<span>{{ referencesMessage }}</span>
		</p>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

const Wbui2025ReferenceViewContent = require( './referenceViewContent.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025References',
	components: {
		Wbui2025ReferenceViewContent
	},
	props: {
		referencesDataString: {
			type: String,
			required: true
		},
		referenceCount: {
			type: Number,
			required: true
		},
		references: {
			type: Object,
			required: true
		},
		statementId: {
			type: String,
			required: true
		}
	},
	computed: {
		hasReferences() {
			return this.referenceCount > 0;
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

	.cdx-accordion {
		h3.cdx-accordion__header {
			font-weight: @font-weight-normal;
			color: @color-progressive;
		}

		.cdx-accordion__content {
			padding: 0;
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
}
</style>
