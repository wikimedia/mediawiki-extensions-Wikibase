'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	MixinBuilder = require( '../pagesections/mixinbuilder' ),
	MainStatementSection = require( '../pagesections/main.statement.section' ),
	ComponentInteraction = require( '../pagesections/ComponentInteraction' ),
	PageMixture = MixinBuilder.mix( Page ).with( MainStatementSection, ComponentInteraction );

class ItemPage extends PageMixture {

	static get ITEM_WIDGET_SELECTORES() {
		return {
			MAIN_STATEMENTS: 'div.wikibase-entityview-main > .wikibase-statementgrouplistview',
			ADD_STATEMENT: 'div.wikibase-addtoolbar > .wikibase-toolbar-button-add > a',
			SAVE: '.wikibase-toolbar-button-save',
			EDIT: ',wikibase-toolbar-button-edit',
			PROPERTY_INPUT: '.ui-entityselector-input',
			VALUE_INPUT: '.valueview-input',
			QUALIFIERS: '.wikibase-statementview-qualifiers .listview-item',
			REFERENCES: '.wikibase-statementview-references',
			NTH_ELEMENT: '.wikibase-listview > .listview-item'
		};
	}

	open( entityId ) {
		super.openTitle( 'Special:EntityPage/' + entityId );
	}

	get saveButtonEnabled() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.SAVE ).getAttribute( 'aria-disabled' ) === 'false';
	}
}

module.exports = new ItemPage();
