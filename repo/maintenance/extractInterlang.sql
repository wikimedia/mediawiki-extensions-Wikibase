-- Extracts interlanguage links in the format used by importInterlanguage.php
select distinct page_title, ll_lang, ll_title
from page, langlinks 
where page_id = ll_from and page_namespace = 0
order by 1, 2, 3;
