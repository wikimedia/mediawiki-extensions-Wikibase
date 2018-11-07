const assert = require( 'assert' ),
	  Page = require( 'wdio-mediawiki/Page' );

class EntityPage extends Page {

	open( entityId ) {
		super.openTitle( 'Special:EntityPage/' + entityId );
	}

}

module.exports = new EntityPage();
