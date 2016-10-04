<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use RequestContext;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Specials\SpecialNewItem;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Specials\SpecialNewItem
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
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
class SpecialNewItemTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialNewItem();
	}

	public function testExecute_creationForm() {
		//TODO: Verify that more of the output is correct.

		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [ 'createpage' => true ] ] );

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

	public function testExecute_itemCreation() {
		$user = RequestContext::getMain()->getUser();

		$this->setMwGlobals(
			[
				'wgGroupPermissions' =>
				[ '*' => [
					'createpage' => true,
					'edit' => true
				] ],
				'wgArticlePath' => '/$1',
				'wgServer' => 'much.data',
			]
		);

		$label = 'SpecialNewItemTest label';
		$description = 'SpecialNewItemTest description';
		$request = new FauxRequest(
			[
				'lang' => 'en',
				'label' => $label,
				'description' => $description,
				'aliases' => '',
				'wpEditToken' => $user->getEditToken()
			],
			true
		);

		list( $output, $webResponse ) = $this->executeSpecialPage( '', $request );
		$this->assertSame( '', $output );

		$itemUrl = $webResponse->getHeader( 'location' );
		$itemIdSerialization = preg_replace( '@much\.data/(.*:)(Q\d+)@', '$2', $itemUrl );
		$itemId = new ItemId( $itemIdSerialization );

		/* @var $item Item */
		$item = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $itemId );
		$this->assertInstanceOf( Item::class, $item );

		$this->assertSame(
			$label,
			$item->getLabels()->getByLanguage( 'en' )->getText()
		);
		$this->assertSame(
			$description,
			$item->getDescriptions()->getByLanguage( 'en' )->getText()
		);
	}

}
