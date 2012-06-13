require 'spec_helper'

describe "Check functionality of create new item" do

  context "Check for create new item" do
    it "should check for functionality of create new item" do
      initial_label = generate_random_string(10)
      initial_description = generate_random_string(20)

      visit_page(NewItemPage)
      @current_page.wait_for_item_to_load
      @current_page.labelInputField.should be_true
      @current_page.descriptionInputField.should be_true
      @current_page.create_new_item(initial_label, initial_description)
      @current_page.itemLabelSpan.should == initial_label
      @current_page.itemDescriptionSpan.should == initial_description

    end
  end

end

