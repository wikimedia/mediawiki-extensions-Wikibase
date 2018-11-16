'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	MixinBuilder = require( '../pagesections/mixinbuilder' ),
	MainStatementSection = require( '../pagesections/main.statement.section' ),
	ComponentInteraction = require( '../pagesections/ComponentInteraction' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

class ItemPage extends MixinBuilder.mix( Page ).with( MainStatementSection, ComponentInteraction ) {

	open( entityId ) {
		super.openTitle( 'Special:EntityPage/' + entityId );
	}

	protectPage( entityId )	{
		LoginPage.loginAdmin();
		this.open( entityId );
		browser.waitForVisible( '#p-cactions' );
		$( '#p-cactions' ).click();
		browser.waitForVisible( '#ca-protect' );
		$( '#ca-protect' ).click();
		browser.waitForVisible( '#mwProtect-level-edit' );
		$( '#mwProtect-level-edit' ).$( '[value="sysop"]' ).click();
		$( '#mw-Protect-submit' ).click();
		browser.waitForVisible( '#pt-logout' );
		$( '#pt-logout a' ).click();
		this.open( entityId );
	}

}

module.exports = new ItemPage();
