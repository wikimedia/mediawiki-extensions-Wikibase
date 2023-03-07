import ReferenceSection from '@/presentation/components/ReferenceSection.vue';
import { shallowMount } from '@vue/test-utils';
import { createStore } from '@/store';
import Application from '@/store/Application';
import newMockServiceContainer from '../../services/newMockServiceContainer';
import MessageKeys from '@/definitions/MessageKeys';
import { MutableStore } from '../../../util/store';

const REFERENCE_ITEM_SELECTOR = '.wb-db-references__listItem';

describe( 'ReferenceSection', () => {
	let store: MutableStore<Application>;

	beforeEach( () => {
		store = createStore( newMockServiceContainer( {} ) );
	} );

	it( 'shows the Reference message as the header', () => {
		const referenceHeading = 'here be credibility';
		const getText = jest.fn(
			( key: string ) => {
				if ( key === MessageKeys.REFERENCES_HEADING ) {
					return referenceHeading;
				}

				return '';
			},
		);
		store.getters = { targetReferences: [] };

		const wrapper = shallowMount( ReferenceSection, {
			global: {
				plugins: [ store ],
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						getText,
					},
				},
			},
		} );

		expect( wrapper.text() ).toBe( referenceHeading );
		expect( getText ).toHaveBeenCalledWith( MessageKeys.REFERENCES_HEADING );
	} );

	it( 'shows each renderedTargetReferences HTML if there are any', () => {
		const renderedTargetReferences = [
			'<span>foo</span>',
			'<span>bar</span>',
		];
		store.commit( 'setRenderedTargetReferences', renderedTargetReferences );
		const wrapper = shallowMount( ReferenceSection, {
			global: {
				plugins: [ store ],
			},
		} );

		const references = wrapper.findAll( REFERENCE_ITEM_SELECTOR );
		expect( references ).toHaveLength( renderedTargetReferences.length );
		renderedTargetReferences.forEach( ( referenceHTML, index ) => {
			expect( references[ index ].element.innerHTML ).toBe( referenceHTML );
		} );
	} );

	it( 'does not show renderedTargetReferences HTML if there are none', () => {
		const renderedTargetReferences: string[] = [];
		store.commit( 'setRenderedTargetReferences', renderedTargetReferences );
		const wrapper = shallowMount( ReferenceSection, {
			global: {
				plugins: [ store ],
			},
		} );

		expect( wrapper.find( REFERENCE_ITEM_SELECTOR ).exists() ).toBe( false );
	} );

} );
