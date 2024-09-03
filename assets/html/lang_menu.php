<!-- 
    MENU LINGUE
-->                
<?php
if(count($App->Lang->getPublicLanguages()) > 1):
    echo '          <ul id="lang_menu" class="reset" role="menu" data-animscroll data-as-delay="600ms" data-as-animation="fadeIn">'.PHP_EOL;
    foreach($App->Lang->getPublicLanguages() as $k => $L):

        $lang_attivo    = $App->Lang->lgId == $L['id']  ? ' class="active"' : '';
        $lang_test_mode = $L['test_mode']               ? ' style="display: none"' : '';
        $lang_nofollow   = $App->Lang->lgId == $L['id'] || $L['test_mode'] ? ' rel="nofollow"' : '';

        # CAMBIO LINGUA NELLA STESSA PAGINA
        # reindirizzo alla homepage se
        # - sono in homepage
        # - non ho un link attivo
        # - se ho un link attivo, ma non il corrispondente nella lingua
        if(isset($alternates) && isset($alternates[$L['suffix']])):
            $myLink = $alternates[$L['suffix']];
        else:
            try {
                $myLink = $App->Lang->lgAlternate($L['suffix']);
            } catch(Exception $e){
                $myLink = $k == 0 ? '/' : '/'.$L['suffix'].'/';
            }
        endif;

        echo '          <li'.$lang_attivo.'>
                            <a href="'.$myLink.'" title="'.$L['aria_label'].'" aria-label="'.$L['aria_label'].'"'.$lang_test_mode.$lang_nofollow.' data-title="'.$L['lang'].'">
                                <span>'.$L['suffix'].'</span>
                            </a>
                        </li>'.PHP_EOL;
    endforeach;
    echo '          </ul>'.PHP_EOL;
endif;
?>
