<?php

namespace Wikibase\Repo\Tests\Specials;

use SpecialPageTestBase;
use Wikibase\Repo\Specials\SpecialNewProperty;

/**
 * @covers Wikibase\Repo\Specials\SpecialNewProperty
 * @covers Wikibase\Repo\Specials\SpecialNewEntity
 * @covers Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Addshore
 */
class SpecialNewPropertyTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialNewProperty();
	}

	public function testExecute() {
		//TODO: Verify that more of the output is correct.
		//TODO: Verify that item creation works via a faux post request

		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [ 'property-create' => true ] ] );

		$matchers['label'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-newentity-label',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'label',
				]
			] ];
		$matchers['description'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-newentity-description',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'description',
				]
			] ];
		$matchers['submit'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-newentity-submit',
			],
			'child' => [
				'tag' => 'button',
				'attributes' => [
					'type' => 'submit',
					'name' => 'submit',
				]
			] ];

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'LabelText/DescriptionText' );
		$matchers['label']['child'][0]['attributes']['value'] = 'LabelText';
		$matchers['description']['child'][0]['attributes']['value'] = 'DescriptionText';

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
