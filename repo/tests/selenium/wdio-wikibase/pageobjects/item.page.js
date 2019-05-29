'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	MixinBuilder = require( '../pagesections/mixinbuilder' ),
	MainStatementSection = require( '../pagesections/main.statement.section' ),
	ComponentInteraction = require( '../pagesections/ComponentInteraction' ),
	PageMixture = MixinBuilder.mix( Page ).with( MainStatementSection, ComponentInteraction );

class ItemPage extends PageMixture {

	static get ITEM_WIDGET_SELECTORES() {
		return {
			ADD_STATEMENT: 'div.wikibase-addtoolbar > .wikibase-toolbar-button-add > a',
			SAVE_BUTTON: '.wikibase-toolbar-button-save'
		};
	}

	open( entityId ) {
		super.openTitle( 'Special:EntityPage/' + entityId );
	}

	get addStatementLink() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.ADD_STATEMENT );
	}

	isSaveButtonEnabled() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.SAVE_BUTTON ).getAttribute( 'aria-disabled' ) === 'false';
	}
}

module.exports = new ItemPage();
