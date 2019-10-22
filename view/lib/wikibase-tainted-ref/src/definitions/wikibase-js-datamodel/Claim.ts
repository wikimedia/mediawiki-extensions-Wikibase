import { Snak } from '@/definitions/wikibase-js-datamodel/Snak';

export interface Claim {
	getGuid(): string;
	getMainSnak(): Snak;
}
