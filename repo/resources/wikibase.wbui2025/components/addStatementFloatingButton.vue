<template>
	<wbui2025-add-statement-modal
		v-if="addStatementModalVisible"
		:entity-id="entityId"
		:section-key="sectionKey"
		@hide="modalHidden">
	</wbui2025-add-statement-modal>
	<div
		v-if="visible && expanded"
		class="wikibase-wbui2025-add-statement-float-button-container"
	>
		<cdx-icon
			:icon="cdxIconClose"
			class="wikibase-wbui2025-add-statement-float-button-close-icon"
			@click="expanded = false"
		></cdx-icon>
		<cdx-button
			class="wikibase-wbui2025-add-statement-float-button"
			@click="addStatementModalVisible = true"
		>
			{{ $i18n( 'wikibase-addstatement' ) }}
		</cdx-button>
	</div>
	<div
		v-if="visible && !expanded"
		class="wikibase-wbui2025-add-statement-float-disc"
		@click="expanded = true"
	>
		<cdx-icon :icon="cdxIconAdd"></cdx-icon>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxButton, CdxIcon } = require( '../../../codex.js' );
const { cdxIconAdd, cdxIconClose } = require( '../icons.json' );

const Wbui2025AddStatementModal = require( './addStatementModal.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddStatementFloatingButton',
	components: {
		CdxButton,
		CdxIcon,
		Wbui2025AddStatementModal
	},
	props: {
		entityId: {
			type: String,
			required: true
		}
	},
	data: () => ( {
		expanded: false,
		addStatementModalVisible: false,
		visible: false,
		sectionKey: 'statements',
		scrollListener: null,
		cdxIconAdd,
		cdxIconClose
	} ),
	methods: {
		modalHidden() {
			this.addStatementModalVisible = false;
			this.expanded = false;
		},
		elementOnScreen( element ) {
			const boundingRect = element.getBoundingClientRect();
			return boundingRect.bottom > 0 && boundingRect.top < window.innerHeight;
		},
		scrollPositionUpdated() {
			const addStatementButtons = Array.from( document.getElementsByClassName( 'wikibase-wbui2025-add-statement-button' ) );
			const statementsHeader = document.getElementById( 'claims' );
			const identifiersHeader = document.getElementById( 'identifiers' );
			if ( identifiersHeader && identifiersHeader.getBoundingClientRect().top < window.innerHeight ) {
				this.sectionKey = 'identifiers';
			} else {
				this.sectionKey = 'statements';
			}
			const headerRect = statementsHeader ? statementsHeader.getBoundingClientRect() : null;
			const headerScrolledPast = headerRect ? headerRect.bottom < 0 : true;
			const allScrolledPast = addStatementButtons.length > 0 && addStatementButtons.every( ( el ) => el.getBoundingClientRect().bottom < 0 );
			const anyOnScreen = addStatementButtons.some( ( el ) => this.elementOnScreen( el ) );
			this.visible = headerScrolledPast && !allScrolledPast && !anyOnScreen;
		}
	},
	mounted: function () {
		this.scrollListener = () => {
			this.scrollPositionUpdated();
		};
		window.addEventListener( 'scroll', this.scrollListener );
	},
	beforeUnmount: function () {
		if ( this.scrollListener ) {
			window.removeEventListener( 'scroll', this.scrollListener );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

#wikibase-wbui2025-add-statement-floating-button {
	position: fixed;
	right: @spacing-100;
	bottom: 0;
	display: flex;
	width: 10.5rem;
	height: 9.875rem;
	flex-direction: row;
	justify-content: right;
	align-items: center;
	gap: @spacing-65;
}

.wikibase-wbui2025-add-statement-float-disc {
	position: absolute;
	right: 0;
	display: flex;
	cursor: pointer;
	width: @size-200;
	height: @size-200;
	padding: @spacing-75 @spacing-75;
	justify-content: center;
	align-items: center;
	border-radius: @border-radius-pill;
	border: 2px solid @border-color-progressive--focus;
	background: @background-color-progressive-subtle;
	backdrop-filter: blur(6px);

	.cdx-icon {
		width: 70%;
		height: 70%;
		flex-shrink: 0;
		color: @color-progressive;
	}
}

.wikibase-wbui2025-add-statement-float-button-container {
	.cdx-icon {
		cursor: pointer;
		position: absolute;
		right: 0;
		top: 1.6875rem;
	}

	.wikibase-wbui2025-add-statement-float-button {
		display: flex;
		width: 8.5rem;
		min-width: 2.125rem;
		max-width: @size-2800;
		min-height: 2.125rem;
		max-height: 2.125rem;
		padding: @spacing-30 @spacing-50;
		justify-content: center;
		align-items: center;
		gap: @spacing-25;

		&.cdx-button:enabled {
			border-radius: @border-radius-pill;
			border: 2px solid @border-color-progressive;
			background: @background-color-progressive-subtle;
			color: @color-progressive;
		}
	}
}
</style>
