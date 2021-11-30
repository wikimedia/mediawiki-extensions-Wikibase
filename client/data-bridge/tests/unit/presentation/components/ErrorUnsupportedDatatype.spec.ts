import { shallowMount, mount } from '@vue/test-utils';
import ErrorUnsupportedDatatype from '@/presentation/components/ErrorUnsupportedDatatype.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import MessageKeys from '@/definitions/MessageKeys';
import { calledWithHTMLElement } from '../../../util/assertions';
import { createTestStore } from '../../../util/store';

describe( 'ErrorUnsupportedDatatype', () => {
	const targetProperty = 'P569',
		pageTitle = 'Marie_Curie',
		originalHref = 'https://www.wikidata.org/wiki/Q7186',
		dataType = 'time',
		messageGet = jest.fn( ( key ) => key ),
		store = createTestStore( {
			state: {
				targetProperty,
				pageTitle,
				originalHref,
			},
		} );

	it( 'uses IconMessageBox to display the error message', () => {
		const wrapper = shallowMount( ErrorUnsupportedDatatype, {
			propsData: {
				dataType,
			},
			global: {
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
					},
				},
				plugins: [ store ],
			},
		} );
		expect( wrapper.findComponent( IconMessageBox ).exists() ).toBe( true );
	} );

	it( 'passes a slot to IconMessageBox which contains the header message', () => {
		mount( ErrorUnsupportedDatatype, {
			propsData: {
				dataType,
			},
			global: {
				stubs: { BailoutActions: true },
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
					},
				},
				plugins: [ store ],
			},
		} );

		calledWithHTMLElement( messageGet, 0, 1 );

		expect( messageGet ).toHaveBeenNthCalledWith(
			1,
			MessageKeys.UNSUPPORTED_DATATYPE_ERROR_HEAD,
			`<span class="wb-db-term-label" lang="zxx" dir="auto">${targetProperty}</span>`,
		);
	} );

	it( 'shows the message body', () => {
		mount( ErrorUnsupportedDatatype, {
			propsData: {
				dataType,
			},
			global: {
				stubs: { BailoutActions: true },
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
					},
				},
				plugins: [ store ],
			},
		} );

		calledWithHTMLElement( messageGet, 1, 1 );

		expect( messageGet ).toHaveBeenNthCalledWith(
			2,
			MessageKeys.UNSUPPORTED_DATATYPE_ERROR_BODY,
			`<span class="wb-db-term-label" lang="zxx" dir="auto">${targetProperty}</span>`,
			dataType,
		);
	} );

	it( 'uses BailoutActions to provide a bail out path for unsupported data type', () => {
		const wrapper = shallowMount( ErrorUnsupportedDatatype, {
			propsData: {
				dataType,
			},
			global: {
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
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
