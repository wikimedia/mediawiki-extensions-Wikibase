require 'ruby_selenium'

class ArticlePage<RubySelenium
  include PageObject

  page_url self.get_url

  # language links
  div(:langLinks, :id => "p-lang")
  unordered_list(:langLinksList, :xpath => "//div[@id='p-lang']/div/ul")
  link(:germanWikiLink, :text => "Deutsch")
  span(:wikiArticleTitle, :xpath => "//h1[@id='firstHeading']/span")

  def count_language_links
    puts "counting language links"
    count = 0
    langLinksList_element.each do |listItem|
      count = count+1
      if count >= WIKI_MIN_LANGUAGE_LINKS
        return count
      end
    end
    return count
  end

end
