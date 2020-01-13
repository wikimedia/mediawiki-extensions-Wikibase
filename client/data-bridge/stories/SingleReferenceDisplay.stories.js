import { storiesOf } from '@storybook/vue';
import SingleReferenceDisplay from '@/presentation/components/SingleReferenceDisplay.vue';

storiesOf( 'SingleReferenceDisplay', module )
	.addParameters( { component: SingleReferenceDisplay } )
	.add( 'item - extId - time reference', () => ( {
		data() {
			return {
				sampleReference: {
					snaks: {
						P248: [ {
							snaktype: 'value',
							property: 'P248',
							datavalue: {
								value: {
									'entity-type': 'item',
									'numeric-id': 54919,
									id: 'Q54919',
								},
								type: 'wikibase-entityid',
							},
							datatype: 'wikibase-item',
						} ],
						P214: [ {
							snaktype: 'value',
							property: 'P214',
							datavalue: {
								value: '113230702',
								type: 'string',
							},
							datatype: 'external-id',
						} ],
						P813: [ {
							snaktype: 'value',
							property: 'P813',
							datavalue: {
								value: {
									time: '+2013-12-07T00:00:00Z',
									timezone: 0,
									before: 0,
									after: 0,
									precision: 11,
									calendarmodel: 'http://www.wikidata.org/entity/Q1985727',
								},
								type: 'time',
							},
							datatype: 'time',
						} ],
					},
					'snaks-order': [
						'P248',
						'P214',
						'P813',
					],
				},
			};
		},
		components: { SingleReferenceDisplay },
		template:
			`<div>
				<SingleReferenceDisplay :reference="sampleReference" />
			</div>`,
	} ) )
	.add( 'can use different separator reference', () => ( {
		data() {
			return {
				sampleSeparator: ', ',
				sampleReference: {
					snaks: {
						P248: [ {
							snaktype: 'value',
							property: 'P248',
							datavalue: {
								value: 'first',
								type: 'string',
							},
							datatype: 'string',
						} ],
						P214: [ {
							snaktype: 'value',
							property: 'P214',
							datavalue: {
								value: 'second',
								type: 'string',
							},
							datatype: 'external-id',
						} ],
					},
					'snaks-order': [
						'P248',
						'P214',
					],
				},
			};
		},
		components: { SingleReferenceDisplay },
		template:
		`<div>
				<SingleReferenceDisplay :reference="sampleReference" :separator="sampleSeparator" />
			</div>`,
	} ) )
	.add( 'item only reference', () => ( {
		data() {
			return {
				sampleReference: {
					snaks: {
						P248: [ {
							snaktype: 'value',
							property: 'P248',
							datavalue: {
								value: {
									'entity-type': 'item',
									'numeric-id': 54919,
									id: 'Q54919',
								},
								type: 'wikibase-entityid',
							},
							datatype: 'wikibase-item',
						} ],
					},
					'snaks-order': [
						'P248',
					],
				},
			};
		},
		components: { SingleReferenceDisplay },
		template:
			`<div>
				<SingleReferenceDisplay :reference="sampleReference" />
			</div>`,
	} ) );
