import SnakMap from '@/datamodel/SnakMap';

interface Reference {
	hash: string;
	snaks: SnakMap;
	'snaks-order': string[];
}

export default Reference;
