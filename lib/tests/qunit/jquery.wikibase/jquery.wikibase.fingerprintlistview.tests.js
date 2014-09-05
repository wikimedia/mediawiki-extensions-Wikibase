/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @return {jQuery}
 */
var createFingerprintlistview = function( options ) {
	options = $.extend( {
		entityId: 'i am an entity id',
		api: 'i am an api',
		value: [
			{
				language: 'de',
				label: 'de-label',
				description: 'de-description'
			}, {
				language: 'en',
				label: 'en-label',
				description: 'en-description'
			}
		]
	}, options || {} );

	return $( '<table/>' )
		.appendTo( 'body' )
		.addClass( 'test_fingerprintlistview' )
		.fingerprintlistview( options );
};

QUnit.module( 'jquery.wikibase.fingerprintlistview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_fingerprintlistview' ).each( function() {
			var $fingerprintlistview = $( this ),
				fingerprintlistview = $fingerprintlistview.data( 'fingerprintlistview' );

			if( fingerprintlistview ) {
				fingerprintlistview.destroy();
			}

			$fingerprintlistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createFingerprintlistview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $fingerprintlistview = createFingerprintlistview(),
		fingerprintlistview = $fingerprintlistview.data( 'fingerprintlistview' );

	assert.ok(
		fingerprintlistview !== undefined,
		'Created widget.'
	);

	fingerprintlistview.destroy();

	assert.ok(
		$fingerprintlistview.data( 'fingerprintlistview' ) === undefined,
		'Destroyed widget.'
	);
} );

}( jQuery, QUnit ) );
