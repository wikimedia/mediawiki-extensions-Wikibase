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
					class="wikibase-wbui2025-link wikibase-wbui2025-edit-link"
					:class="{ 'wikibase-wbui2025-edit-link-unsupported': isUnsupportedDataType, 'is-red-link': isUnsupportedDataType }"
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
const Wbui2025PropertyName = require( './wikibase.wbui2025.propertyName.vue' );
const Wbui2025StatementView = require( './wikibase.wbui2025.statementView.vue' );
const Wbui2025EditStatementGroup = require( './wikibase.wbui2025.editStatementGroup.vue' );
const supportedDatatypes = require( './supportedDatatypes.json' );
const { getStatementsForProperty } = require( './store/savedStatementsStore.js' );

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
			return getStatementsForProperty( this.propertyId );
		},
		isUnsupportedDataType() {
			if ( !this.statements || this.statements.length === 0 ) {
				return false;
			}
			const datatype = this.statements[ 0 ].mainsnak.datatype;
			return !supportedDatatypes.includes( datatype );
		}
	},
	methods: {
		showEditForm() {
			if ( this.isUnsupportedDataType ) {
				return;
			}
			this.showModalEditForm = true;
		},
		hideEditForm() {
			this.showModalEditForm = false;
		}
	}
} );
</script>
