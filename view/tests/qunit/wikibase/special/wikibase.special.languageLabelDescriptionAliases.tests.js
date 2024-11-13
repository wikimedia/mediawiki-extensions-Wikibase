/**
 * @license GPL-2.0-or-later
 * @author Arthur Taylor
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.special.languageLabelDescriptionAliases' );

	var nextId = 1;

	var createInputWidget = function ( name, value ) {
		if ( !value ) {
			value = '';
		}
		var fixture = '\
            <div id=\'ooui-php-' + nextId++ + '\' class=\'mw-htmlform-field-HTMLTrimmedTextField oo-ui-fieldLayout\' \
                 data-ooui=\'{"_":"mw.htmlform.FieldLayout","fieldWidget":{"tag":"wb-newentity-' + name +
                 '"},"label":{"html":"' + name +
                 ':"},"classes":["mw-htmlform-field-HTMLTrimmedTextField"]}\'> \
              <div class=\'oo-ui-fieldLayout-body\'> \
                <div class=\'oo-ui-fieldLayout-field\'> \
                  <div id=\'wb-newentity-' + name + '\' class=\'oo-ui-inputWidget oo-ui-textInputWidget\' \
                    data-ooui=\'{"_":"OO.ui.TextInputWidget","placeholder":"enter a ' + name + '","name":"' +
                    name + '","inputId":"ooui-php-' + nextId + '"}\'> \
                    <input type=\'text\' name=\'' + name + '\' value=\'' + value + '\' placeholder=\'enter a ' + name +
                    '\' id=\'ooui-php-' + nextId++ + '\' class=\'oo-ui-inputWidget-input\' /> \
                  </div> \
                </div> \
              </div> \
            </div>';
		return $( $.parseHTML( fixture, document ) )
			.appendTo( document.body );
	};

	var createFakeFormForLang = function ( languageCode ) {
		var $result = $( document.createElement( 'div' ) )
			.appendTo( document.body );
		$result.append( createInputWidget( 'lang', languageCode ) );
		$result.append( createInputWidget( 'label' ) );
		$result.append( createInputWidget( 'description' ) );
		$result.append( createInputWidget( 'aliases' ) );
		return $result;
	};

	var verifyAndHydrateFakeForm = function ( assert, $fixture, languageCode ) {
		assert.strictEqual(
			$( document.getElementsByName( 'lang' ) ).closest( '.oo-ui-inputWidget' ).length,
			1,
			'Expected language element to be present'
		);
		wb.hydrateLanguageLabelInputForm();
		assert.strictEqual( $fixture.find( 'input[name="lang"]' ).val(), languageCode );
	};

	QUnit.test( 'Expect placeholders to be overridden on hydrate for English', ( assert ) => {
		var $fixture = createFakeFormForLang( 'en' );
		verifyAndHydrateFakeForm( assert, $fixture, 'en' );
		[ 'label', 'description', 'aliases' ].forEach( ( inputName ) => {
			var expectedPlaceholder = '(wikibase-' + inputName + '-edit-placeholder-language-aware: English)';
			assert.strictEqual(
				$fixture.find( 'input[name="' + inputName + '"]' ).attr( 'placeholder' ),
				expectedPlaceholder
			);
		} );
		$fixture.remove();
	} );

	QUnit.test( 'Expect placeholders not to be overridden for mul', ( assert ) => {
		mw.config.set( 'wgCanonicalSpecialPageName', 'NewItem' );
		var $fixture = createFakeFormForLang( 'mul' );
		verifyAndHydrateFakeForm( assert, $fixture, 'mul' );
		assert.strictEqual(
			$fixture.find( 'input[name="description"]' ).attr( 'placeholder' ),
			''
		);
		[ 'label', 'aliases' ].forEach( ( inputName ) => {
			var expectedPlaceholder = '(wikibase-' + inputName + '-edit-placeholder-mul)';
			assert.strictEqual(
				$fixture.find( 'input[name="' + inputName + '"]' ).attr( 'placeholder' ),
				expectedPlaceholder
			);
		} );
		$fixture.remove();
	} );

	QUnit.test( 'Expect description field to be disabled for mul', ( assert ) => {
		mw.config.set( 'wgCanonicalSpecialPageName', 'NewItem' );
		var $fixture = createFakeFormForLang( 'mul' );
		verifyAndHydrateFakeForm( assert, $fixture, 'mul' );
		assert.true(
			$fixture.find( 'input[name="description"]' ).is( ':disabled' )
		);
		$fixture.remove();
	} );

	QUnit.test( 'Expect the description disabled notice matches the page', ( assert ) => {
		[
			[ 'NewItem', 'wikibase-item-description-edit-not-supported' ],
			[ 'NewProperty', 'wikibase-property-description-edit-not-supported' ]
		].forEach( ( testCase ) => {
			var pageName = testCase[ 0 ];
			var messageKey = testCase[ 1 ];
			mw.config.set( 'wgCanonicalSpecialPageName', pageName );
			var $fixture = createFakeFormForLang( 'mul' );
			verifyAndHydrateFakeForm( assert, $fixture, 'mul' );
			assert.strictEqual(
				$fixture.find( '.oo-ui-fieldLayout-messages' ).text(), '(' + messageKey + ')'
			);
			$fixture.remove();
		} );
	} );

}( wikibase ) );
