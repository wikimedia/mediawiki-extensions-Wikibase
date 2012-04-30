
require 'spec_helper'
require 'net/http'
require 'uri'
require 'json'

describe "Check for labels" do
  context "Check for firstHeading" do
    it "should check for firstHeading" do 
      item_id = visit_page(AddItemPage).create_new_item("mytestitem")
      # puts item_id
      end
    end
    
  end      
