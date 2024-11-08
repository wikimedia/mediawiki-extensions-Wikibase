/**
 * @license GPL-2.0-or-later
 * @author Arthur Taylor
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.TempUserWatcher', QUnit.newMwEnvironment() );

	var TempUserWatcher = wb.entityChangers.TempUserWatcher;

	QUnit.test( 'is a function', ( assert ) => {
		assert.strictEqual(
			typeof TempUserWatcher,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', ( assert ) => {
		assert.true( new TempUserWatcher() instanceof TempUserWatcher );
	} );

	QUnit.test( 'sets redirect Url if present', ( assert ) => {
		const target = 'https://wiki.example/';
		const tempUserWatcher = new TempUserWatcher();
		tempUserWatcher.processApiResult( {
			tempusercreated: true,
			tempuserredirect: target
		} );
		assert.strictEqual( target, tempUserWatcher.getRedirectUrl(), 'it should set the URL' );
	} );

}( wikibase ) );
