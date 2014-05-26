/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( QUnit, util, sinon ) {
	'use strict';

	var messages = {
			'messageProviderTestMessage1': 'message1',
			'messageProviderTestMessage2': 'message2'
		},
		mockMessageRegistry = {
			'messageProviderTestMessage1': 'messageStore1',
			'messageStoreTestMessage2': 'messageStore2'
		},
		mockMessageGetter = function( key ) {
			return mockMessageRegistry[key];
		};

	QUnit.module( 'util.MessageProvider' );

	QUnit.test( 'getMessage(): Use default messages', function( assert ) {
		var messageProvider = new util.MessageProvider( {
				defaultMessages: messages
			} );

		assert.equal(
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

	QUnit.test( 'getMessage(): Use message getter', function( assert ) {
		var messageProvider = new util.MessageProvider( {
				messageGetter: mockMessageGetter
			} );

		assert.equal(
			messageProvider.getMessage( 'messageProviderTestMessage1' ),
			'messageStore1',
			'Fetched message via message getter.'
		);

		assert.strictEqual(
			messageProvider.getMessage( 'doesNotExist' ),
			null,
			'Returning "null" if message cannot be found.'
		);

		messageProvider = new util.MessageProvider( {
			defaultMessages: messages,
			messageGetter: mockMessageGetter
		} );

		assert.equal(
			messageProvider.getMessage( 'messageProviderTestMessage1' ),
			'messageStore1',
			'Fetched message via message getter while having a default message with the same key.'
		);

		assert.equal(
			messageProvider.getMessage( 'messageProviderTestMessage2' ),
			'message2',
			'Returning default message if message cannot be retrieved via message getter.'
		);
	} );

	QUnit.test( 'setDefaultMessages()', function( assert ) {
		var messageProvider = new util.MessageProvider();

		assert.strictEqual(
			messageProvider.getMessage( 'messageProviderTestMessage1' ),
			null,
			'Instantiated a message provider without default messages.'
		);

		messageProvider.setDefaultMessages( messages );

		assert.equal(
			messageProvider.getMessage( 'messageProviderTestMessage1' ),
			'message1',
			'Returning default message after setting default messages.'
		);
	} );

	QUnit.test( 'correctly passes additional params', function( assert ) {
		var messageGetter = sinon.spy();
		var messageProvider = new util.MessageProvider( {
			messageGetter: messageGetter
		} );

		messageProvider.getMessage( 'id', [ 'param one' ] );
		messageProvider.getMessage( 'id', [ 'param one', 'param two' ] );

		sinon.assert.calledTwice( messageGetter );
		assert.ok( messageGetter.firstCall.calledWithExactly( 'id', 'param one' ) );
		assert.ok( messageGetter.secondCall.calledWithExactly( 'id', 'param one', 'param two' ) );
	} );

}( QUnit, util, sinon ) );
