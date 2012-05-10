require 'ruby_selenium'

class ArticlePage<RubySelenium
  include PageObject

  page_url self.get_url

  # language links
  div(:langLinks, :id => "p-lang")
  unordered_list(:langLinksList, :xpath => "//div[@id='p-lang']/div/ul")

  def count_language_links
    count = 0
    langLinksList_element.each do |listItem|
      count = count+1
    end
    return count
  end

end
