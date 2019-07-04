import Snak from '@/datamodel/Snak';
import QualifierMap from '@/datamodel/QualifierMap';
import Reference from '@/datamodel/Reference';

type Rank = 'preferred'|'normal'|'deprecated';

interface Statement {
	id: string;
	mainsnak: Snak;
	rank: Rank;
	qualifiers?: QualifierMap;
	'qualifiers-order'?: string[];
	references?: Reference[];
	type: 'statement';
}

export default Statement;
