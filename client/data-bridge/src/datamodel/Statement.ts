import Snak from '@/datamodel/Snak';
import QualifierMap from '@/datamodel/QualifierMap';
import Reference from '@/datamodel/Reference';

export type Rank = 'preferred'|'normal'|'deprecated';

interface Statement {
	id?: string; // absent in new statements we create (fresh ID assigned server-side on save)
	mainsnak: Snak;
	rank: Rank;
	qualifiers?: QualifierMap; // may be absent if empty, to save space
	'qualifiers-order'?: string[]; // may be absent if empty
	references?: Reference[]; // may be absent if empty
	type: 'statement';
}

export default Statement;
