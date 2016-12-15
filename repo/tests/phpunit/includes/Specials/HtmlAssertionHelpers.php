<?php

namespace Wikibase\Repo\Tests\Specials;

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
			"Failed to find input element with name '{$name}' and value '{$value}'"
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
	 * @param string $name
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
			}
			catch ( \PHPUnit_Framework_ExpectationFailedException $exception ) {
				continue;
			}
		}

		$this->fail( "Failed to find submit element" );
	}

	protected function assertHtmlContainsErrorMessage( $html, $messageText ) {
		$matcher = [
			'tag' => 'div',
			'class' => 'error',
			'content' => 'regexp: /' . preg_quote( $messageText, '/' ) . '/ui',
		];
		$this->assertTag(
			$matcher,
			$html,
			"Failed to find error message with text '{$messageText}' in html:\n\n {$html}"
		);
	}

	abstract protected function assertTag($matcher, $actual, $message = '', $isHtml = true);

	abstract public function fail($message = '');
}
