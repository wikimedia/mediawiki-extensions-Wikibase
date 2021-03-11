import TermLabel from '@/presentation/components/TermLabel';

export default {
	title: 'TermLabel',
	component: TermLabel,
};

export function EnglishTermInEnglishParagraph() {
	return {
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
	};
}

export function HebrewTermInEnglishParagraph() {
	return {
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
	};
}

export function EnglishTermInArabicParagraph() {
	return {
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
	};
}

export function persianNameNextToNeutralCharacters() {
	return {
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
	};
}
