<template>
	<wikibase-wbui2025-modal-overlay
		modal-class="wikibase-wbui2025-add-value-modal"
		:header="$i18n( 'wikibase-addvalue' )"
		@hide="$emit( 'cancel' )"
	>
		<template #content>
			<wikibase-wbui2025-edit-statement
				:statement-id="statementId"
				hide-remove-button
				focus-on-mount
			></wikibase-wbui2025-edit-statement>
		</template>

		<template #footer>
			<div class="wikibase-wbui2025-edit-statement-footer">
				<div class="wikibase-wbui2025-edit-form-actions">
					<cdx-button weight="quiet" @click="$emit( 'cancel' )">
						<cdx-icon :icon="cdxIconClose"></cdx-icon>
						{{ $i18n( 'wikibase-cancel' ) }}
					</cdx-button>

					<cdx-button
						action="progressive"
						weight="primary"
						:disabled="!canSubmit"
						@click="emitAdd"
					>
						<cdx-icon :icon="cdxIconCheck"></cdx-icon>
						{{ $i18n( 'wikibase-add' ) }}
					</cdx-button>
				</div>
			</div>
		</template>
	</wikibase-wbui2025-modal-overlay>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { mapState } = require( 'pinia' );
const {
	CdxButton,
	CdxIcon
} = require( '../../../codex.js' );
const {
	cdxIconClose,
	cdxIconCheck
} = require( '../icons.json' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const WikibaseWbui2025EditStatement = require( './editStatement.vue' );
const WikibaseWbui2025ModalOverlay = require( './modalOverlay.vue' );

module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddStatementValue',
	components: {
		CdxButton,
		CdxIcon,
		WikibaseWbui2025EditStatement,
		WikibaseWbui2025ModalOverlay
	},
	props: {
		statementId: {
			type: String,
			required: true
		}
	},
	emits: [ 'add', 'cancel' ],
	data: () => ( {
		cdxIconClose,
		cdxIconCheck
	} ),
	computed: Object.assign(
		mapState(
			wbui2025.store.useEditStatementsStore,
			{
				fullyParsed: 'isFullyParsed',
				hasChanges: 'hasChanges'
			}
		),
		{
			canSubmit() {
				return this.fullyParsed && this.hasChanges === true;
			}
		}
	),
	methods: {
		emitAdd() {
			this.$emit( 'add', this.statementId );
		}
	}
} );
</script>

<style>
.wikibase-wbui2025-edit-statement-footer {
	display: flex;
	justify-content: center;
	padding: 1rem;
	box-shadow: 0 -2px 12px rgba(0,0,0,0.1);
}
.wikibase-wbui2025-edit-form-actions {
	display: flex;
	gap: 1rem;
	align-items: center;
}
</style>
