/**
 * QUnit tests for the template engine.
 *
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */

( function () {
	'use strict';

	QUnit.module( 'wikibase.templates', QUnit.newMwEnvironment() );

	QUnit.test( 'mw.wbTemplate()', function ( assert ) {
		assert.strictEqual(
			typeof mw.wbTemplates,
			'object',
			'mw.wbTemplates is defined.'
		);

		/**
		 * Values to be passed to the template function as parameters.
		 *
		 * @type {Array}
		 */
		var baseParams = [
			[ [] ], // values for no-parameter templates
			[ // single-parameter templates
				'param',
				'<div></div>',
				$( '<div>' ).append( 'text' ),
				$( '<tr>' ),
				$( '<td>' ).append( $( '<span>' ).text( 'text' ) ),
				'text with&nbsp;spaces'
			],
			[ // two-parameter templates
				[ 'param1', 'param2' ],
				[ 'param1', $( '<div>' ) ],
				[ $( '<div>' ), $( '<div>' ).text( 'param2' ) ]
			]
		];

		/**
		 * Test template definitions and expected results according to the parameters specified
		 * above. Empty string represent combinations that would result in invalid HTML causing an
		 * error to be thrown.
		 *
		 * @type {Array}
		 */
		var testsData = [
			{
				'just plain text': [ 'just plain text' ]
			},
			{
				$1: [
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
					'',
					'',
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
					'',
					'',
					'',
					'',
					'<tr><td><span>text</span></td></tr>',
					''
				],
				'<table>$1</table>': [
					'',
					'',
					'',
					'<table><tbody><tr></tr></tbody></table>',
					'',
					''
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
				/* eslint-disable no-tabs */
				'<div><!-- white space -->	$1<div></div><!-- white space --> \n</div>': [
					'<div><!-- white space -->	param<div></div><!-- white space --> \n</div>',
					'<div><!-- white space -->	&lt;div&gt;&lt;/div&gt;<div></div><!-- white space --> \n</div>',
					'<div><!-- white space -->	<div>text</div><div></div><!-- white space --> \n</div>',
					'',
					'',
					'<div><!-- white space -->	text with&amp;nbsp;spaces<div></div><!-- white space --> \n</div>'
				],
				/* eslint-enable */
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
				$1$2: [ 'param1param2', 'param1<div></div>', '<div></div><div>param2</div>' ]
			}
		];

		/**
		 * Verifies if mw.wbTemplate() delivers an expected result.
		 *
		 * @param {Array} params
		 * @param {string} template
		 * @param {string} expected
		 */
		var verifyTemplate = function ( params, template, expected ) {
			var key = 'test';

			/**
			 * Helper function to replace all < and > characters.
			 * Firefox re-converts &lt; and &gt; being applied to $.html() in certain strings.
			 * Example: While other browsers return <input value="&lt;div&gt;&lt;/div&gt;"> when
			 * feeding with the string "<div></div>", Firefox returns <input value="<div></div>">
			 * which seems to be intended. However, for the need of cross-browser comparison we just
			 * convert each < and > character.
			 *
			 * @param {string} string
			 * @return {string}
			 */
			function replaceChevrons( string ) {
				return string.replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
			}

			if ( !Array.isArray( params ) ) {
				params = [ params ];
			}

			mw.wbTemplates.store.set( key, template );

			var paramMessage = '';
			params.forEach( function ( param, i ) {
				if ( i > 0 ) {
					paramMessage += ', ';
				}
				if ( typeof param === 'string' ) {
					paramMessage += param;
				} else if ( param instanceof $ ) {
					paramMessage += 'jQuery object';
				}
			} );

			if ( expected === '' ) {
				assert.throws(
					function () { $( '<div>' ).append( mw.wbTemplate( key, params ) ).html(); },
					'Triggered error when trying to create invalid HTML filling single param template "' + template + '" with "' + paramMessage + '"'
				);
			} else {
				assert.strictEqual(
					replaceChevrons( $( '<div>' ).append( mw.wbTemplate( key, params ) ).html() ),
					replaceChevrons( expected ),
					'Verified template: "' + template + '" with "' + paramMessage + '"'
				);
			}

		};

		// Loop through testsData and params to run the tests
		testsData.forEach( function ( testData, numberOfParams ) {
			// eslint-disable-next-line no-jquery/no-each-util
			$.each( testData, function ( template, expectedResults ) {
				baseParams[ numberOfParams ].forEach( function ( params, i ) {
					verifyTemplate( params, template, expectedResults[ i ] );
				} );
			} );
		} );

	} );

	var templateName = 'wikibase-some-test-template';

	QUnit.test( '$element.applyTemplate() adds classes from the template', function ( assert ) {
		mw.wbTemplates.store.set( templateName, '<div class="class-from-template"></div>' );

		var $div = $( '<div>' ).addClass( 'my-class' );
		$div.applyTemplate( templateName );

		assert.assertTrue( $div.hasClass( 'my-class' ) );
		assert.assertTrue( $div.hasClass( 'class-from-template' ) );
	} );

	QUnit.test(
		'$element.applyTemplate() copies all attributes from the template',
		function ( assert ) {
			mw.wbTemplates.store.set( templateName, '<div attr1="val1" attr2="val2"></div>' );

			var $div = $( '<div>' );
			$div.applyTemplate( templateName );

			assert.strictEqual( $div.attr( 'attr1' ), 'val1' );
			assert.strictEqual( $div.attr( 'attr2' ), 'val2' );
		}
	);

	QUnit.test(
		'$element.applyTemplate() replaces contents from the template',
		function ( assert ) {
			mw.wbTemplates.store.set( templateName, '<div>template contents</div>' );

			var $div = $( '<div>' ).text( 'some contents' );
			$div.applyTemplate( templateName );

			assert.strictEqual( $div.html(), 'template contents' );
		}
	);

}() );
