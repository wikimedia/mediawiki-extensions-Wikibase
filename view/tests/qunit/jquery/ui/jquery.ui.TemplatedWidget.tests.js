/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	QUnit.module( 'jquery.ui.TemplatedWidget' );

	QUnit.test( 'Create & destroy', function ( assert ) {
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
		 * @param {jQuery} $subj
		 */
		function checkShortCuts( templateShortCuts, $subj ) {
			// eslint-disable-next-line no-jquery/no-each-util
			$.each( templateShortCuts, function ( key, selector ) {
				assert.true(
					$subj.data( 'TemplatedWidget' )[ key ] instanceof $,
					'Assigned templateShortCut: ' + key + '.'
				);
			} );
		}

		for ( var i = 0; i < testSets.length; i++ ) {
			mw.wbTemplates.store.set( 'templatedWidget-test', testSets[ i ][ 0 ] );

			var $subject = $( '<div>' );

			$subject.TemplatedWidget( $.extend( {
				template: 'templatedWidget-test'
			}, testSets[ i ][ 1 ] ) );

			assert.true(
				$subject.data( 'TemplatedWidget' ) instanceof $.ui.TemplatedWidget,
				'Test set #' + i + ': Initialized widget.'
			);

			$subject.removeAttr( 'class' );

			assert.strictEqual(
				$( '<div>' ).append( $subject ).html(),
				$( '<div>' ).append(
					mw.wbTemplate( 'templatedWidget-test', testSets[ i ][ 1 ].templateParams )
				).html(),
				'Verified generated HTML.'
			);

			if ( testSets[ i ][ 1 ].templateShortCuts ) {
				checkShortCuts( testSets[ i ][ 1 ].templateShortCuts, $subject );
			}

			$subject.data( 'TemplatedWidget' ).destroy();

			assert.strictEqual(
				$subject.data( 'TemplatedWidget' ),
				undefined,
				'Destroyed widget.'
			);
		}

	} );

}() );
