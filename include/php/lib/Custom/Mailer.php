<?php 
namespace Custom;
class Mailer extends \AtrioTeam\MailBuilder\MailBuilder {


    protected $style = '
        <style type="text/css">
            /*<!--*/
                @import url(\'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap\');

                body 
                { 
                    line-height: 2;
                    margin: 0;
                    padding: 0;
                    font-family: \'Open Sans\',\'Arial\',\'Helvetica\',sans-serif;
                    font-size: 13px;
                    color: #141414;
                    background: #F0F0F0;
                }

                h1,h2,h3,h4,h5,h6 
                {
                    font-size: 13px;
                    color: #141414;
                }

                a { color: #141414; text-decoration: underline; font-weight: bold; }
                strong { font-weight: bold; }

                .reset {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }

                abbr { text-decoration: none; }

                #wrapper 
                {
                    padding: 45px 10px;
                    text-align: center;
                    background: #F0F0F0;
                }

                    #wrapper_center {
                        max-width: 600px;
                        margin: 0 auto;
                    }

                    h1 { line-height: 30px; margin: 0 0 35px 0; font-size: 15px;  }

                    .section {
                        padding: 25px 25px;
                        margin-bottom: 30px;
                        text-align: left;
                        background: #FFF;
                    }

                    .section_no_margin {margin-bottom: 0;}


                        .section_title {
                            padding: 0;
                            margin: 0 0 20px 0;
                            font-size: 13px;
                            font-weight: normal;
                            text-transform: uppercase;
                            border-bottom: 1px solid #DDDDDD;
                        }

                        .section_content {
                            padding-top: 20px;
                            margin-top: -9px;
                            margin-bottom: 40px;
                        }

                        .btn {
                            height: 56px;
                            padding: 18px 30px 17px 30px;
                            box-sizing: border-box;
                            display: block;
                            position: relative;
                            text-align: center;
                            text-decoration: none;
                            white-space: nowrap;
                            letter-spacing: .1154em;
                            text-transform: uppercase;
                            color: #FFFFFF;
                            background-color: #003762;
                            background-image: -webkit-gradient(left top, right top, color-stop(0%, #003762), color-stop(100%, #235A85));
                            background-image: -webkit-linear-gradient(140.26deg, #003762 0%, #235A85 100%);
                            background-image: -moz-linear-gradient(140.26deg, #003762 0%, #235A85 100%);
                            background-image: -ms-linear-gradient(140.26deg, #003762 0%, #235A85 100%);
                            background-image: -o-linear-gradient(140.26deg, #003762 0%, #235A85 100%);
                            background-image: linear-gradient(140.26deg, #003762 0%, #235A85 100%);
                        }

                    .section_block {padding: 25px 0; margin-bottom: 50px; background: #FFF;}

                #footer { 
                    max-width: 400px;
                    padding-top: 34px;
                    margin: 0 auto;
                    line-height: 1.2;
                    font-size: 12px;
                    color: #666666;
                }

                    #footer a {
                        font-weight: normal;
                        text-decoration: none;
                        color: #666666;
                    }

                .table_data { 
                    width: 100%; 
                    border-spacing: 0; 
                    border-collapse: collapse;
                }
                    .table_data th { padding: 10px; vertical-align: top; text-align: left; text-transform: uppercase; }
                    .table_data td { padding: 10px; border-bottom: 1px solid #DDDDDD; vertical-align: top; text-align: left; }
                        .table_data td.right { text-align: right; }

                    .table_data td.image {padding: 0;}


                @media only screen and (min-width: 500px){                                                
                    #wrapper { 
                        padding-right: 25px !important; 
                        padding-left: 25px !important; 
                    }
                }

                @media only screen and (min-width: 600px){
                    .section {
                        padding-right: 60px !important;
                        padding-left: 60px !important;
                    }
                }
            /*-->*/
            </style>
            <!--[if mso]>
            <style type="text/css">
                body,table,td {font-family: \'Arial\',\'Helvetica\',sans-serif;}
            </style>
            <![endif]-->
            ';

    /**
    -----------------------------------------------------------------------
    COSTRUTTORE
    imposto di valori del form da sessione se presenti (valori, errori)
    -----------------------------------------------------------------------
    @params
    $Lang       : istanza di LangManager per gestione lingue
    -----------------------------------------------------------------------
    */
    public function __construct($Lang)
    {
        parent::__construct($Lang);

    }



    /**
    -----------------------------------------------------------------------
    getMailHead()
    Ritorna l'intestazione della mail
    -----------------------------------------------------------------------
    */
    public function getMailHead(){

        if($this->mailHead === null):
            $this->mailHead = '
                                <!DOCTYPE html>
                                <html lang="'.$this->Lang->lgSuff.'">
                                    <head>
                                        <meta charset="utf-8">
                                        <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=yes,minimum-scale=.5,maximum-scale=3">
                                        <title>'.$this->arMailInfo['SITE_NAME'].'</title>
                                    </head>
                                    <body>
                                        '.$this->style.'
                                        <div id="wrapper">
                                            <div id="wrapper_center">
                                                <h1><img src="'.$this->serverName.'/assets/imgs/layout/logo_mail.png" alt="'.$this->arMailInfo['SITE_NAME'].'" style="width: 150px; max-width: 100%;"></h1>'.PHP_EOL;
        endif;
        return $this->mailHead;

    }


    /**
    -----------------------------------------------------------------------
    setMailHead()
    Imposta l'intestazione della mail
    -----------------------------------------------------------------------
    */
    public function setMailHead($str = ''){
        $this->mailHead = $str;
    }


    /**
    -----------------------------------------------------------------------
    getMailFoot()
    Ritorna il piè pagina della mail della mail
    -----------------------------------------------------------------------
    */
    public function getMailFoot(){

        if($this->mailFoot === null):
            $this->mailFoot = '             </div> <!-- #wrapper_center -->
                                            <div id="footer">
                                                &copy; '.$this->arMailInfo['SITE_NAME'].'  <abbr title="'.$this->Lang->returnT('label_piva').'" style="text-transform: uppercase;">'.$this->Lang->returnT('label_piva_abbr').'</abbr> '.$this->arMailInfo['PIVA'].' <br>
                                                <a target="_blank" href="'.$this->Lang->returnL('terms').'">'.$this->Lang->returnT('terms').'</a> - <a target="_blank" href="'.$this->Lang->returnL('privacy').'">'.$this->Lang->returnT('label_privacy').'</a>
                                            </div>
                                        </div> <!-- #wrapper -->
                                    </body>
                                </html>'.PHP_EOL;
        endif;
        return $this->mailFoot;
    }


    /**
    -----------------------------------------------------------------------
    setMailFoot()
    Imposta il piè pagina della mail della mail
    -----------------------------------------------------------------------
    */
    public function setMailFoot($str = ''){
        $this->mailFoot = $str;
    }


    /**
    *   getMailBody()
    *   ritorna il corpo della mail attualmente impostato
    *   @param void
    *   @return string $mailBody : il corpo della mail attualmente impostato
    */
    public function getMailBody(){
        if($this->mailBody === null):
            $this->mailBody = ' 
            
            <div class="textbody">
                <h3 class="section_title">Lorem ipsum</h3>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Expedita veritatis quibusdam atque. Omnis quibusdam cupiditate dignissimos eius, sequi cum ipsum cumque a, sed quasi fuga alias sunt consectetur itaque optio ut, nobis ullam reiciendis fugiat sapiente ex unde veniam eveniet officia? Perferendis odio sed voluptatum eos ad magnam minima laborum? Aspernatur recusandae a delectus modi, cumque sint odit sunt reiciendis temporibus, quidem id ipsum repudiandae quos ullam officiis assumenda ad accusamus fugiat, doloribus quis tenetur quod magnam laboriosam! Iste, voluptate neque reiciendis quia repellendus exercitationem consequatur quis iure laudantium necessitatibus alias accusantium nam possimus expedita, ad commodi autem labore quisquam.</p>
                <p>Totam dolore odit expedita nihil, deserunt accusamus ab eius repellat laboriosam minus, harum nostrum inventore! Totam odio impedit minima, sapiente ex. Iure perferendis doloremque quaerat voluptatem adipisci, dicta totam ut omnis assumenda facere iusto. Omnis earum sapiente illo deserunt consequatur, repellat similique fuga officiis accusantium magnam ullam quaerat cum unde culpa porro vitae hic pariatur. Voluptas eos dignissimos porro iusto nulla repudiandae, earum ipsam animi ex beatae error expedita, ratione eius omnis. Impedit nihil aspernatur, minima minus. Autem provident corporis minima cum culpa aliquam, veniam quas voluptate voluptates, laboriosam, nemo officiis quisquam ad error maiores quasi harum nihil doloribus ea!</p>
                <p>Ea facere sit tempore cupiditate. Suscipit esse iure, nobis nostrum beatae tempora? Impedit, possimus iure ipsum numquam ratione, id odio. Asperiores voluptates mollitia, dignissimos culpa nam sunt odit voluptate facilis neque reiciendis quos quo pariatur perspiciatis veniam. Porro vel, ipsum perferendis delectus doloribus suscipit tempora impedit. Magni tenetur enim, earum vitae maxime fuga in reiciendis ipsum deleniti similique, nobis. Aperiam a accusantium similique quis placeat dignissimos voluptatum maxime eum cumque beatae. Quod cum, quo autem a consectetur adipisci iste. Porro minima quam reiciendis magnam libero, placeat officiis neque! Aspernatur asperiores quos accusamus, sapiente aliquam, adipisci rem ea temporibus modi saepe.</p>
                <p>Aut, impedit eum nobis? Vel laudantium odit provident expedita labore nesciunt tempora, iure perspiciatis voluptatibus! Illo atque placeat deleniti autem possimus earum porro nobis recusandae quos ipsum fugit harum quo, pariatur corporis ut, natus dolor. Labore, sed, accusantium distinctio, accusamus earum corrupti, dolor commodi cum eum placeat numquam praesentium! Sit voluptatibus est alias reprehenderit sequi sunt laudantium, eius optio dolorum perferendis vitae ad architecto facere, ratione nostrum obcaecati officia magnam placeat laborum necessitatibus facilis provident? Illo sequi consequuntur ipsum qui excepturi in quam amet pariatur, accusamus iste blanditiis modi perspiciatis assumenda deserunt, rem id. Qui at iure nihil, excepturi dolore.</p>
                <p>Tenetur voluptatem voluptatum natus ea veritatis sed itaque et repellat rem dolore eius nam quos aliquid voluptates neque consequuntur facilis, eos nostrum quibusdam officiis! Debitis non, consequatur ducimus rem. Culpa minima cumque, expedita, repellendus quam ratione asperiores totam dicta aliquid porro error eaque. Odio animi veniam eveniet culpa totam dignissimos est libero autem ipsam distinctio sit hic harum asperiores aspernatur excepturi accusamus dolores iure, amet doloribus eligendi quasi ipsum commodi. Earum autem doloremque, perspiciatis quos rerum eum, commodi quidem debitis nulla alias sequi voluptas vel, harum adipisci. Quos delectus, nostrum suscipit velit. Optio asperiores, veniam magni quos in repudiandae, consequuntur.</p>
                <p>Sed vitae mollitia nesciunt odit tempore esse, nisi facere dignissimos error ipsam quibusdam soluta illum doloremque deleniti cum totam eos maxime a dolorum quaerat eum. Quae dignissimos rem laudantium, illo earum, pariatur culpa. Obcaecati non libero commodi repudiandae sapiente aliquam nostrum, quod itaque! Sequi debitis, totam voluptatum quam id, velit voluptas veritatis amet, esse delectus voluptate sed, harum beatae hic autem nihil repudiandae natus dolores aperiam veniam! Ea porro dolores quisquam molestias inventore quis aut explicabo animi nulla debitis, quibusdam officia illum dicta consequatur assumenda. Quo distinctio, hic similique, quas nobis, repudiandae quae quis, deserunt minus libero consequatur minima tempore.</p>
                <p>Consequuntur, reprehenderit! Ducimus in modi, quibusdam excepturi cum delectus et harum iure accusantium nulla quae, voluptas illo ea porro perferendis vel molestiae, soluta, adipisci numquam fuga praesentium accusamus! Consequatur totam deleniti voluptatibus cupiditate inventore sed amet, vitae ipsum nostrum porro ad optio modi incidunt sunt, nisi quos. Molestias eveniet necessitatibus neque dolores omnis sint eius quisquam ipsum, hic similique amet accusamus rem earum fugiat soluta ad a, dolorem. Dignissimos ad omnis sed animi. Deleniti perferendis beatae eaque necessitatibus, temporibus quos debitis voluptatibus! Dolor omnis accusantium sapiente fugiat iste, rerum id, consequuntur, corporis dicta inventore tenetur maxime iusto, fuga cumque ducimus.</p>
            </div>
            <div class="textbody">
                <p>Incidunt nulla voluptate nesciunt illo impedit commodi, deserunt officia praesentium placeat, hic cupiditate distinctio doloribus cum et dignissimos neque nemo obcaecati optio quidem possimus, animi nam consequuntur! Consequuntur facere et maiores accusamus molestias possimus quos tenetur saepe nostrum, ipsum ab reiciendis dolores sequi eum porro minus maxime, animi, minima placeat? Voluptates suscipit eveniet reiciendis reprehenderit saepe quae, adipisci voluptatum, tempora labore aliquam veniam deserunt est. Obcaecati, adipisci, nisi. Minima ipsum commodi sunt voluptatibus vero. Officiis asperiores, tempore alias ipsa saepe quis totam dolore numquam voluptatum nostrum facilis, ad maxime quos! Minima aspernatur autem obcaecati similique nam molestiae officiis rem esse!</p>
                <p>Doloribus dolorem ullam, inventore fugiat earum, vel similique itaque perferendis. Quaerat iure sit, nesciunt eligendi qui, asperiores, obcaecati esse architecto fuga ratione inventore alias excepturi eos consectetur dolore ipsam tenetur minima at explicabo tempora quidem cupiditate odio deleniti. Unde, ducimus quas dolores, aliquid repudiandae dolorum eligendi exercitationem, voluptatum consequatur quis rerum, natus eveniet accusantium modi harum deleniti id! Suscipit rem quas harum ea sequi perspiciatis voluptatem cupiditate facilis, ad quae culpa repudiandae earum consequatur delectus autem eaque mollitia nostrum saepe asperiores hic libero, necessitatibus repellendus itaque minus. Possimus dolor eum rerum minima aliquid quisquam, esse voluptatibus, repellendus cumque! Enim, odio.</p>
                <p>Accusantium optio illum fuga sapiente a consectetur officiis nam ratione. Et quod quas, quisquam similique eveniet adipisci cupiditate perspiciatis, assumenda, quaerat nesciunt facere optio aperiam earum sequi animi maiores, qui nihil neque corrupti quibusdam ratione odio. Cumque officia suscipit nostrum provident architecto aut quaerat porro minus facilis quasi eum, iure consequuntur dolor sunt tempora, facere cupiditate possimus eveniet magnam voluptatem magni ipsa error numquam illo? Inventore in aliquam earum, recusandae accusamus neque pariatur, dolor laborum facere officiis odit, magni aspernatur illum eos eaque magnam. Corrupti voluptates unde velit doloremque nihil dicta! Id labore facere sint accusantium doloribus odit sunt sapiente!</p>
            </div>'.PHP_EOL;
        endif;
        return $this->mailBody;
    }


}

