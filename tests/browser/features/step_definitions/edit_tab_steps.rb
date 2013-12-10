Then(/^the edit\-tab button should not be visible$/) do
  on(ItemPage).editTab?.should be_false
end
