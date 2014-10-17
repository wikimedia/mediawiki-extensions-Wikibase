/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */

( function( $, wb, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createEntityview = function( options, $node ) {
	options = $.extend( {
		entityStore: 'i am an entity store',
		api: 'i am an api',
		valueViewBuilder: 'i am a valueview builder',
		value: new wb.datamodel.Item( 'Q1' ) // FIXME: value is optional according to doc
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $entityview = $node
		.addClass( 'test_entityview' )
		.entityview( options );

	$entityview.data( 'entityview' )._save = function() {
		return $.Deferred().resolve( {
			entity: {
				lastrevid: 'i am a revision id'
			}
		} ).promise();
	};

	return $entityview;
};

QUnit.module( 'jquery.wikibase.entityview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_entityview' ).each( function() {
			var $entityview = $( this ),
				entityview = $entityview.data( 'entityview' );

			if( entityview ) {
				entityview.destroy();
			}

			$entityview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createEntityview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $entityview = createEntityview(),
		entityview = $entityview.data( 'entityview' );

	assert.ok(
		entityview !== 'undefined',
		'Created widget.'
	);

	entityview.destroy();

	assert.ok(
		$entityview.data( 'entityview' ) === undefined,
		'Destroyed widget.'
	);

	$entityview = createEntityview( { languages: [ 'ku' ] } );
	entityview = $entityview.data( 'entityview' );

	assert.ok(
		entityview !== 'undefined',
		'Created widget with a language.'
	);
} );

}( jQuery, wikibase, QUnit ) );
