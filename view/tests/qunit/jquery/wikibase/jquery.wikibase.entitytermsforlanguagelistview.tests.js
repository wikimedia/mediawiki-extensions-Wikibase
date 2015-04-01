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
var createEntitytermsforlanguagelistview = function( options ) {
	options = $.extend( {
		entityId: 'i am an EntityId',
		entityChangersFactory: {
			getAliasesChanger: function() { return 'i am an AliasesChanger'; },
			getDescriptionsChanger: function() { return 'i am a DescriptionsChanger'; },
			getLabelsChanger: function() { return 'i am a LabelsChanger'; }
		},
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
		]
	}, options || {} );

	return $( '<table/>' )
		.appendTo( 'body' )
		.addClass( 'test_entitytermsforlanguagelistview' )
		.entitytermsforlanguagelistview( options );
};

QUnit.module( 'jquery.wikibase.entitytermsforlanguagelistview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_entitytermsforlanguagelistview' ).each( function() {
			var $entitytermsforlanguagelistview = $( this ),
				entitytermsforlanguagelistview
					= $entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' );

			if( entitytermsforlanguagelistview ) {
				entitytermsforlanguagelistview.destroy();
			}

			$entitytermsforlanguagelistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createEntitytermsforlanguagelistview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $entitytermsforlanguagelistview = createEntitytermsforlanguagelistview(),
		entitytermsforlanguagelistview
			= $entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' );

	assert.ok(
		entitytermsforlanguagelistview !== undefined,
		'Created widget.'
	);

	entitytermsforlanguagelistview.destroy();

	assert.ok(
		$entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'isInitialValue()', function( assert ) {
	var $entitytermsforlanguagelistview = createEntitytermsforlanguagelistview(),
		entitytermsforlanguagelistview
			= $entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' );

	entitytermsforlanguagelistview.startEditing();

	assert.ok(
		entitytermsforlanguagelistview.isInitialValue(),
		'Verified isInitialValue() returning true.'
	);

	var $item = entitytermsforlanguagelistview.$listview.data( 'listview' ).addItem( {
		language: 'fa',
		label: new wb.datamodel.Term( 'fa', 'fa-label' ),
		description: new wb.datamodel.Term( 'fa', 'fa-description' ),
		aliases: new wb.datamodel.MultiTerm( 'fa', [] )
	} );

	assert.ok(
		!entitytermsforlanguagelistview.isInitialValue(),
		'Verified isInitialValue() returning false after changing value.'
	);

	entitytermsforlanguagelistview.$listview.data( 'listview' ).removeItem( $item );

	assert.ok(
		entitytermsforlanguagelistview.isInitialValue(),
		'Verified isInitialValue() returning true after resetting to initial value.'
	);
} );

// TODO: Add test which is kind of pointless without having a method to save a whole fingerprint
// which could be overwritten by the test mechanism. Instead, the "save" functions of labelview,
// descriptionview and aliasesview for each single entitytermsforlanguage would need to be
// overwritten (see entitytermsforlanguage tests).
// QUnit.test( 'startEditing() & stopEditing()', function( assert ) {} );

QUnit.test( 'setError()', function( assert ) {
	var $entitytermsforlanguagelistview = createEntitytermsforlanguagelistview(),
		entitytermsforlanguagelistview
			= $entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' );

	$entitytermsforlanguagelistview
	.on( 'entitytermsforlanguagelistviewtoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered "toggleerror" event.'
		);
	} );

	entitytermsforlanguagelistview.setError();
} );

QUnit.test( 'value()', function( assert ) {
	var $entitytermsforlanguagelistview = createEntitytermsforlanguagelistview(),
		entitytermsforlanguagelistview
			= $entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' );

	// TODO: Enhance test as soon as SiteLinkList is implemented in DataModelJavaScript and used
	// as value object.
	assert.equal(
		entitytermsforlanguagelistview.value().length,
		2,
		'Retrieved value.'
	);

	assert.throws(
		function() {
			entitytermsforlanguagelistview.value( [] );
		},
		'Throwing error when trying to set a new value.'
	);
} );

}( jQuery, wikibase, QUnit ) );
