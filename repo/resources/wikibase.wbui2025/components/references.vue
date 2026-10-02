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
		<p v-else class="wikibase-wbui2025-no-references">
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

.wikibase-wbui2025-references {
	summary {
		color: @color-progressive;
		&::before {
			background-color: @color-progressive;
		}

		&:active, &:hover, &:focus {
			background: inherit;
		}
	}

	.cdx-accordion__header {
		font-weight: @font-weight-normal;
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

p.wikibase-wbui2025-no-references {
	color: @color-subtle;
	padding-top: @spacing-35;
	padding-bottom: @spacing-35;
	margin: 0;

	span {
		display: block;
		padding: @spacing-35 @spacing-30 @spacing-35 @spacing-75;
	}
}
</style>
