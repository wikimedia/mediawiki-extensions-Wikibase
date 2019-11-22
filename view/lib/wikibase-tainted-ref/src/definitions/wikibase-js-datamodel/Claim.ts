import { Snak } from '@/definitions/wikibase-js-datamodel/Snak';
import { SnakList } from '@/definitions/wikibase-js-datamodel/SnakList';

export interface Claim {
	getGuid(): string;
	getMainSnak(): Snak;
	getQualifiers(): SnakList;
}
