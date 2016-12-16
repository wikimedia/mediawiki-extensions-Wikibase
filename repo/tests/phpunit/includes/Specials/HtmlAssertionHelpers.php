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
		$matcher = [
			'tag' => 'input',
			'attributes' => [
				'name' => $name,
			],
		];
		$this->assertTag(
			$matcher,
			$html,
			"Failed to find input element with name '{$name}'"
		);
	}

	/**
	 * @param string $html
	 * @param string $name
	 * @param string $value
	 */
	protected function assertHtmlContainsInputWithNameAndValue( $html, $name, $value ) {
		$matcher = [
			'tag' => 'input',
			'attributes' => [
				'name' => $name,
				'value' => $value,
			],
		];
		$this->assertTag(
			$matcher,
			$html,
			"Failed to find input element with name '{$name}' and value '{$value}' in :\n{$html}"
		);
	}

	/**
	 * @param string $html
	 * @param string $name
	 */
	protected function assertHtmlContainsSelectWithName( $html, $name ) {
		$matcher = [
			'tag' => 'select',
			'attributes' => [
				'name' => $name,
			],
		];
		$this->assertTag(
			$matcher,
			$html,
			"Failed to find select element with name '{$name}' in html:\n\n {$html}"
		);
	}

	/**
	 * @param string $html
	 * @param string $name
	 * @param string $value
	 */
	protected function assertHtmlContainsSelectWithNameAndSelectedValue( $html, $name, $value ) {
		$matcher = [
			'tag' => 'select',
			'attributes' => [
				'name' => $name,
			],
			'child' => [
				'tag' => 'option',
				'attributes' => [
					'value' => $value,
					'selected' => 'selected',
				],
			],
		];
		$this->assertTag(
			$matcher,
			$html,
			"Failed to find select element with name '{$name}' and selected value '{$value}'" .
			" in html:\n\n {$html}"
		);
	}

	/**
	 * @param string $html
	 */
	protected function assertHtmlContainsSubmitControl( $html ) {
		$matchers = [
			'button submit' => [
				'tag' => 'button',
				'attributes' => [
					'type' => 'submit',
				],
			],
			'input submit' => [
				'tag' => 'input',
				'attributes' => [
					'type' => 'submit',
				],
			],
		];

		foreach ( $matchers as $matcher ) {
			try {
				$this->assertTag( $matcher, $html, "Failed to find submit element" );

				return;
			} catch ( \PHPUnit_Framework_ExpectationFailedException $exception ) {
				continue;
			}
		}

		$this->fail( "Failed to find submit element" );
	}

	protected function assertHtmlContainsErrorMessage( $html, $messageText ) {
		$assertions = [
			[ $this, 'assertHtmlContainsFormErrorMessage' ],
			[ $this, 'assertHtmlContainsOOUiErrorMessage' ],
		];

		foreach ( $assertions as $assertion ) {
			try {
				$assertion( $html, $messageText );

				return;
			} catch ( \PHPUnit_Framework_ExpectationFailedException $exception ) {
				continue;
			}
		}

		$this->fail(
			"Failed to find error message with text '{$messageText}' in html:\n\n {$html}"
		);
	}

	protected function assertHtmlContainsFormErrorMessage( $html, $messageText ) {
		$matcher = [
			'tag' => 'div',
			'class' => 'error',
			'content' => 'regexp: /' . preg_quote( $messageText, '/' ) . '/ui',
		];
		$this->assertTag(
			$matcher,
			$html,
			"Failed to find form error message with text '{$messageText}' in html:\n\n {$html}"
		);
	}

	protected function assertHtmlContainsOOUiErrorMessage( $html, $messageText ) {
		$matcher = [
			'tag' => 'li',
			'class' => 'oo-ui-fieldLayout-messages-error',
			'content' => 'regexp: /' . preg_quote( $messageText, '/' ) . '/ui',
		];
		$this->assertTag(
			$matcher,
			$html,
			"Failed to find OO-UI error message with text '{$messageText}' in html:\n\n {$html}"
		);
	}

	/**
	 * @param array $matcher Associative array of structure required by $options argument
	 * 				of {@see \PHPUnit_Util_XML::findNodes}
	 * @param string $htmlOrXml
	 * @param string $message
	 * @param bool $isHtml
	 *
	 *  @see \MediaWikiTestCase::assertTag
	 */
	abstract protected function assertTag( $matcher, $htmlOrXml, $message = '', $isHtml = true );

	/**
	 * @param string $message
	 *
	 * @see \PHPUnit_Framework_Assert::fail
	 */
	abstract public function fail( $message = '' );

}
