<?php

namespace Wikibase\Repo\Tests\Specials;

/**
 * @license GPL-2.0+
 */
trait HtmlAssertionHelpers {

	/**
	 * @param string $html
	 * @param string $name
	 */
	protected function assertHtmlContainsInputWithName( $html, $name ) {

		assertThat(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline( "<input name='$name'/>" ) ) ) )
		);
	}

	/**
	 * @param string $html
	 * @param string $name
	 * @param string $value
	 */
	protected function assertHtmlContainsInputWithNameAndValue( $html, $name, $value ) {

		assertThat(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline( "<input name='$name' value='$value'/>" ) ) ) ) );
	}

	/**
	 * @param string $html
	 * @param string $name
	 */
	protected function assertHtmlContainsSelectWithName( $html, $name ) {

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<select name='$name'/>" ) ) ) )
		);
	}

	/**
	 * @param string $html
	 * @param string $name
	 * @param string $value
	 */
	protected function assertHtmlContainsSelectWithNameAndSelectedValue( $html, $name, $value ) {

		assertThat(
			$html,
			is(
				htmlPiece(
					havingChild(
						allOf(
							tagMatchingOutline( "<select name='$name'/>" ),
							havingDirectChild(
								tagMatchingOutline( "<option value='$value' selected/>" )
							)
						)
					)
				)
			)
		);
	}

	/**
	 * @param string $html
	 */
	protected function assertHtmlContainsSubmitControl( $html ) {
		assertThat(
			$html,
			is(
				htmlPiece(
					havingChild(
						either(
							both(
								withTagName( 'button' )
							)->andAlso(
									either(
										withAttribute( 'type' )->havingValue( 'submit' )
									)->orElse(
										not( withAttribute( 'type' ) )
									)
							)
						)->orElse(
							tagMatchingOutline( '<input type="submit"/>' )
						)
					)
				)
			)
		);
	}

	protected function assertHtmlContainsErrorMessage( $html, $messageText ) {
		$formErrorMessage = tagMatchingOutline( '<div class="error"/>' );
		$ooUiErrorMessage = tagMatchingOutline( '<li class="oo-ui-fieldLayout-messages-error"/>' );

		assertThat( $html, is( htmlPiece(
			havingChild(
				both( either( $formErrorMessage )->orElse( $ooUiErrorMessage ) )
					->andAlso( havingTextContents( containsString( $messageText )->ignoringCase() ) ) ) ) ) );
	}

}
