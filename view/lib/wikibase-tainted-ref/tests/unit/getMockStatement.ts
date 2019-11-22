import { Statement } from '@/definitions/wikibase-js-datamodel/Statement';

export default function getMockStatement(
	mainSnakEqual: boolean,
	referencesEqual = true,
	emptyReferences = false,
	itemPresent = true,
	qualifiersEqual = true,
): Statement {
	return {
		getClaim: () => {
			return {
				getMainSnak: () => {
					return { equals: () => mainSnakEqual };
				},
				getGuid: jest.fn(),
				getQualifiers: () => {
					return { equals: () => qualifiersEqual };
				},
			};
		},
		getReferences: () => {
			return {
				equals: () => referencesEqual,
				isEmpty: () => emptyReferences,
				hasItem: () => itemPresent,
				length: 1,
				each: jest.fn(),
			};
		},
	};
}
