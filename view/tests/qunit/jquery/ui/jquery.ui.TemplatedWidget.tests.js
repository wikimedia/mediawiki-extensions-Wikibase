/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( mw, $, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.ui.TemplatedWidget' );

	QUnit.test( 'Create & destroy', function ( assert ) {
		assert.expect( 4 );
		var testSets = [
			[
				'<div><span>$1</span></div>',
				{
					templateParams: [ 'test' ],
					templateShortCuts: {
						$div: 'span'
					}
				}
			]
		];

		/**
		 * @param {Object} templateShortCuts
		 * @param {jQuery} $subject
		 */
		function checkShortCuts( templateShortCuts, $subject ) {
			$.each( templateShortCuts, function ( key, selector ) {
				assert.ok(
					$subject.data( 'TemplatedWidget' )[ key ] instanceof $,
					'Assigned templateShortCut: ' + key + '.'
				);
			} );
		}

		for ( var i = 0; i < testSets.length; i++ ) {
			mw.wbTemplates.store.set( 'templatedWidget-test', testSets[ i ][ 0 ] );

			var $subject = $( '<div/>' );

			$subject.TemplatedWidget( $.extend( {
				template: 'templatedWidget-test'
			}, testSets[ i ][ 1 ] ) );

			assert.ok(
				$subject.data( 'TemplatedWidget' ) instanceof $.ui.TemplatedWidget,
				'Test set #' + i + ': Initialized widget.'
			);

			$subject.removeAttr( 'class' );

			assert.equal(
				$( '<div/>' ).append( $subject ).html(),
				$( '<div/>' ).append(
					mw.wbTemplate( 'templatedWidget-test', testSets[ i ][ 1 ].templateParams )
				).html(),
				'Verified generated HTML.'
			);

			if ( testSets[ i ][ 1 ].templateShortCuts ) {
				checkShortCuts( testSets[ i ][ 1 ].templateShortCuts, $subject );
			}

			$subject.data( 'TemplatedWidget' ).destroy();

			assert.ok(
				$subject.data( 'TemplatedWidget' ) === undefined,
				'Destroyed widget.'
			);
		}

	} );

}( mediaWiki, jQuery, QUnit ) );
