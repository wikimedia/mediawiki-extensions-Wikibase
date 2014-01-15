# -*- encoding : utf-8 -*-
# Wikidata item tests
#
# License:: GNU GPL v2+
#
# steps for the item deletion

When(/^I click the item delete button$/) do
  on(DeleteItemPage).delete_item(@item_under_test["url"])
end

Then(/^Page should be deleted$/) do
  on(ItemPage) do |page|
    page.navigate_to_entity @item_under_test["url"]
	page.entity_label_span?.should be_false
	page.entity_description_span?.should be_false
  end
end
