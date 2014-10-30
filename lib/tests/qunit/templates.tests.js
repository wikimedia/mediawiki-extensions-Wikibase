/**
 * QUnit tests for the template engine.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( mw, $, QUnit ) {
	'use strict';

	QUnit.module( 'templates', QUnit.newMwEnvironment() );

	QUnit.test( 'mw.wbTemplate()', function( assert ) {

		assert.equal(
			typeof mw.wbTemplates,
			'object',
			'mw.wbTemplates is defined.'
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
				$( '<td/>' ).append( $( '<span/>' ).text( 'text' ) ),
				'text with&nbsp;spaces'
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
		 * Expected result may be an array. Since IE's HTML parsing does not strip invalid HTML
		 * structure in multiple cases, it will not throw errors treating the HTML structure as
		 * valid. While the first array value represents the normally expected result, the second
		 * value represents the result expected for IE.
		 * @type {Array}
		 */
		var testsData = [
			{
				'just plain text': [ 'just plain text' ]
			},
			{
				'$1': [
					'param',
					'&lt;div&gt;&lt;/div&gt;',
					'<div>text</div>',
					'<tr></tr>',
					'<td><span>text</span></td>',
					'text with&amp;nbsp;spaces' // actually returns &nbsp; entity but test performs double escaping
				],
				'text $1 text': [
					'text param text',
					'text &lt;div&gt;&lt;/div&gt; text',
					'text <div>text</div> text',
					['', 'text <tr></tr> text'],
					['', 'text <td><span>text</span></td> text'],
					'text text with&amp;nbsp;spaces text'
				],
				'<div>$1</div>': [
					'<div>param</div>',
					'<div>&lt;div&gt;&lt;/div&gt;</div>',
					'<div><div>text</div></div>',
					'',
					'',
					'<div>text with&amp;nbsp;spaces</div>'
				],
				'<div>something</div>$1': [
					'<div>something</div>param',
					'<div>something</div>&lt;div&gt;&lt;/div&gt;',
					'<div>something</div><div>text</div>',
					'',
					'',
					'<div>something</div>text with&amp;nbsp;spaces'
				],
				'<div><div>$1</div></div>': [
					'<div><div>param</div></div>',
					'<div><div>&lt;div&gt;&lt;/div&gt;</div></div>',
					'<div><div><div>text</div></div></div>',
					'',
					'',
					'<div><div>text with&amp;nbsp;spaces</div></div>'
				],
				'<tr>$1</tr>': [
					['', '<tr>param</tr>'],
					['', '<tr><div></div></tr>'],
					['', '<tr><div>text</div></tr>'],
					'',
					'<tr><td><span>text</span></td></tr>',
					['', '<tr>text with&amp;nbsp;spaces</tr>']
				],
				'<table>$1</table>': [
					['', '<table>param</table>'],
					['', '<table><div></div></table>'],
					['', '<table><div>text</div></table>'],
					'<table><tbody><tr></tr></tbody></table>',
					'',
					['', '<table>text with&amp;nbsp;spaces</table>']
				],
				'text<div>$1</div>': [
					'text<div>param</div>',
					'text<div>&lt;div&gt;&lt;/div&gt;</div>',
					'text<div><div>text</div></div>',
					'',
					'',
					'text<div>text with&amp;nbsp;spaces</div>'
				],
				'<div>$1</div>text': [
					'<div>param</div>text',
					'<div>&lt;div&gt;&lt;/div&gt;</div>text',
					'<div><div>text</div></div>text',
					'',
					'',
					'<div>text with&amp;nbsp;spaces</div>text'
				],
				'<div><!-- white space -->	$1<div></div><!-- white space --> \n</div>': [
					'<div><!-- white space -->	param<div></div><!-- white space --> \n</div>',
					'<div><!-- white space -->	&lt;div&gt;&lt;/div&gt;<div></div><!-- white space --> \n</div>',
					'<div><!-- white space -->	<div>text</div><div></div><!-- white space --> \n</div>',
					'',
					'',
					'<div><!-- white space -->	text with&amp;nbsp;spaces<div></div><!-- white space --> \n</div>'
				],
				'<span><input value="$1" /></span>': [
					'<span><input value="param"></span>',
					'<span><input value="&lt;div&gt;&lt;/div&gt;"></span>',
					'',
					'',
					'',
					'<span><input value="text with&amp;nbsp;spaces"></span>'
				]
			},
			{
				'<div>$1</div><div>$2</div>': [ '<div>param1</div><div>param2</div>', '<div>param1</div><div><div></div></div>', '<div><div></div></div><div><div>param2</div></div>' ],
				'$1$2': [ 'param1param2', 'param1<div></div>', '<div></div><div>param2</div>' ]
			}
		];

		/**
		 * Verifies if mw.wbTemplate() delivers an expected result.
		 *
		 * @param {Array} params
		 * @param {String} template
		 * @param {String|Array} expected
		 */
		var verifyTemplate = function( params, template, expected ) {
			var key = 'test';

			/**
			 * Helper function to replace all < and > characters.
			 * Firefox re-converts &lt; and &gt; being applied to $.html() in certain strings.
			 * Example: While other browsers return <input value="&lt;div&gt;&lt;/div&gt;"> when
			 * feeding with the string "<div></div>", Firefox returns <input value="<div></div>">
			 * which seems to be intended. However, for the need of cross-browser comparison we just
			 * convert each < and > character.
			 *
			 * @param {String} string
			 * @return {String}
			 */
			function replaceChevrons( string ) {
				return string.replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
			}

			if ( !$.isArray( params ) ) {
				params = [ params ];
			}

			mw.wbTemplates.store.set( key, template );

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

			if ( $.isArray( expected ) ) {
				expected = ( $.client.profile().name === 'msie' ) ? expected[1] : expected[0];
			}

			if ( expected === '' ) {
				assert.throws(
					function() { $( '<div/>' ).append( mw.wbTemplate( key, params ) ).html(); },
					'Triggered error when trying to create invalid HTML filling single param template "' + template + '" with "' + paramMessage + '"'
				);
			} else {
				assert.equal(
					replaceChevrons( $( '<div/>' ).append( mw.wbTemplate( key, params ) ).html() ),
					replaceChevrons( expected ),
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
