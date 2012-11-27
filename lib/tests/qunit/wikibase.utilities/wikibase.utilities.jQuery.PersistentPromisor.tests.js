/**
 * QUnit tests for Wikibase jQuery extension 'PersistentPromisor'
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( $ ) { 'use strict';

	module( 'wikibase.utilities.jQuery.PersistentPromisor', window.QUnit.newWbEnvironment() );

	test( 'Basic jQuery.PersistentPromisor() tests', function() {

		ok(
			$.isFunction( $.PersistentPromisor( function() {} ) ),
			'jQuery.PersistentPromisor() returns a function'
		);
		var deferred; // we want the deferred in this scope so we have full control triggering it for tests

		var ppFunc = $.PersistentPromisor( function( returnPromise ) {
			if( returnPromise === false ) {
				return false; // for test whether this can also handle other return values
			}
			deferred = $.Deferred(); // set fresh, unresolved deferred for outer scope
			var promise = deferred.promise();
			promise.promisor = {
				customStuff: 'foo' // possible to store additional stuff in .promisor ?
			};
			return deferred.promise();
		} );

		// get promise for the first time! Same promise should be returned until we call deferred.resolve()/reject()!
		var ppPromise = ppFunc( 'A' );

		equal(
			ppPromise.promisor.isOngoing,
			false,
			'PersistentPromisor wrapper just called once, not registered as ongoing'
		);

		equal(
			ppPromise.promisor.customStuff,
			'foo',
			'Custom .promisor information not overwritten'
		);

		equal(
			ppFunc( 'B' ).promisor.originalArguments[0], // pp() will increase counter for next test...
			'A',
			'Promise.promisor remembers original arguments'
		);

		equal(
			ppPromise.promisor.isOngoing, // should be increased by last test^^
			1,
			'PersistentPromisor wrapper called a second time, isOngoing increased...'
		);

		equal(
			ppFunc().promisor.isOngoing,
			2,
			'...and increased again after calling function again'
		);

		deferred.resolve(); // resolve the deferred the ppPromise comes from!

		equal(
			ppFunc( false ),
			false,
			'When inner function is returning a non jQuery.Promise value, jQuery.PersistentPromisor will be able to handle it.'
		);

		var newPpPromise = ppFunc(); // call function again and get a NEW promise (because the old one was resolved)!

		equal(
			newPpPromise.promisor.isOngoing,
			false,
			'Promise was finally resolved... calling function again should have returned a new promise where .promisor.isOngoing is set to false again'
		);

		equal(
			ppFunc().promisor.isOngoing,
			1,
			'and to 1 after calling function again'
		);

		equal(
			ppPromise.promisor.isOngoing,
			2,
			'reference to OLD promise still there and not reset or anything'
		);

		deferred.reject(); // reject the deferred the newPpPromise comes from!

		equal(
			ppFunc().promisor.isOngoing,
			false,
			'Rejecting deferred behaves exactly like the resolved deferred, calling the function again will again return a new promise'
		);


	} );

}( jQuery ) );
