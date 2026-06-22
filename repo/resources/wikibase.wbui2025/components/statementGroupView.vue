<template>
	<div class="wikibase-wbui2025-statement-group">
		<div v-if="showModalEditForm" class="modal-statement-edit-form-anchor">
			<wbui2025-edit-statement-group
				:property-id="propertyId"
				:entity-id="entityId"
				@hide="hideEditForm"
			></wbui2025-edit-statement-group>
		</div>
		<div class="wikibase-wbui2025-statement-heading">
			<div class="wikibase-wbui2025-statement-heading-row">
				<p>
					<wbui2025-property-name :property-id="propertyId"></wbui2025-property-name>
				</p>
				<div
					class="wikibase-wbui2025-edit-link"
					:class="{
						'wikibase-wbui2025-link': !isDeletedProperty,
						'wikibase-wbui2025-edit-link--deleted-property': isDeletedProperty
					}"
					@click="showEditForm"
				>
					<span class="wikibase-wbui2025-icon-edit-small"></span>
					<span class="wikibase-wbui2025-link-heavy">
						{{ $i18n( 'wikibase-edit' ) }}
					</span>
				</div>
			</div>
		</div>
		<wbui2025-statement-view
			v-for="statement in statements"
			:key="statement"
			:statement-id="statement.id"
		></wbui2025-statement-view>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025PropertyName = require( './propertyName.vue' );
const Wbui2025StatementView = require( './statementView.vue' );
const Wbui2025EditStatementGroup = require( './editStatementGroup.vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025StatementGroupView',
	components: {
		Wbui2025PropertyName,
		Wbui2025StatementView,
		Wbui2025EditStatementGroup
	},
	props: {
		entityId: {
			type: String,
			required: true
		},
		propertyId: {
			type: String,
			required: true
		}
	},
	data() {
		return {
			showModalEditForm: false
		};
	},
	computed: {
		statements() {
			return wbui2025.store.getStatementsForProperty( this.propertyId );
		},
		isDeletedProperty() {
			return wbui2025.store.isDeletedProperty( this.propertyId );
		}
	},
	methods: {
		showEditForm() {
			if ( !this.isDeletedProperty ) {
				this.showModalEditForm = true;
			}
		},
		hideEditForm() {
			this.showModalEditForm = false;
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-statement-group {
	border-color: @border-color-subtle;
	border-width: 1px;
	border-style: solid;
	margin-top: 1em;

	.wikibase-wbui2025-link() {
		.cdx-mixin-link();
	}

	.wikibase-wbui2025-link {
		.wikibase-wbui2025-link();

		&-heavy {
			font-weight: 500;
			font-size: 0.85rem;
		}
	}

	.wikibase-wbui2025-property-name :link {
		.wikibase-wbui2025-link();
	}

	.wikibase-wbui2025-statement-heading {
		display: table;
		background-color: @background-color-neutral;
		width: 100%;

		&-row {
			display: table-row;
		}

		.wikibase-wbui2025-property-name {
			display: table-cell;
			padding: @spacing-35 0 @spacing-35 @spacing-75;
			vertical-align: middle;
		}

		.wikibase-wbui2025-edit-link {
			display: table-cell;
			width: 1px;
			white-space: nowrap;
			padding-right: @spacing-75;
			vertical-align: middle;
			cursor: pointer;

			.wikibase-wbui2025-link-heavy {
				font-size: 1rem;
			}

			.cdx-icon {
				vertical-align: inherit;
			}

			&--deleted-property {
				color: @color-disabled;
				cursor: default;

				.wikibase-wbui2025-icon-edit-small {
					.cdx-mixin-css-icon( @cdx-icon-edit, @color-disabled, @param-size-icon: @size-icon-small );
				}
			}
		}
	}
}

.wikibase-wbui2025-property-name--deleted .wikibase-wbui2025-property-name-link :link,
.wikibase-wbui2025-property-name--deleted .wikibase-wbui2025-property-name-link :visited {
	color: @color-error;
}

.wikibase-wbui2025-icon-edit-small {
	.cdx-mixin-css-icon( @cdx-icon-edit, @param-size-icon: @size-icon-small );
	padding: 3px 0 3px 0;
}
</style>
