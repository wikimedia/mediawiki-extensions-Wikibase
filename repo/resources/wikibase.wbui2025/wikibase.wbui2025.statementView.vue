<template>
	<div class="wikibase-wbui2025-statement-group">
		<div v-if="showModalEditForm" class="modal-statement-edit-form-anchor">
			<wbui2025-edit-statement-view
				:property-id="propertyId"
				@hide="hideEditForm"
			></wbui2025-edit-statement-view>
		</div>
		<div class="wikibase-wbui2025-statement-heading">
			<div class="wikibase-wbui2025-statement-heading-row">
				<p>
					<wbui2025-property-name :property-id="propertyId"></wbui2025-property-name>
				</p>
				<div class="wikibase-wbui2025-edit-link" @click="showEditForm">
					<span class="wikibase-wbui2025-icon-edit-small"></span>
					<span class="wikibase-wbui2025-link-heavy">edit</span>
				</div>
			</div>
		</div>
		<wbui2025-statement-detail-view
			v-for="statement in statements"
			:key="statement"
			:statement="statement"
		></wbui2025-statement-detail-view>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Wbui2025PropertyName = require( './wikibase.wbui2025.propertyName.vue' );
const Wbui2025StatementDetailView = require( './wikibase.wbui2025.statementDetailView.vue' );
const Wbui2025EditStatementView = require( './wikibase.wbui2025.editStatement.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025StatementView',
	components: {
		Wbui2025PropertyName,
		Wbui2025StatementDetailView,
		Wbui2025EditStatementView
	},
	props: {
		statements: {
			type: Array,
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
	methods: {
		showEditForm() {
			[ 'body', '.minerva-footer', '.minerva-header' ]
					.map( ( el ) => document.querySelector( el ) )
					.filter( ( el ) => el )
					.forEach( ( el ) => el.classList.add( 'wikibase-wbui2025-modal-open' ) );
			this.showModalEditForm = true;
		},
		hideEditForm() {
			[ 'body', '.minerva-footer', '.minerva-header' ]
					.map( ( el ) => document.querySelector( el ) )
					.filter( ( el ) => el )
					.forEach( ( el ) => el.classList.remove( 'wikibase-wbui2025-modal-open' ) );
			this.showModalEditForm = false;
		}
	}
} );
</script>
