import { Statement } from '@/definitions/wikibase-js-datamodel/Statement';

export default function getMockStatement(
	mainSnakEqual: boolean,
	referencesEqual: boolean = true,
	emptyReferences: boolean = false,
): Statement {
	return {
		getClaim: () => {
			return {
				getMainSnak: () => {
					return { equals: () => mainSnakEqual };
				},
				getGuid: jest.fn(),
			};
		},
		getReferences: () => {
			return {
				equals: () => referencesEqual,
				isEmpty: () => emptyReferences,
			};
		},
	};
}
