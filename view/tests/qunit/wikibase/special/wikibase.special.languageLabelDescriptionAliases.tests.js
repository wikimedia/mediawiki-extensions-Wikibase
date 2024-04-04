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

	var createFakeFormForLang = function ( language ) {
		var $result = $( document.createElement( 'div' ) )
			.appendTo( document.body );
		$result.append( createInputWidget( 'lang', language ) );
		$result.append( createInputWidget( 'label' ) );
		$result.append( createInputWidget( 'description' ) );
		$result.append( createInputWidget( 'aliases' ) );
		return $result;
	};

	QUnit.test( 'Expect placeholders to be overridden on hydrate for English', function ( assert ) {
		var $fixture = createFakeFormForLang( 'en' );
		assert.strictEqual(
			$( document.getElementsByName( 'lang' ) ).closest( '.oo-ui-inputWidget' ).length,
			1,
			'Expected language element to be present'
		);
		wb.hydrateLanguageLabelInputForm();
		assert.strictEqual( $fixture.find( 'input[name="lang"]' ).val(), 'en' );
		[ 'label', 'description', 'aliases' ].forEach( function ( inputName ) {
			var expectedPlaceholder = '(wikibase-' + inputName + '-edit-placeholder-language-aware: English)';
			assert.strictEqual(
				$fixture.find( 'input[name="' + inputName + '"]' ).attr( 'placeholder' ),
				expectedPlaceholder
			);
		} );
		$fixture.remove();
	} );

	QUnit.test( 'Expect placeholders not to be overriden for mul', function ( assert ) {
		var $fixture = createFakeFormForLang( 'mul' );
		assert.strictEqual(
			$( document.getElementsByName( 'lang' ) ).closest( '.oo-ui-inputWidget' ).length,
			1,
			'Expected language element to be present'
		);
		wb.hydrateLanguageLabelInputForm();
		assert.strictEqual( $fixture.find( 'input[name="lang"]' ).val(), 'mul' );
		assert.strictEqual(
			$fixture.find( 'input[name="description"]' ).attr( 'placeholder' ),
			''
		);
		[ 'label', 'aliases' ].forEach( function ( inputName ) {
			var expectedPlaceholder = '(wikibase-' + inputName + '-edit-placeholder-mul)';
			assert.strictEqual(
				$fixture.find( 'input[name="' + inputName + '"]' ).attr( 'placeholder' ),
				expectedPlaceholder
			);
		} );
		$fixture.remove();
	} );

}( wikibase ) );
