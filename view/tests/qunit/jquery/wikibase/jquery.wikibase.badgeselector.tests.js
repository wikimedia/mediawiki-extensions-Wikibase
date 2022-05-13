/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'jquery.wikibase.badgeselector', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_badgeselector' ).each( function () {
				var $node = $( this ),
					badgeselector = $node.data( 'badgeselector' );
				if ( badgeselector ) {
					badgeselector.destroy();
				}
				$node.remove();
			} );
		}
	} ) );

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createBadgeselector( options ) {
		options = $.extend( {
			badges: {
				Q1: 'additionalCssClass-1',
				Q2: 'additionalCssClass-21 additionalCssClass22',
				Q3: 'additionalCssClass-3'
			},
			entityIdPlainFormatter: function ( entityId ) {
				return $.Deferred().resolve( entityId ).promise();
			},
			languageCode: 'en'
		}, options || {} );

		var $badgeselector = $( '<span>' )
			.addClass( 'test_badgeselector' )
			.appendTo( document.body )
			.badgeselector( options );

		return $badgeselector;
	}

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $badgeselector = createBadgeselector(),
			badgeselector = $badgeselector.data( 'badgeselector' );

		assert.notStrictEqual(
			badgeselector,
			undefined,
			'Instantiated widget.'
		);

		badgeselector.destroy();

		assert.strictEqual(
			$badgeselector.data( 'badgeselector' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'startEditing() & stopEditing()', function ( assert ) {
		var $badgeselector = createBadgeselector(),
			badgeselector = $badgeselector.data( 'badgeselector' );

		$badgeselector
		.on( 'badgeselectorafterstartediting', function ( event ) {
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'badgeselectorafterstopediting', function ( event, dropValue ) {
			assert.true(
				true,
				'Stopped edit mode.'
			);
		} );

		badgeselector.startEditing();
		badgeselector.startEditing(); // should not trigger event
		badgeselector.stopEditing();
		badgeselector.stopEditing(); // should not trigger event
	} );

	QUnit.test( 'value()', function ( assert ) {
		var $badgeselector = createBadgeselector(),
			badgeselector = $badgeselector.data( 'badgeselector' );

		assert.deepEqual(
			badgeselector.value(),
			[],
			'Returning empty value in non-edit mode.'
		);

		badgeselector.startEditing();

		assert.deepEqual(
			badgeselector.value(),
			[],
			'Returning empty value in edit mode regardless of placeholder badge.'
		);
	} );

	QUnit.test( 'startEditing and stopEditing add and remove an empty badge', function ( assert ) {
		var $badgeselector = createBadgeselector(),
			badgeselector = $badgeselector.data( 'badgeselector' );

		badgeselector.startEditing();

		assert.strictEqual( $badgeselector.find( '[data-wb-badge=""]' ).length, 1 );

		badgeselector.stopEditing( true );

		assert.strictEqual( $badgeselector.find( '[data-wb-badge=""]' ).length, 0 );

		badgeselector.startEditing();

		assert.strictEqual( $badgeselector.find( '[data-wb-badge=""]' ).length, 1 );

		badgeselector.stopEditing();

		assert.strictEqual( $badgeselector.find( '[data-wb-badge=""]' ).length, 0 );

	} );

}( wikibase ) );
