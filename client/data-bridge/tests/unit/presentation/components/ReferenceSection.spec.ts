import Vue from 'vue';
import ReferenceSection from '@/presentation/components/ReferenceSection.vue';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import { createStore } from '@/store';
import Vuex, {
	Store,
} from 'vuex';
import Application from '@/store/Application';
import newMockServiceContainer from '../../services/newMockServiceContainer';
import MessageKeys from '@/definitions/MessageKeys';

const localVue = createLocalVue();

localVue.use( Vuex );

const REFERENCE_ITEM_SELECTOR = '.wb-db-references__listItem';

describe( 'ReferenceSection', () => {
	let store: Store<Application>;

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
		Vue.set( store, 'getters', { targetReferences: [] } );

		const wrapper = shallowMount( ReferenceSection, {
			store,
			localVue,
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					getText,
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
			store,
			localVue,
		} );

		const references = wrapper.findAll( REFERENCE_ITEM_SELECTOR );
		expect( references ).toHaveLength( renderedTargetReferences.length );
		renderedTargetReferences.forEach( ( referenceHTML, index ) => {
			expect( references.at( index ).element.innerHTML ).toBe( referenceHTML );
		} );
	} );

	it( 'does not show renderedTargetReferences HTML if there are none', () => {
		const renderedTargetReferences: string[] = [];
		store.commit( 'setRenderedTargetReferences', renderedTargetReferences );
		const wrapper = shallowMount( ReferenceSection, {
			store,
			localVue,
		} );

		expect( wrapper.find( REFERENCE_ITEM_SELECTOR ).exists() ).toBe( false );
	} );

} );
