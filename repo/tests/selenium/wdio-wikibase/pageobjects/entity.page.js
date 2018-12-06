const Page = require( '../../../../../../../tests/selenium/wdio-mediawiki/Page' );

class EntityPage extends Page {

	open( entityId ) {
		super.openTitle( 'Special:EntityPage/' + entityId );
	}

}

module.exports = new EntityPage();
