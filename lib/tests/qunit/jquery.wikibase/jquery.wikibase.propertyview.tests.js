/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createPropertyview = function( options, $node ) {
	options = $.extend( {
		entityStore: new wb.store.EntityStore(),
		entityChangersFactory: {
			getAliasesChanger: function() { return 'I am an AliasesChanger'; },
			getDescriptionsChanger: function() { return 'I am a DescriptionsChanger'; },
			getLabelsChanger: function() { return 'I am a LabelsChanger'; }
		},
		api: 'I am an Api',
		valueViewBuilder: 'I am a valueview builder',
		dataTypeStore: 'I am a DataTypeStore',
		value: new wb.datamodel.Property( 'P1', 'someDataType' ),
		languages: 'en'
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $propertyview = $node
		.addClass( 'test_propertyview' )
		.propertyview( options );

	$propertyview.data( 'propertyview' )._save = function() {
		return $.Deferred().resolve( {
			entity: {
				lastrevid: 'i am a revision id'
			}
		} ).promise();
	};

	return $propertyview;
};

QUnit.module( 'jquery.wikibase.propertyview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_propertyview' ).each( function() {
			var $propertyview = $( this ),
				propertyview = $propertyview.data( 'propertyview' );

			if( propertyview ) {
				propertyview.destroy();
			}

			$propertyview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createPropertyview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	assert.throws(
		function() {
			createPropertyview( { languages: null } );
		},
		'Throwing error when trying to initialize widget without a language.'
	);

	var $propertyview = createPropertyview(),
		propertyview = $propertyview.data( 'propertyview' );

	assert.ok(
		propertyview instanceof $.wikibase.propertyview,
		'Created widget.'
	);

	propertyview.destroy();

	assert.ok(
		$propertyview.data( 'propertyview' ) === undefined,
		'Destroyed widget.'
	);

	$propertyview = createPropertyview( { languages: ['ku'] } );
	propertyview = $propertyview.data( 'propertyview' );

	assert.ok(
		propertyview instanceof $.wikibase.propertyview,
		'Created widget with a language.'
	);
} );

}( jQuery, wikibase, QUnit ) );
