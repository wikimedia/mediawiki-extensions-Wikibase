import { shallowMount } from '@vue/test-utils';
import SingleReferenceDisplay from '@/presentation/components/SingleReferenceDisplay.vue';

describe( 'SingleReferenceDisplay', () => {
	it( 'shows primitive datavalue values as they are', () => {
		const wrapper = shallowMount(
			SingleReferenceDisplay, {
				propsData: {
					reference: {
						snaks: {
							P214: [ {
								snaktype: 'value',
								property: 'P214',
								datavalue: {
									value: '113230702',
									type: 'string',
								},
								datatype: 'external-id',
							} ],
						},
						'snaks-order': [
							'P214',
						],
					},
				},
			},
		);

		expect( wrapper.text() ).toBe( '113230702' );
	} );

	it( 'shows complex datavalue values stringified', () => {
		const wrapper = shallowMount(
			SingleReferenceDisplay, {
				propsData: {
					reference: {
						snaks: {
							P248: [ {
								snaktype: 'value',
								property: 'P248',
								datavalue: {
									value: {
										'entity-type': 'item',
										'numeric-id': 54919,
										id: 'Q54919',
									},
									type: 'wikibase-entityid',
								},
								datatype: 'wikibase-item',
							} ],
						},
						'snaks-order': [
							'P248',
						],
					},
				},
			},
		);

		expect( wrapper.text() ).toBe( '{"entity-type":"item","numeric-id":54919,"id":"Q54919"}' );
	} );

	it( 'shows snaks for different properties next to each other', () => {
		const wrapper = shallowMount(
			SingleReferenceDisplay, {
				propsData: {
					reference: {
						snaks: {
							P123: [ {
								snaktype: 'value',
								property: 'P214',
								datavalue: {
									value: 'second',
									type: 'string',
								},
								datatype: 'external-id',
							} ],
							P214: [ {
								snaktype: 'value',
								property: 'P214',
								datavalue: {
									value: 'first',
									type: 'string',
								},
								datatype: 'external-id',
							} ],
						},
						'snaks-order': [
							'P214',
							'P123',
						],
					},
				},
			},
		);

		expect( wrapper.text() ).toBe( 'first. second' );
	} );

	it( 'uses the supplied separator', () => {
		const wrapper = shallowMount(
			SingleReferenceDisplay, {
				propsData: {
					reference: {
						snaks: {
							P123: [ {
								snaktype: 'value',
								property: 'P214',
								datavalue: {
									value: 'second',
									type: 'string',
								},
								datatype: 'external-id',
							} ],
							P214: [ {
								snaktype: 'value',
								property: 'P214',
								datavalue: {
									value: 'first',
									type: 'string',
								},
								datatype: 'external-id',
							} ],
						},
						'snaks-order': [
							'P214',
							'P123',
						],
					},
					separator: ', ',
				},
			},
		);

		expect( wrapper.text() ).toBe( 'first, second' );
	} );

	it( 'shows multiple snaks for a single property next to each other', () => {
		const wrapper = shallowMount(
			SingleReferenceDisplay, {
				propsData: {
					reference: {
						snaks: {
							P214: [ {
								snaktype: 'value',
								property: 'P214',
								datavalue: {
									value: 'first',
									type: 'string',
								},
								datatype: 'external-id',
							}, {
								snaktype: 'value',
								property: 'P214',
								datavalue: {
									value: 'second',
									type: 'string',
								},
								datatype: 'external-id',
							} ],
						},
						'snaks-order': [
							'P214',
						],
					},
				},
			},
		);

		expect( wrapper.text() ).toBe( 'first. second' );
	} );

	it( 'ignores snaks with novalue or somevalue', () => {
		const wrapper = shallowMount(
			SingleReferenceDisplay, {
				propsData: {
					reference: {
						snaks: {
							P214: [ {
								snaktype: 'novalue',
								property: 'P214',
								datavalue: {
									value: 'first',
									type: 'string',
								},
								datatype: 'external-id',
							}, {
								snaktype: 'somevalue',
								property: 'P214',
								datavalue: {
									value: 'second',
									type: 'string',
								},
								datatype: 'external-id',
							} ],
						},
						'snaks-order': [
							'P214',
						],
					},
				},
			},
		);

		expect( wrapper.text() ).toBe( '' );
	} );
} );
