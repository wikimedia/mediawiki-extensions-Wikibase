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
		}
	},
	methods: {
		showEditForm() {
			this.showModalEditForm = true;
		},
		hideEditForm() {
			this.showModalEditForm = false;
		}
	}
} );
</script>
