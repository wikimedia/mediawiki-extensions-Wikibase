'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	MixinBuilder = require( '../pagesections/mixinbuilder' ),
	MainStatementSection = require( '../pagesections/main.statement.section' ),
	ComponentInteraction = require( '../pagesections/ComponentInteraction' ),
	PageMixture = MixinBuilder.mix( Page ).with( MainStatementSection, ComponentInteraction );

class ItemPage extends PageMixture {

	open( entityId ) {
		super.openTitle( 'Special:EntityPage/' + entityId );
	}

}

module.exports = new ItemPage();
