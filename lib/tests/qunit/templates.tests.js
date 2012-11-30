/**
 * QUnit tests for the template engine.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( mw, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'templates', QUnit.newMwEnvironment() );

	QUnit.test( 'mw.template()', function( assert ) {

		assert.equal(
			typeof mw.templates,
			'object',
			'mw.templates is defined.'
		);

		/**
		 * Values to be passed to the template function as parameters.
		 * @type {Array}
		 */
		var params = [
			[ [] ], // values for no-parameter templates
			[ // single-parameter templates
				'param',
				'<div></div>',
				$( '<div/>').append( 'text' ),
				$( '<tr/>' ),
				$( '<td/>' ).append( $( '<span/>' ).text( 'text' ) )
			],
			[ // two-parameter templates
				[ 'param1', 'param2' ],
				[ 'param1', $( '<div/>' ) ],
				[ $( '<div/>' ), $( '<div/>').text( 'param2' ) ]
			]
		];

		/**
		 * Test template definitions and expected results according to the parameters specified
		 * above. Empty string represent combinations that would result in invalid HTML causing an
		 * error to be thrown.
		 * @type {Array}
		 */
		var testsData = [
			{
				'just plain text': [ 'just plain text' ]
			},
			{
				'$1': [
					'param',
					'<div></div>',
					'<div>text</div>',
					'<tr></tr>',
					'<td><span>text</span></td>'
				],
				'text $1 text': [
					'text param text',
					'text <div></div> text',
					'text <div>text</div> text',
					'',
					''
				],
				'<div>$1</div>': [
					'<div>param</div>',
					'<div><div></div></div>',
					'<div><div>text</div></div>',
					'',
					''
				],
				'<div>something</div>$1': [
					'<div>something</div>param',
					'<div>something</div><div></div>',
					'<div>something</div><div>text</div>',
					'',
					''
				],
				'<div><div>$1</div></div>': [
					'<div><div>param</div></div>',
					'<div><div><div></div></div></div>',
					'<div><div><div>text</div></div></div>',
					'',
					''
				],
				'<tr>$1</tr>': [
					'',
					'',
					'',
					'',
					'<tr><td><span>text</span></td></tr>'
				],
				'<table>$1</table>': [
					'',
					'',
					'',
					'<table><tbody><tr></tr></tbody></table>',
					''
				],
				'text<div>$1</div>': [
					'text<div>param</div>',
					'text<div><div></div></div>',
					'text<div><div>text</div></div>',
					'',
					''
				],
				'<div>$1</div>text': [
					'<div>param</div>text',
					'<div><div></div></div>text',
					'<div><div>text</div></div>text',
					'',
					''
				],
				'<div><!-- white space -->	$1<div></div><!-- white space --> \n</div>': [
					'<div><!-- white space -->	param<div></div><!-- white space --> \n</div>',
					'<div><!-- white space -->	<div></div><div></div><!-- white space --> \n</div>',
					'<div><!-- white space -->	<div>text</div><div></div><!-- white space --> \n</div>',
					'',
					''
				],
				'<span><input value="$1" /></span>': [
					'<span><input value="param"></span>',
					'',
					'',
					'',
					''
				]
			},
			{
				'<div>$1</div><div>$2</div>': [ '<div>param1</div><div>param2</div>', '<div>param1</div><div><div></div></div>', '<div><div></div></div><div><div>param2</div></div>' ],
				'$1$2': [ 'param1param2', 'param1<div></div>', '<div></div><div>param2</div>' ]
			}
		];

		/**
		 * Verifies if mw.template() delivers an expected result.
		 *
		 * @param {Array} params
		 * @param {String} template
		 * @param {String} expected
		 */
		var verifyTemplate = function( params, template, expected ) {
			var key = 'test';

			if ( !$.isArray( params ) ) {
				params = [ params ];
			}

			mw.templates.store.set( key, template );

			var paramMessage = '';
			$.each( params, function( i, param ) {
				if ( i > 0 ) {
					paramMessage += ', ';
				}
				if ( typeof param === 'string' ) {
					paramMessage += param;
				} else if ( param instanceof jQuery ) {
					paramMessage += 'jQuery object';
				}
			} );

			if ( expected === '' ) {
				assert.throws(
					function() { $( '<div/>' ).append( mw.template( key, params ) ).html() },
					'Triggered error when trying to create invalid HTML filling single param template "' + template + '" with "' + paramMessage + '"'
				);
			} else {
				assert.equal(
					$( '<div/>' ).append( mw.template( key, params ) ).html(),
					expected,
					'Verified template: "' + template + '" with "' + paramMessage + '"'
				);
			}

		};

		// Loop through testsData and params to run the tests
		$.each( testsData, function( numberOfParams, testData ) {
			$.each( testData, function( template, expectedResults ) {
				$.each( params[ numberOfParams ], function( i, params ) {
					verifyTemplate( params, template, expectedResults[ i ] );
				} );
			} );
		} );

	} );

}( mediaWiki, jQuery, QUnit ) );
