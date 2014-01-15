# -*- encoding : utf-8 -*-
# Wikidata item tests
#
# License:: GNU GPL v2+
#
# steps to check the edit tab functionality

Then(/^the edit\-tab button should not be visible$/) do
  on(ItemPage).edit_tab?.should be_false
end
