/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( QUnit, util ) {
	'use strict';

	QUnit.module( 'util.MessageProvider' );

	QUnit.test( 'Basic message management', function( assert ) {
		var messages = {
				'messageProviderTestMessage1': 'message1',
				'messageProviderTestMessage2': 'message2'
			},
			messageProvider = new util.MessageProvider( messages );

		assert.equal(
			messageProvider.getMessage( 'messageProviderTestMessage2' ),
			'message2',
			'Fetched default message.'
		);

		if( mediaWiki !== undefined && mediaWiki.msg ) {
			messageProvider = new util.MessageProvider( messages, mediaWiki.msg );

			assert.equal(
				messageProvider.getMessage( 'messageProviderTestMessage2' ),
				'<messageProviderTestMessage2>',
				'Fetched message from mediaWiki context.'
			);
		}

	} );

}( QUnit, util ) );
