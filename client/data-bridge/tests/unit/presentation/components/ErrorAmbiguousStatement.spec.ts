import { shallowMount, mount } from '@vue/test-utils';
import ErrorAmbiguousStatement from '@/presentation/components/ErrorAmbiguousStatement.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import MessageKeys from '@/definitions/MessageKeys';
import { calledWithHTMLElement } from '../../../util/assertions';
import { createTestStore } from '../../../util/store';

describe( 'ErrorAmbiguousStatement', () => {
	const targetProperty = 'P569',
		pageTitle = 'Marie_Curie',
		originalHref = 'https://www.wikidata.org/wiki/Q7186',
		messageGet = jest.fn( ( key ) => key ),
		store = createTestStore( {
			state: {
				targetProperty,
				pageTitle,
				originalHref,
			},
		} );

	it( 'uses IconMessageBox to display the error header and body messages', () => {
		const wrapper = mount( ErrorAmbiguousStatement, {
			global: {
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
						getText: messageGet,
					},
				},
				plugins: [ store ],
				stubs: { BailoutActions: true },
			},
		} );

		calledWithHTMLElement( messageGet, 1, 1 );

		expect( wrapper.findComponent( IconMessageBox ).exists() ).toBe( true );
		expect( messageGet ).toHaveBeenNthCalledWith(
			1,
			MessageKeys.AMBIGUOUS_STATEMENT_ERROR_HEAD,
		);
		expect( messageGet ).toHaveBeenNthCalledWith(
			2,
			MessageKeys.AMBIGUOUS_STATEMENT_ERROR_BODY,
			`<span class="wb-db-term-label" lang="zxx" dir="auto">${targetProperty}</span>`,
		);

	} );

	it( 'uses BailoutActions to provide a bail out path for the ambiguous statement error', () => {
		const wrapper = shallowMount( ErrorAmbiguousStatement, {
			global: {
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
						getText: messageGet,
					},
				},
				plugins: [ store ],
			},
		} );

		expect( wrapper.findComponent( BailoutActions ).exists() ).toBe( true );
		expect( wrapper.findComponent( BailoutActions ).props() ).toStrictEqual( {
			originalHref,
			pageTitle,
		} );
	} );

} );
