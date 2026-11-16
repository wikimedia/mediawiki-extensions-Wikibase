/**
 * @license GPL-2.0-or-later
 */
( function ( mw ) {
	'use strict';

	const copyright = require( 'wikibase.copyright' );
	const cookieKey = 'wikibase.acknowledgedcopyrightversion';
	const optionsKey = 'wb-acknowledgedcopyrightversion';
	const copyrightVersion = 'wikibase-1';

	QUnit.module( 'wikibase.copyright', QUnit.newMwEnvironment( {
		config: {
			wbCopyright: {
				version: copyrightVersion,
				messageHtml: 'agreement text with <a>link</a>'
			}
		}
	} ) );

	QUnit.test( 'isAccepted is true if the cookie for accepted version matches current', ( assert ) => {
		mw.cookie.set( cookieKey, copyrightVersion );

		assert.true( copyright.isAccepted() );
	} );

	QUnit.test( 'isAccepted is true if the accepted version in user option matches current', ( assert ) => {
		mw.cookie.set( cookieKey, null );
		const optionsStub = sinon.stub( mw.user.options, 'get' );
		optionsStub.withArgs( optionsKey ).returns( copyrightVersion );

		assert.true( copyright.isAccepted() );
		sinon.assert.calledWith( optionsStub, optionsKey );
	} );

	QUnit.test( 'isAccepted is false if neither the cookie nor user option matches current', ( assert ) => {
		mw.cookie.set( cookieKey, 'some-other-version' );
		const optionsStub = sinon.stub( mw.user.options, 'get' );
		optionsStub.withArgs( optionsKey ).returns( 'not-this-either' );

		assert.false( copyright.isAccepted() );
	} );

	QUnit.test( 'persistAcceptance sets the cookie it the user is not named (is temp or anon)', () => {
		const userStub = sinon.stub( mw.user, 'isNamed' );
		userStub.returns( false );
		const cookieStub = sinon.stub( mw.cookie, 'set' );
		const api = {
			saveOption: sinon.spy( ( _value ) => false )
		};
		copyright.persistAcceptance( api )();
		sinon.assert.notCalled( api.saveOption );
		sinon.assert.calledWith( cookieStub, cookieKey, copyrightVersion, { expires: 94608000, path: '/' } );
	} );

	QUnit.test( 'persistAcceptance makes an api call to save the acceptance when the user is named (not anon or temp)', () => {
		const userStub = sinon.stub( mw.user, 'isNamed' );
		userStub.returns( true );
		const done = () => ( { fail: () => false } );
		const api = {
			saveOption: sinon.spy( ( _optKey, _cVersion ) => ( { done } ) )
		};
		copyright.persistAcceptance( api )();
		sinon.assert.calledWith( api.saveOption, optionsKey, copyrightVersion );
	} );

}( mw ) );
