<?php
/*
*====================================================================================
*
* query modello da registrare in DataAccess
*
*====================================================================================
*/

return array(
  		'NAZIONI' => "
						SELECT 
						X.id
						,X.level
						,X.id_item_sup
						,X.sigla
						, IF (
							(NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
							,true
							,false
							) AS published 
						,XL.title
						FROM elenco_stati AS X 
						INNER JOIN elenco_stati_lang AS XL ON XL.id_item = X.id 
						WHERE 1 = 1
					"
		,'PROVINCE' => "
						SELECT 
						X.id
						,X.sigla
						, IF (
							(NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
							,true
							,false
							) AS published 
						,XL.title
						FROM elenco_province AS X 
						INNER JOIN elenco_province_lang AS XL ON XL.id_item = X.id 
						WHERE 1 = 1
					"        
		,'SPEDIZIONI' => "
						SELECT 
						X.id
						,X.code
						,X.needs_shipping
						,X.modalita_trasporto
						,XR.soglia
						,XR.prezzo
						,X.id_iva
						,XV.valore AS iva
						, IF (
							(NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
							,true
							,false
						) AS published 
						,XL.title
						,XL.descr
						FROM spedizioni AS X 
						INNER JOIN spedizioni_lang AS XL ON XL.id_item = X.id 
						INNER JOIN spedizioni_rel_cliente_cat AS XR ON XR.id_main = X.id 
						LEFT JOIN iva AS XV ON XV.id = X.id_iva
						WHERE 1 = 1
					"
		,'METODI_PAGAMENTO' => "
						SELECT 
						X.id
						,X.code
						,X.prezzo
						,X.prezzo_tipo
						,X.id_iva
						,XV.valore AS iva
						,X.ordine_min
						,X.ordine_max
						,X.instant_payment
						,X.use_extra_security_check
						,X.extra_security_check_threshold
						,IF(
							(NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
							,true
							,false
						) AS published 
						,XL.title
						,XL.descr
						FROM metodo_pagamento AS X 
						INNER JOIN metodo_pagamento_lang AS XL ON XL.id_item = X.id 
						LEFT JOIN iva AS XV ON XV.id = X.id_iva
						WHERE 1 = 1
					"
		,'CLIENTE_CAT' => "
						SELECT 
						X.id
						,X.code
						,X.default_cat
						,X.cliente_type
						,X.min_cart
						,X.min_cart_um
						, IF (
							(NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
							,true
							,false
						) AS published 
						,XL.title
						FROM cliente_cat AS X 
						INNER JOIN cliente_cat_lang AS XL ON XL.id_item = X.id 
						WHERE 1 = 1
					"
    	,'LINGUE'    => "
                        SELECT 
                        *
                        FROM lingue
                        WHERE attivo = 1 AND attivo_shop = 1 ORDER BY id ASC
                    "
		,'WARNINGS'	=> "
                        SELECT 
                        X.id
                        ,X.color_bg
                        ,X.color_text
                        ,X.file_1
                        ,IF(
                            (NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
                            ,true
                            ,false
                          ) AS published 
                         ,XL.title
                         FROM warning AS X 
                         INNER JOIN warning_lang AS XL ON XL.id_item = X.id 
                        WHERE 1 = 1
					"
		,'FAQ_CATS'	=> "
                        SELECT 
                        X.id
                        ,IF(
                            (NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
                            ,true
                            ,false
                          ) AS published 
                         ,XL.title
                         FROM faq_cat AS X 
                         INNER JOIN faq_cat_lang AS XL ON XL.id_item = X.id 
                        WHERE 1 = 1
					"
		,'FAQS'	=> "
                        SELECT 
                        X.id
                        ,X.id_cat
                        ,IF(
                            (NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
                            ,true
                            ,false
                          ) AS published 
                         ,XL.title
                         ,XL.descr
                         ,XC.position AS position_cat
                         FROM faq AS X 
                         INNER JOIN faq_lang AS XL ON XL.id_item = X.id 
                         INNER JOIN faq_cat AS XC ON XC.id = X.id_cat 
                        WHERE 1 = 1
					"
        ,'CALENDARS' => "
                        SELECT 
                        X.id
                        ,X.file_1
                        ,IF(
                            (NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
                            ,true
                            ,false
                          ) AS published 
                         ,XL.title
                         ,XL.file_1_text
                         FROM calendar AS X 
                         INNER JOIN calendar_lang AS XL ON XL.id_item = X.id 
                        WHERE 1 = 1
                    "
        ,'PARKMAP_ITEM_CATS' => "
                        SELECT 
                        X.id
                        ,X.color
                        ,IF(
                            (NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
                            ,true
                            ,false
                          ) AS published 
                         ,XL.title
                         FROM parkmap_item_cat AS X 
                         INNER JOIN parkmap_item_cat_lang AS XL ON XL.id_item = X.id 
                        WHERE 1 = 1
                    "
        ,'PARKMAP_ITEMS' => "
                        SELECT 
                        X.id
                        ,X.id_cat
                        ,X.number
                        ,X.img_1
                        ,X.position
                        ,X.x
                        ,X.y
                        ,IF(
                            (NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
                            ,true
                            ,false
                          ) AS published 
                         ,XL.title
                         ,XL.descr
                         ,XL.url_page
                         ,XL.title_page
                         ,XL.description_page
                         ,XL.keywords_page
                         ,XC.color AS color_cat
                         ,XCL.title AS title_cat
                         FROM parkmap_item AS X 
                         INNER JOIN parkmap_item_lang AS XL ON XL.id_item = X.id 
                         INNER JOIN parkmap_item_cat AS XC ON X.id_cat = XC.id 
                         INNER JOIN parkmap_item_cat_lang AS XCL ON X.id_cat = XCL.id_item AND XL.lang = XCL.lang 
                        WHERE 1 = 1
                    "
        ,'VIDEOS' => "
                        SELECT 
                        X.id
                        ,X.img_1
                        ,X.link
                        ,IF(
                            (NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
                            ,true
                            ,false
                          ) AS published 
                         ,XL.title
                         FROM video AS X 
                         INNER JOIN video_lang AS XL ON XL.id_item = X.id 
                        WHERE 1 = 1
                    "
        ,'PROD_CATS' => "
                        SELECT 
                        X.id
                        ,IF(
                            (NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
                            ,true
                            ,false
                          ) AS published 
                         ,XL.title
                         FROM prod_cat AS X 
                         INNER JOIN prod_cat_lang AS XL ON XL.id_item = X.id 
                        WHERE 1 = 1
                    "
        ,'PRODOTTI' => "
                        SELECT 
                        X.id
                        ,X.id_cat
                        ,X.price
                        ,X.img_1
                        ,IF(X.vegetarian = 'Y',1,0) AS is_vegan
                        ,IF(X.glutenfree = 'Y',1,0) AS is_glutenfree
                        ,X.position
                        ,IF(
                            (NOW() BETWEEN X.date_start AND X.date_end) AND X.public = 'Y' AND X.date_insert IS NOT NULL
                            ,true
                            ,false
                          ) AS published 
                         ,XL.title
                         ,XL.subtitle
                         ,XL.descr
                         ,XL.url_page
                         ,XL.title_page
                         ,XL.description_page
                         ,XL.keywords_page
                         ,XCL.title AS title_cat
                         FROM prodotto AS X 
                         INNER JOIN prodotto_lang AS XL ON XL.id_item = X.id 
                         INNER JOIN prod_cat_lang AS XCL ON X.id_cat = XCL.id_item AND XL.lang = XCL.lang 
                        WHERE 1 = 1
                    ");
