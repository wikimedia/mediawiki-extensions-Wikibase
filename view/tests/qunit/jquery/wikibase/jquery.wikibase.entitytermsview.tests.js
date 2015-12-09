/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @return {jQuery}
 */
var createEntitytermsview = function( options ) {
	options = $.extend( {
		value: [
			{
				language: 'de',
				label: new wb.datamodel.Term( 'de', 'de-label' ),
				description: new wb.datamodel.Term( 'de', 'de-description' ),
				aliases: new wb.datamodel.MultiTerm( 'de', [] )
			}, {
				language: 'en',
				label: new wb.datamodel.Term( 'en', 'en-label' ),
				description: new wb.datamodel.Term( 'en', 'en-description' ),
				aliases: new wb.datamodel.MultiTerm( 'en', [] )
			}
		],
		entityChangersFactory: {
			getAliasesChanger: function() { return 'I am an AliasesChanger'; },
			getDescriptionsChanger: function() { return 'I am a DescriptionsChanger'; },
			getLabelsChanger: function() { return 'I am a LabelsChanger'; }
		}
	}, options || {} );

	return $( '<div/>' )
		.appendTo( 'body' )
		.addClass( 'test_entitytermsview' )
		.entitytermsview( options );
};

QUnit.module( 'jquery.wikibase.entitytermsview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_entitytermsview' ).each( function() {
			var $entitytermsview = $( this ),
				entitytermsview = $entitytermsview.data( 'entitytermsview' );

			if ( entitytermsview ) {
				entitytermsview.destroy();
			}

			$entitytermsview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.expect( 3 );
	assert.throws(
		function() {
			createEntitytermsview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $entitytermsview = createEntitytermsview(),
		entitytermsview = $entitytermsview.data( 'entitytermsview' );

	assert.ok(
		entitytermsview !== undefined,
		'Created widget.'
	);

	entitytermsview.destroy();

	assert.ok(
		$entitytermsview.data( 'entitytermsview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'setError()', function( assert ) {
	assert.expect( 1 );
	var $entitytermsview = createEntitytermsview(),
		entitytermsview = $entitytermsview.data( 'entitytermsview' );

	$entitytermsview
	.on( 'entitytermsviewtoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered "toggleerror" event.'
		);
	} );

	entitytermsview.setError();
} );

QUnit.test( 'value()', function( assert ) {
	assert.expect( 2 );
	var $entitytermsview = createEntitytermsview(),
		entitytermsview = $entitytermsview.data( 'entitytermsview' );

	// TODO: Enhance test as soon as SiteLinkList is implemented in DataModelJavaScript
	assert.equal(
		entitytermsview.value().length,
		2,
		'Retrieved value.'
	);

	assert.throws(
		function() {
			entitytermsview.value( [] );
		},
		'Throwing error when trying to set a new value.'
	);
} );

}( jQuery, wikibase, QUnit ) );
