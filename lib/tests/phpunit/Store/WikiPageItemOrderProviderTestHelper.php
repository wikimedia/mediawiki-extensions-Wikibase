<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

/**
 * Helper class for tests for WikiPageItemOrderProvider subclasses.
 *
 * @license GPL-2.0-or-later
 * @author Noa Rave
 */
class WikiPageItemOrderProviderTestHelper {

	public static function provideGetItemOrder(): iterable {
		return [
			'empty page' => [
				'',
				[],
			],
			'syntax that is not accepted' => [
				"*\nQ1\n"
				. "* Q2Q\n"
				. " # Q3\n"
				. " * Q4\n"
				. "* Item:Q5\n"
				. "* [[d:Project:Q6]]\n"
				. "* d:Q7\n"
				. "* {{q|8}}\n"
				. "* {{q|P11}}",
				[],
			],
			'simple match' => [
				"* Q1 \n"
				. "*Q133 \n"
				. "* q5", // Testing for lower case item IDs
				[ 'Q1' => 0, 'Q133' => 1, 'Q5' => 2 ],
			],
			'multiple bullets' => [
				"* Q1 \n"
				. "** Q2 \n",
				[ 'Q1' => 0, 'Q2' => 1 ],
			],
			'ordered list' => [
				"# Q1 \n"
				. "# Q2 \n",
				[ 'Q1' => 0, 'Q2' => 1 ],
			],
			'ordered with multiple pounds' => [
				"# Q1 \n"
				. "## Q2 \n",
				[ 'Q1' => 0, 'Q2' => 1 ],
			],
			'mixed bullets and pounds' => [
				"# Q1 \n"
				. "#* Q2 \n",
				[ 'Q1' => 0, 'Q2' => 1 ],
			],
			'strip multiline comment' => [
				"* Q1 \n"
				. "<!-- * Q133 \n"
				. "* Q5 -->",
				[ 'Q1' => 0 ],
			],
			'muliple comments' => [
				"* Q1 \n"
				. "<!-- * Q133 --> \n"
				. "* <!-- Q5 -->",
				[ 'Q1' => 0 ],
			],
			'bullet point glibberish' => [
				"* Q1 \n"
				. "* Q133 \n"
				. "* Q5 Unicorns are all \n"
				. "*  very beautiful!\n"
				. "** This is a subheading",
				[ 'Q1' => 0, 'Q133' => 1, 'Q5' => 2 ],
			],
			'additional text' => [
				"* Q1 \n"
				. "* Q133 \n"
				. "* Q5 Unicorns are all \n"
				. "very beautiful!",
				[ 'Q1' => 0, 'Q133' => 1, 'Q5' => 2 ],
			],
			'wiki links' => [
				"*\t[[Item:Q9]]\n"
				. "* [[Item:Q8|Q1008]]\n"
				. "* [[d:Item:Q7]]\n"
				. "* [[P6|Q1006]]",
				[ 'Q9' => 0, 'Q8' => 1, 'Q7' => 2 ],
			],
			'templates' => [
				"* {{NiceTemplate|q7}}\n"
				. "* {{NiceTemplate|q8}}\n"
				. "* {{|Q9}}\n"
				. "* {{Q|10}}\n"
				. "* {{Q11}}\n"
				. "* {{C0nfusingT3mpl4te|Q12}}\n",
				[ 'Q7' => 0, 'Q8' => 1 ],
			],
		];
	}

}
