# -*- encoding : utf-8 -*-
# Wikidata item tests
#
# License:: GNU GPL v2+
#
# steps for the item deletion

When(/^I click the item delete button$/) do
  on(DeleteItemPage).delete_item(@item_under_test['url'])
end

Then(/^Page should be deleted$/) do
  on(ItemPage) do |page|
    page.navigate_to_entity @item_under_test['url']
    expect(page.entity_id_span?).to be false
    expect(page.entity_description_div?).to be false
  end
end
