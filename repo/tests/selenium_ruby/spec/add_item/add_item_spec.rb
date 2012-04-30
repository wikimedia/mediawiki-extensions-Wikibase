require 'spec_helper'

describe "Create a new item" do
  context "Check for correct itemID" do
    it "should check for correct itemID" do 
      AddItemPage.new(@browser).create_new_item("mytestitem").should be_true
    end
  end
end
