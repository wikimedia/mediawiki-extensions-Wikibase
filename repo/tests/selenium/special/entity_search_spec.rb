# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for entity search using the entity selector widget

require 'spec_helper'

num_items = 8
label_common_prefix = generate_random_string(4)
alias_a = generate_random_string(8)
alias_b = generate_random_string(8)

# items
count = 0
items = Array.new
while count < num_items do
  items.push({"label"=>label_common_prefix + generate_random_string(10), "description"=>generate_random_string(20)})
  count = count + 1
end

describe "Check entityselector search" do
  before :all do
    # set up: create items
    items.each do |item|
      visit_page(CreateItemPage) do |page|
        item['id'] = page.create_new_item(item['label'], item['description'])
        item['url'] = page.current_url
      end
    end
  end

  context "Check common behavior" do
    it "should check for autocomplete in searchbox" do
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput?.should be_true
        page.entitySelectorSearchInput = items[0]["label"][0..7]
        ajax_wait
        page.wait_for_suggestions_list
        page.entitySelectorSearchInput.should == items[0]["label"]
      end
    end
    it "should check for multiple suggestions" do
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput?.should be_true
        page.entitySelectorSearchInput = label_common_prefix
        ajax_wait
        page.wait_for_suggestions_list
        page.count_search_results.should == 9 # 7 suggestions, "more" & "containing.."
      end
    end
  end

  context "Check suggestions" do
    it "should check for empty suggestions" do
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput = "foo" # non existent item
        ajax_wait
        page.wait_for_suggestions_list
        page.entitySelectorSearch?.should be_true
        page.count_search_results.should == 1 # just the "containing" element
        page.get_search_results[0].text.include?("containing...").should be_true
        page.get_search_results[0].text.include?("foo").should be_true
      end
    end
    it "should check for suggestion based on label" do
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput = items[0]["label"] # existent item
        ajax_wait
        page.wait_for_suggestions_list
        page.entitySelectorSearch?.should be_true
        page.count_search_results.should == 2 # 1 suggestion & the "containing" element
        page.get_search_results[0].text.include?(items[0]["label"]).should be_true
        page.get_search_results[0].text.include?(items[0]["description"]).should be_true
        page.get_search_results[1].text.include?("containing...").should be_true
        page.get_search_results[1].text.include?(items[0]["label"]).should be_true

        page.entitySelectorSearchInput_element.clear
        page.entitySelectorSearchInput = items[0]["label"][0..7] # label fragment of existent item
        ajax_wait
        page.wait_for_suggestions_list
        page.entitySelectorSearch?.should be_true
        page.count_search_results.should == 2 # 1 suggestion & the "containing" element
        page.get_search_results[0].text.include?(items[0]["label"]).should be_true
        page.get_search_results[0].text.include?(items[0]["description"]).should be_true
        page.get_search_results[1].text.include?("containing...").should be_true
        page.get_search_results[1].text.include?(items[0]["label"][0..7]).should be_true
      end
    end
    it "should check for suggestion based on alias" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.add_aliases([alias_a, alias_b])
      end
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput = alias_a # alias of an existing item
        ajax_wait
        page.wait_for_suggestions_list
        page.entitySelectorSearch?.should be_true
        page.count_search_results.should == 2 # 1 suggestion & the "containing" element
        page.get_search_results[0].text.include?(items[0]["label"]).should be_true
        page.get_search_results[0].text.include?(items[0]["description"]).should be_true
        page.get_search_results[0].text.include?("Also known as: " + alias_a).should be_true
        page.get_search_results[1].text.include?("containing...").should be_true
        page.get_search_results[1].text.include?(alias_a).should be_true

        page.entitySelectorSearchInput_element.clear
        page.entitySelectorSearchInput = alias_a[0..6] # alias-fragment of an existing item
        ajax_wait
        page.wait_for_suggestions_list
        page.entitySelectorSearch?.should be_true
        page.count_search_results.should == 2 # 1 suggestion & the "containing" element
        page.get_search_results[0].text.include?(items[0]["label"]).should be_true
        page.get_search_results[0].text.include?(items[0]["description"]).should be_true
        page.get_search_results[0].text.include?("Also known as: " + alias_a).should be_true
        page.get_search_results[1].text.include?("containing...").should be_true
        page.get_search_results[1].text.include?(alias_a[0..6]).should be_true
      end
    end
    it "should trigger regular search for nonexistent item" do
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput = "foo" # non existent item
        ajax_wait
        page.wait_for_suggestions_list
        page.get_search_results[0].click
      end
      on_page(SearchPage) do |page|
        page.searchResultDiv?.should be_true
        page.searchResults?.should be_false
        page.noResults?.should be_true
      end
    end
    it "should trigger regular search for existing item" do
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput = items[0]["label"] # existent item
        ajax_wait
        page.wait_for_suggestions_list
        page.get_search_results[1].click
      end
      on_page(SearchPage) do |page|
        page.searchResultDiv?.should be_true
        page.searchResults?.should be_true
        page.noResults?.should be_false
        page.count_search_results.should == 1
        page.firstResultLabelSpan_element.text.should == items[0]["label"]
      end
    end
    it "should click on suggested item" do
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput = items[0]["label"] # existent item
        ajax_wait
        page.wait_for_suggestions_list
        page.get_search_results[0].click
        page.wait_for_entity_to_load
        page.entityLabelSpan?.should be_true
        page.entityLabelSpan.should == items[0]["label"]
      end
    end
  end

  context "Check more-button" do
    it "should check existence of 'more'" do
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput = label_common_prefix
        ajax_wait
        page.wait_for_suggestions_list
        page.get_search_results[7].text.include?("more").should be_true
      end
    end
    it "should check functionality of 'more'" do
      visit_page(RepoMainPage) do |page|
        page.entitySelectorSearchInput = label_common_prefix
        ajax_wait
        page.wait_for_suggestions_list
        page.count_search_results.should == 9 # 7 suggestions, "more" & "containing.."
        page.get_search_results[7].click
        ajax_wait
        page.wait_for_suggestions_list
        page.count_search_results.should == 9 # 8 suggestions, "containing.."
        search_results = page.get_search_results
        search_results[7].text.include?("more").should be_false
        search_results[8].text.include?("more").should be_false
      end
    end
  end
end
