Given(/^I have at least (\d+) badges to test$/) do |num|
  @available_badges = visit(ItemPage).available_badges
  expect(@available_badges.length).to be >= num.to_i
end

When(/^I click the empty badge selector$/) do
  on(ItemPage).sitelinks_form[1].empty_badge_element.when_visible.click
end

When(/^I click the (\d). badge selector id item$/) do |num|
  on(ItemPage).badge_selector_list[@available_badges[num.to_i - 1]].selector_id_link_element.when_visible.click
end

Then(/^The (\d+)\. badge id should be attached to the sitelink$/) do |num|
  expect(on(ItemPage).badge_list[@available_badges[num.to_i - 1]].badge_element.when_visible).to be_visible
end

Then(/^Sitelink badge selector should be there$/) do
  expect(on(ItemPage).sitelinks_form[1].badge_selector_element.when_visible).to be_visible
end

Then(/^Sitelink badge selector menu should be there$/) do
  expect(on(ItemPage).badge_selector_menu_element.when_visible).to be_visible
end

Then(/^Sitelink badge selector menu should show available badges$/) do
  @available_badges.each do |badge|
    expect(on(ItemPage).badge_selector_list[badge].selector_id_link_element.when_visible).to be_visible
  end
end

Then(/^Sitelink empty badge selector should be there$/) do
  expect(on(ItemPage).sitelinks_form[1].empty_badge_element.when_visible).to be_visible
end

Then(/^Sitelink empty badge selector should not be there$/) do
  expect(on(ItemPage).sitelinks_form[1].empty_badge_element.when_not_visible).to_not be_visible
end
