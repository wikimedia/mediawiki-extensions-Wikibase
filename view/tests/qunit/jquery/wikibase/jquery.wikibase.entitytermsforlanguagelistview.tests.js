/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

/**
 *  @returns {Fingerprint}
 */
function createFingerprint() {
	return new wb.datamodel.Fingerprint(
		new wb.datamodel.TermMap( {
			de: new wb.datamodel.Term( 'de', 'de-label' ),
			en: new wb.datamodel.Term( 'en', 'en-label' ),
			fa: new wb.datamodel.Term( 'fa', 'fa-label' )
		} ),
		new wb.datamodel.TermMap( {
			de: new wb.datamodel.Term( 'de', 'de-description' ),
			en: new wb.datamodel.Term( 'en', 'en-description' ),
			fa: new wb.datamodel.Term( 'fa', 'fa-description' )
		} ),
		new wb.datamodel.MultiTermMap( {
			de: new wb.datamodel.MultiTerm( 'de', [] ),
			en: new wb.datamodel.MultiTerm( 'en', [] ),
			fa: new wb.datamodel.MultiTerm( 'fa', [] )
		} )
	);
}

/**
 * @param {Object} [options]
 * @return {jQuery}
 */
function createEntitytermsforlanguagelistview( options ) {
	options = $.extend( {
		entityChangersFactory: {
			getAliasesChanger: function() { return 'I am an AliasesChanger'; },
			getDescriptionsChanger: function() { return 'I am a DescriptionsChanger'; },
			getLabelsChanger: function() { return 'I am a LabelsChanger'; }
		},
		value: createFingerprint(),
		userLanguages: [ 'de', 'en' ]
	}, options || {} );

	return $( '<table/>' )
		.appendTo( 'body' )
		.addClass( 'test_entitytermsforlanguagelistview' )
		.entitytermsforlanguagelistview( options );
}

QUnit.module( 'jquery.wikibase.entitytermsforlanguagelistview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_entitytermsforlanguagelistview' ).each( function() {
			var $entitytermsforlanguagelistview = $( this ),
				entitytermsforlanguagelistview
					= $entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' );

			if ( entitytermsforlanguagelistview ) {
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
			= $entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview'),
		listview = entitytermsforlanguagelistview.$listview.data( 'listview' );

	entitytermsforlanguagelistview.startEditing();

	assert.ok(
		entitytermsforlanguagelistview.isInitialValue(),
		'Verified isInitialValue() returning true.'
	);

	var $item = listview.addItem( 'fa' );

	assert.ok(
		entitytermsforlanguagelistview.isInitialValue(),
		'Verified isInitialValue() returning true when adding a language.'
	);

	listview.listItemAdapter().liInstance( $item ).value( {
		language: 'fa',
		label: new wb.datamodel.Term( 'fa', 'changed-term' ),
		description: new wb.datamodel.Term( 'fa', 'changed-description' ),
		aliases: new wb.datamodel.MultiTerm( 'fa', [] )
	} );

	assert.ok(
		!entitytermsforlanguagelistview.isInitialValue(),
		'Verified isInitialValue() returning false after changing the value.'
	);

	listview.removeItem( $item );

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

	assert.ok(
		entitytermsforlanguagelistview.value().equals( createFingerprint() ),
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
