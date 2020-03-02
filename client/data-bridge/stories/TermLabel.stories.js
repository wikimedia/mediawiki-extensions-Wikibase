import { storiesOf } from '@storybook/vue';
import TermLabel from '@/presentation/components/TermLabel';

storiesOf( 'TermLabel', module )
	.addParameters( { component: TermLabel } )
	.add( 'English term in English paragraph', () => ( {
		data: () => ( {
			term: {
				language: 'en',
				value: 'example (exemplary)',
			},
		} ),
		components: { TermLabel },
		template:
			`<p lang="en" dir="ltr">
				This is an example paragraph
				mentioning the
				<TermLabel :term="term"/>
				property.
			</p>`,
	} ) )
	.add( 'Hebrew term in English paragraph', () => ( {
		data: () => ( {
			term: {
				language: 'he',
				value: 'דֻּגְמָה',
			},
		} ),
		components: { TermLabel },
		template:
			`<p lang="en" dir="ltr">
				This example paragraph uses the
				<TermLabel :term="term"/> property,
				whose label I got from English Wiktionary.
			</p>`,
	} ) )
	.add( 'English term in Arabic paragraph', () => ( {
		data: () => ( {
			term: {
				language: 'en',
				value: 'example (exemplary)',
			},
		} ),
		components: { TermLabel },
		template:
			`<p lang="ar" dir="rtl">
				قمت بترجمة نص <TermLabel :term="term"/>
				باستخدام ترجمة جوجل
				ونعتذر عن أي أخطاء.
			</p>`,
	} ) )
	.add( 'Persian name next to neutral characters', () => ( {
		data: () => ( {
			term: {
				language: 'fa',
				value: 'محمد بن موسی خوارزمی',
			},
		} ),
		components: { TermLabel },
		template:
			`<dl lang="en" dir="ltr">
				<dt>With <code>&lt;TermLabel&gt;</code></dt>
				<dd>
					<TermLabel :term="term"/> (780 – 850)
					was a Persian scholar.
				</dd>
				<dt>Without <code>&lt;TermLabel&gt;</code></dt>
				<dd>
					{{ term.value }} (780 – 850)
					was a Persian scholar.
				</dd>
			</dl>`,
	} ) );
