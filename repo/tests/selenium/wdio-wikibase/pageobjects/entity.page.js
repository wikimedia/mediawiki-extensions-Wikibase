const Page = require( 'wdio-mediawiki/Page' );

class EntityPage extends Page {

	open( entityId ) {
		super.openTitle( 'Special:EntityPage/' + entityId );

		browser.execute( () => {
			mw.cookie.set( 'wikibase-no-anonymouseditwarning', 'true' );
		} );
	}
}

module.exports = new EntityPage();
