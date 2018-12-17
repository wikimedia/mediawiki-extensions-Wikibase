/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( QUnit, util ) {
	'use strict';

	var messages = {
		messageProviderTestMessage1: 'message1',
		messageProviderTestMessage2: 'message2'
	};

	QUnit.module( 'util.HashMessageProvider' );

	QUnit.test( 'getMessage(): Use default messages', function( assert ) {
		var messageProvider = new util.HashMessageProvider( messages );

		assert.strictEqual(
			messageProvider.getMessage( 'messageProviderTestMessage2' ),
			'message2',
			'Fetched default message.'
		);

		assert.strictEqual(
			messageProvider.getMessage( 'doesNotExist' ),
			null,
			'Returning "null" if message cannot be found.'
		);
	} );

}( QUnit, util ) );
