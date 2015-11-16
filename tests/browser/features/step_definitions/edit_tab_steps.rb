# -*- encoding : utf-8 -*-
# Wikidata item tests
#
# License:: GNU GPL v2+
#
# steps to check the edit tab functionality

Then(/^the edit\-tab button should not be visible$/) do
  expect(on(ItemPage).edit_tab?).to be false
end
