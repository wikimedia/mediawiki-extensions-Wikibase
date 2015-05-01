<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\FingerprintDeserializer;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @covers Wikibase\DataModel\Deserializers\FingerprintDeserializer
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class FingerprintDeserializerTest extends DeserializerBaseTest {

	/**
	 * @return Deserializer
	 */
	public function buildDeserializer() {
		return new FingerprintDeserializer();
	}

	public function deserializableProvider() {
		return array(
			array(
				array()
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
		);
	}

	public function deserializationProvider() {
		$provider = array(
			array(
				new Fingerprint(),
				array(
				)
			),
		);

		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'Nyan Cat' );
		$fingerprint->setLabel( 'fr', 'Nyan Cat' );

		$provider[] = array(
			$fingerprint,
			array(
				'labels' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'Nyan Cat'
					),
					'fr' => array(
						'language' => 'fr',
						'value' => 'Nyan Cat'
					)
				)
			)
		);

		$fingerprint = new Fingerprint();
		$fingerprint->setDescription( 'en', 'A Nyan Cat' );
		$fingerprint->setDescription( 'fr', 'A Nyan Cat' );

		$provider[] = array(
			$fingerprint,
			array(
				'descriptions' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'A Nyan Cat'
					),
					'fr' => array(
						'language' => 'fr',
						'value' => 'A Nyan Cat'
					)
				)
			)
		);

		$fingerprint = new Fingerprint();
		$fingerprint->setAliasGroup( 'en', array( 'Cat', 'My cat' ) );
		$fingerprint->setAliasGroup( 'fr', array( 'Cat' ) );
		$provider[] = array(
			$fingerprint,
			array(
				'aliases' => array(
					'en' => array(
						array(
							'language' => 'en',
							'value' => 'Cat'
						),
						array(
							'language' => 'en',
							'value' => 'My cat'
						)
					),
					'fr' => array(
						array(
							'language' => 'fr',
							'value' => 'Cat'
						)
					)
				)
			)
		);

		return $provider;
	}

	/**
	 * @dataProvider invalidDeserializationProvider
	 */
	public function testInvalidSerialization( $serialization ) {
		$this->setExpectedException( '\Deserializers\Exceptions\DeserializationException' );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public function invalidDeserializationProvider() {
		return array(
			'label with integer language code' => array(
				array(
					'labels' => array(
						8 => array(
							'language' => 8,
							'value' => 'Cat',
						),
					),
				),
			),
			'label without array key for language code' => array(
				array(
					'labels' => array(
						array(
							'language' => 'en',
							'value' => 'Cat',
						),
					),
				),
			),
			'label with integer value' => array(
				array(
					'labels' => array(
						'en' => array(
							'language' => 'en',
							'value' => 8,
						),
					),
				),
			),
			'alias with interger language code' => array(
				array(
					'aliases' => array(
						8 =>
							array(
								array(
									'language' =>  8,
									'value' => 'Cat',
								),
							),
					),
				)
			),
			'alias without array key for language code' => array(
				array(
					'aliases' => array(
						array(
							array(
								'language' =>  'en',
								'value' => 'Cat',
							),
						),
					),
				)
			),
			'alias as a string only' => array(
				array(
					'aliases' => array(
						'en' => 'Cat'
					)
				)
			),
			'label fallback language term' => array(
				array(
					'labels' => array(
						'en' => array(
							'language' => 'en-cat',
							'value' => 'mew',
						),
					),
				),
			),
			'label with integer fallback language code' => array(
				array(
					'labels' => array(
						'en' => array(
							'language' => 8,
							'value' => 'Cat',
						),
					),
				),
			),
			'label language term with source' => array(
				array(
					'labels' => array(
						'en-cat' => array(
							'language' => 'en-cat',
							'value' => 'mew',
							'source' => 'en',
						),
					),
				),
			),
			'description fallback language term' => array(
				array(
					'descriptions' => array(
						'en' => array(
							'language' => 'en-cat',
							'value' => 'mew',
						),
					),
				),
			),
			'description language term with source' => array(
				array(
					'descriptions' => array(
						'en-cat' => array(
							'language' => 'en-cat',
							'value' => 'mew',
							'source' => 'en',
						),
					),
				),
			),
			'alias fallback language term' => array(
				array(
					'aliases' => array(
						'en' =>
							array(
								array(
									'language' =>  'en-cat',
									'value' => 'mew',
								),
							),
					),
				)
			),
			'alias language term with source' => array(
				array(
					'aliases' => array(
						'en-cat' =>
							array(
								array(
									'language' =>  'en-cat',
									'value' => 'mew',
									'source' => 'en',
								),
							),
					),
				)
			),
		);
	}


}

