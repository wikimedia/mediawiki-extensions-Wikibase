<?php

namespace Wikibase\Repo\Tests\Specials;

use HamcrestPHPUnitIntegration;

/**
 * @license GPL-2.0-or-later
 */
trait HtmlAssertionHelpers {
	use HamcrestPHPUnitIntegration;

	/**
	 * @param string $html
	 * @param string $name
	 */
	protected function assertHtmlContainsInputWithName( $html, $name ) {

		$this->assertThatHamcrest(
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

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline( "<input name='$name' value='$value'/>" ) ) ) ) );
	}

	/**
	 * @param string $html
	 * @param string $name
	 */
	protected function assertHtmlContainsSelectWithName( $html, $name ) {

		$this->assertThatHamcrest(
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

		$this->assertThatHamcrest(
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
		$this->assertThatHamcrest(
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
		$ooUiErrorMessage = tagMatchingOutline( '<div class="oo-ui-flaggedElement-error"/>' );

		$this->assertThatHamcrest( $html, is( htmlPiece(
			havingChild(
				both( either( $formErrorMessage )->orElse( $ooUiErrorMessage ) )
					->andAlso( havingTextContents( containsString( $messageText )->ignoringCase() ) ) ) ) ) );
	}

}
