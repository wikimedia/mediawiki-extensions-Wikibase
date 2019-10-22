import { Claim } from '@/definitions/wikibase-js-datamodel/Claim';

export interface Statement {
	getClaim(): Claim;
	getReferences(): ReferenceList;
}
