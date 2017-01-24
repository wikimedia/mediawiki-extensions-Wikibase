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
			is( htmlPiece( havingChild(
				both( withTagName( 'input' ) )
					->andAlso( withAttribute( 'name' )->havingValue( $name ) ) ) ) )
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
			is( htmlPiece( havingChild(
				allOf(
					withTagName( 'input' ),
					withAttribute( 'name' )->havingValue( $name ),
					withAttribute( 'value' )->havingValue( $value ) ) ) ) ) );
	}

	/**
	 * @param string $html
	 * @param string $name
	 */
	protected function assertHtmlContainsSelectWithName( $html, $name ) {

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'select' ) )
					->andAlso( withAttribute( 'name' )->havingValue( $name ) ) ) ) )
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
							withTagName( 'select' ),
							withAttribute( 'name' )->havingValue( $name ),
							havingDirectChild(
								allOf(
									withTagName( 'option' ),
									withAttribute( 'value' )->havingValue( $value ),
									withAttribute( 'selected' )
								)
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
							both( withTagName( 'button' ) )->andAlso(
									either( withAttribute( 'type' )->havingValue( 'submit' ) )->orElse( not( withAttribute( 'type' ) ) )
								)
						)->orElse(
							both( withTagName( 'input' ) )->andAlso( withAttribute( 'type' )->havingValue( 'submit' ) )
						)
					)
				)
			)
		);
	}

	protected function assertHtmlContainsErrorMessage( $html, $messageText ) {

		$formErrorMessage = allOf(
			withTagName( 'div' ),
			//TODO Use class matcher
			withAttribute( 'class' )->havingValue( containsString( 'error' ) )
		);

		$ooUiErrorMessage = allOf(
			withTagName( 'li' ),
			//TODO Use class matcher
			withAttribute( 'class' )->havingValue( containsString( 'oo-ui-fieldLayout-messages-error' ) )
		);

		assertThat( $html, is( htmlPiece(
			havingChild(
				both( either( $formErrorMessage )->orElse( $ooUiErrorMessage ) )
					->andAlso( 	havingTextContents( containsString( $messageText )->ignoringCase() ) ) ) ) ) );
	}
}
