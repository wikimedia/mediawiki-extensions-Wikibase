/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( QUnit, valueview ) {
	'use strict';

	QUnit.module( 'jquery.valueview.MessageProvider' );

	QUnit.test( 'Basic message management', function( assert ) {
		var messages = {
				'messageProviderTestMessage1': 'message1',
				'messageProviderTestMessage2': 'message2'
			},
			messageProvider = new valueview.MessageProvider( messages );

		assert.equal(
			messageProvider.getMessage( 'messageProviderTestMessage2' ),
			'message2',
			'Fetched message out of mediaWiki context.'
		);

		if( typeof mediaWiki !== 'undefined' && mediaWiki.msg ) {
			messageProvider = new jQuery.valueview.MessageProvider( messages, mediaWiki );

			assert.equal(
				messageProvider.getMessage( 'messageProviderTestMessage2' ),
				'<messageProviderTestMessage2>',
				'Fetched message from mediaWiki context.'
			);
		}

	} );

}( QUnit, jQuery.valueview ) );