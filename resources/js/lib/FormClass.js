/*
################################################################################
class.FormClass
--------------------------------------------------------------------------------
Given a jquery FORM object this class
-> validates form
-> manages ajax data posting (php page logic not implemented)
-> automatic tabindex
-> placeholder fallback
-> returns user messages
--------------------------------------------------------------------------------

!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
NON JAVASCRIPT VALIDATION AND NON JAVASCRIPT SECURITY ISSUES ARE NOT IMPLEMENTED
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

@params: javascript object
							{
								objForm				: js form object
								sendPage 			: url of the page tracking correct form send
								placeholderFallback	: manage placeholder
								clearFormOnSuccess 	: clears form after correct mail send
							}

@depends
	MESSENGER -> per le comunicazioni con l'utente viene utilizzata
	l'istanza MESSENGER della classe Messenger
--------------------------------------------------------------------------------
Updates
07/04/2016 by Jacopo Viscuso @ àtrio
- chrome and edge bug issues
- improved file upload management
	* checking basename
	* php upload responses
01/02/2016 by Jacopo Viscuso @ àtrio
- added file upload support
15/09/2016 by Jacopo Viscuso @ àtrio
- added MESSENGER option
27/12/2017 by Jacopo Viscuso @ àtrio
- improved files upload
- fixed some bugs on file upload
31/08/2018 by Jacopo Viscuso @ àtrio
- added validity control forn min/max in input type number
05/09/2018 by Jacopo Viscuso @ àtrio
- added listener to update_form event that performs automatic operations
  like tabindexing and cusomizing select forms
################################################################################
*/

var FormClass = function(params){

	var $THIS
		,o = {
			settings 			: $.extend({
										objForm 		: null			// jQuery object form
										,formName 		: null			// 
										,sendPage 		: null			// page that listens for success (useful for visit tracking)
										,validationType : 2				// validation type| 0: only frontend validation, 1: only backend validation, 2: both validations
										
										,placeholderFallback	: false	// false: no fallback; 1: normal text over inputs; 2: placeholder are used in place of placeholder attribute
										,automaticTabindex		: true	// automatically sets tabindex values
										
										,clearFormOnSuccess: true		// remove data id successful form submitted
										,tooltipDurationOnSuccess: 10000	// time in ms of tooltip duration on success

										,arFileTypes	: ['pdf'] 		// allowed filetypes
										,maxFileSize 	: 5242880 		// max filesize for files upload (5MB)
										,fileUploadScript: 'file_upload.php'	// php file that manages file upload
										,showMessageOnSuccess : true
									},params)
			,data 				: null									// data post-ed
			,objForm			: null									// the form object -> from settings.objForm
			,actionPage			: null									// data parser
			,sendPage 			: null									// page that listens for success (useful for visit tracking) -> from settings.sendPage
			,formName 			: null
			,formId				: null									// form id
			,formInputs			: null									// form inputs (html objects)
			,formData 			: null 									// data to be sent
			
			,tooltipMsg 		: ''									// which contains tooltip msg string
			,tooltipAnchor		: null									// where tooltip is attached to
			,arMsgs 			: {}

			,listenForSubmissionActive: true							// bool: if form can be submitted (unless ie file is uploading)

			,blocco 			: 0
			,loading 			: null
			,validationSupport 	: {}									// support variable to store informations about validation
			,submit_btn			: null									// verify that submit element is inside or outside form element: if outside class will listen to its click

			,t 					: null 									// timeout reference
			,callback			: null 									// callback function
			,disableLoading		: false 								// se true, disalibita manipolazione del loader


			/*
	--------------------------------------------------------------------------------
			CONSTRUCTOR
			form object verification, listeners activation
	--------------------------------------------------------------------------------
			*/
			,construct 				: function()
			{
				if(!$THIS.settings.objForm || $THIS.settings.objForm == undefined){ return false; };

				$THIS.objForm = $THIS.settings.objForm;

				if($THIS.objForm.length <= 0){
					return false;
				}
				$THIS.actionPage = $THIS.settings.objForm.attr('action');
				$THIS.formId = $THIS.objForm.attr('id');
				$THIS.formName = $THIS.settings.formName ? $THIS.settings.formName : $THIS.formId.split('_').shift();
				$THIS.sendPage = $THIS.settings.sendPage;

				if('object' == typeof window.arTrads){
					$THIS.arMsgs = window.arTrads;
				};

				if ($THIS.settings.disableLoading == true) {
					$THIS.loading = $(); // oggetto vuoto per retrocompatibilità a prima di aggiunta opzione disableLoading
				} else if(LOADING){ 
					$THIS.loading = LOADING;
				} else {
					if($('body > .loading_popup').length > 0){
						$THIS.loading = $('body > .loading_popup');
					} else {
						$THIS.loading = $('<div class="loading_popup loading"></div>').css({
		                                                            width       : '100%'
		                                                            ,height     : '100%'
		                                                            ,display    : 'none'
		                                                            ,position   : 'fixed'
		                                                            ,top        : 0
		                                                            ,left       : 0
		                                                            ,zIndex     : 1000
		                                                            //,background : '#FFFFFF url(/immagini_layout/loading.svg) center center / 50px 50px no-repeat'
		                                                            ,opacity    : .9
		                                                        }).prependTo($('body')).hide(0);						
					};
				};

				$THIS.tooltipAnchor =  $($THIS.objForm).find('fieldset') ? $($THIS.objForm).find('fieldset').last() : $($THIS.objForm);

				$THIS.managePlaceholders();
				$THIS.automaticTabindex();
				$THIS.prepareFileUpload();
				$THIS.listenForSubmission();
				$THIS.listenForTooltipRemoval();
				$THIS.customizeSelect();


				// aggiungo un listener che effettua delle operazioni all'evento update_form
				$(document).on('update_form',function(){
					$THIS.automaticTabindex();
					$THIS.customizeSelect();
				})
			}


			/*
	--------------------------------------------------------------------------------
			managePlaceholders() listenPlaceholder()
			placeholder management
	--------------------------------------------------------------------------------
			*/
			,managePlaceholders : function()
			{
				switch($THIS.settings.placeholderFallback){
					case 2:
					case 1:
						$THIS.listenPlaceholders();
						break;
				}
			}

			,listenPlaceholders	: function(){
				if($THIS.settings.placeholderFallback === false){ $THIS.settings.placeholderFallback = 2; }; // setting rewrite

				switch($THIS.settings.placeholderFallback){
					case 1: // come l'attributo placeholder: se faccio focus o ho del testo il placeholder sparisce
						$('input,textarea,select', $THIS.objForm).each(function(){
							if(this.value){ $(this).prev('.placeholder').addClass('input_has_content'); };
						});
						$('input,textarea', $THIS.objForm).on({
							blur        : function(){
												if(this.value){ $(this).prev('.placeholder').addClass('input_has_content'); }
												else { $(this).prev('.placeholder').removeClass('input_has_content'); }
											}
							,focus      : function(){ 
												$(this).prev('.placeholder').addClass('input_has_content');
											}
							,change     : function(){ 
												$(this).prev('.placeholder').addClass('input_has_content');
											}
						});
						$('select', $THIS.objForm).on({
							change      : function(){
												if(this.value){ $(this).prev('.placeholder').addClass('input_has_content'); }
												else { $(this).prev('.placeholder').removeClass('input_has_content'); }
											}
						});
						break;

					case 2: // come l'attributo placeholder: se faccio focus o ho del testo il placeholder sparisce
						$('input,textarea,select', $THIS.objForm).each(function(){
							if(this.value){ $(this).prev('.placeholder').hide(); };
						});
						$('input,textarea', $THIS.objForm).on({
							blur        : function(){
												if(this.value){ $(this).prev('.placeholder').hide(); }
												else { $(this).prev('.placeholder').show(); }
											}
							,focus      : function(){ 
												if(this.value){ $(this).prev('.placeholder').hide(); }
												else { $(this).prev('.placeholder').show(); }
											}
							,change     : function(){ 
												if(this.value){ $(this).prev('.placeholder').hide(); }
												else { $(this).prev('.placeholder').show(); }
											}
						});
						$('select', $THIS.objForm).on({
							change      : function(){
												if(this.value){ $(this).prev('.placeholder').hide(); }
												else { $(this).prev('.placeholder').show(); }
											}
						});
						break;
				}
			}


			/*
	--------------------------------------------------------------------------------
			automaticTabindex()
			automatically generated tabindexes
	--------------------------------------------------------------------------------
			*/
			,automaticTabindex	: function()
			{
				if($THIS.settings.automaticTabindex === true){
					var fp = $THIS.objForm.attr('id')+$THIS.objForm.attr('class');
					var x = 0;
					$('form').each(function(idx,el){
						if(fp == $(el).attr('id')+$(el).attr('class')){ x = idx; return; }
					});
					$('input,textarea,select,button',$THIS.objForm).each(function(idx,el){
						var tx = (idx+1)+(x*100);
						$(el).attr('tabindex',tx);
					});
				}
			}


			/*
	--------------------------------------------------------------------------------
			prepareFileUpload()
			manages in page file upload (for ajax submission)
	--------------------------------------------------------------------------------
			*/
			,prepareFileUpload 	: function(){

				$('input[type="file"]').each(function(idx,el){

					var id = $(this).attr('id');
					var $thisInput = this;

					var file_uploader = $($THIS.objForm).find('#file_uploader_'+idx);
		            if(file_uploader.length == 0){
		                file_uploader = document.createElement('iframe');
		                file_uploader.id = file_uploader.name = 'file_uploader_'+idx;
		                $(file_uploader).css({
		                    width: 0
		                    ,height: 0
		                    ,border: 0
		                    ,display: 'block'
		                    ,visibility: 'hidden'
		                });
		                $($THIS.objForm).append(file_uploader);
		            };


			        $(this).on('change',function(){
			            $THIS.loading.fadeIn();
			        	$THIS.clearErrors();
			            $THIS.listenForSubmissionActive = 'file';
			            var v = this.value;



			            if(!v.length){
			            	$thisInput.value = '';
			                $('#'+id+'_textname').text($THIS.arMsgs['label_'+$thisInput.name]); 
			                $('#'+id+'_filesize').val(''); 
			                $('#'+id+'_filename').val(''); 

			                $THIS.listenForSubmissionActive = true;
			            	$THIS.loading.fadeOut();
			                return false;                
			            };

			            $('#'+id+'_filesize').val('');
			            $('#'+id+'_filename').val(v);
                        var fileName = document.getElementById(id).files[0].name;
			            $('#'+id+'_textname').text(fileName);

			            if(this.files[0].size > $THIS.settings.maxFileSize){ $('#'+id+'_filesize').val(this.files[0].size); };

			            $($THIS.objForm).attr('action',$THIS.settings.fileUploadScript+'?fn='+$THIS.formName+'&in='+id).attr('target',file_uploader.id).trigger('submit'); //.attr('name',file_uploader.id);

			            window.addEventListener(id+'_uploaded', function (e) {
			            	if(e.detail.result != 1){ 
			            		var type = error;
			            		var msg = '<strong>'+$THIS.arMsgs.warning+'</strong><br>'+e.detail.msg;

								if('undefined' != typeof(MESSENGER)){
									MESSENGER.setClass(type);
									MESSENGER.showMessenger(msg);
								} else {
									switch(type){
										case 'success': msg = '<p class="tooltip_msg success"><a class="close" role="button">'+$THIS.arMsgs.chiudi+'</a>'+msg+'</p>'; break;
										default: msg = '<p class="tooltip_msg errors" role="alertdialog"><a class="close" role="button">'+$THIS.arMsgs.chiudi+'</a><strong>'+$THIS.arMsgs.warning+'</strong><br>'+msg+'</p>';
										
									};
						            $THIS.tooltipMsg = $(msg).appendTo($THIS.tooltipAnchor).show(300);
								};


			            		$thisInput.value = '';
				                $('#'+id+'_textname').text($THIS.arMsgs['label_'+$thisInput.name]); 
				                $('#'+id+'_filesize').val(''); 
				                $('#'+id+'_filename').val(''); 
			            	};

			                $($THIS.objForm).attr('action',$THIS.actionPage).removeAttr('target');
			                $THIS.listenForSubmissionActive = true;
			            	$THIS.loading.fadeOut();

			            },false)

			        });
				})
			}


			/*
	--------------------------------------------------------------------------------
			listenForSubmission()
			Listener for form submission
	--------------------------------------------------------------------------------
			*/
			,listenForSubmission: function()
			{
				$($THIS.objForm,document).on('submit', function(e){
					switch($THIS.listenForSubmissionActive){
						case true:
					        e.preventDefault();
							$THIS.triggerSubmission();
						case false:
				    		return false;
							break;
					}				
			    })
			}


			/*
	--------------------------------------------------------------------------------
			listenForSubmission()
			Listener for form submission
	--------------------------------------------------------------------------------
			*/
			,triggerSubmission 	: function(){
		        $THIS.listenForSubmissionActive = false;
				$($THIS).trigger('submit_processing');
		        $THIS.formData = $THIS.objForm.serialize();
		        $THIS.formInputs = $THIS.objForm[0].elements;

		        $THIS.callback = $THIS.parseData;
		        $THIS.clearErrors();
			}


			/*
	--------------------------------------------------------------------------------
			customizeSelect()
			adds a span to container label to customize select arrow
	--------------------------------------------------------------------------------
			*/
			,customizeSelect: function()
			{
				var labelSelect = $($THIS.objForm).find('.label_select');

				labelSelect.each(function(idx,el){
					var h = parseInt($(el).children('select').height(),10);
					$('<span></span>').addClass('select_cover').css('height',h).appendTo($(el))
				});				
			}


			/*
	--------------------------------------------------------------------------------
			prepareData()
			Validates and prepares data to be sent
	--------------------------------------------------------------------------------
			*/
			,parseData: function()
			{

				if($THIS.settings.validationType == 1){	// validazione solo backend
		        	$THIS.sendData();					
				} else {

					$($THIS.formInputs).each(function(idx,el){
						$THIS.validate(el);
					});

			        if($THIS.tooltipMsg.length > 0){
                       	$($THIS).trigger('submit_error');
			        	$THIS.showMessage($THIS.tooltipMsg,'error');
			        	return false;
			        } else {
			        	if($THIS.settings.validationType == 0){  // validazione solo frontend
                   			$($THIS).trigger('submit_completed',{mode: $THIS.settings.validationType});
			        	} else {
		        			$THIS.sendData();
			        	}
			        }

				}


			}


			/*
	--------------------------------------------------------------------------------
			successCallback()
			callback function triggered on form success
	--------------------------------------------------------------------------------
			*/
			,successCallback: function()
			{
            	clearTimeout($THIS.t);
                if($THIS.settings.clearFormOnSuccess === true){		// clearing form data
                	$THIS.resetForm();
                }
			}


			/*
	--------------------------------------------------------------------------------
			resetForm()
			clear form data
	--------------------------------------------------------------------------------
			*/
			,resetForm: function()
			{
                var field_type
                	,$input;
                if($THIS.formInputs){
	                for(var i = 0, l = $THIS.formInputs.length; i < l; i++){
	                	$input = $THIS.formInputs[i];
	                	field_type = 'string' == typeof $input.type ? $input.type.toLowerCase() : 'text';

	                    switch (field_type) {
	                        case "checkbox":
	                        case "radio":
	                            $input.checked = false;
	                            break;
	                        case "select":
	                            $input.selected = false;
	                            break;
	                        case "file":
	                        	$input.value = "";
	                        	if($('#'+$input.name+'_textname')){ $('#'+$input.name+'_textname').text($THIS.arMsgs.label_cv); };
	                        	if($('#'+$input.name+'_filename')){ $('#'+$input.name+'_filename').val(''); };
	                        	if($('#'+$input.name+'_filesize')){ $('#'+$input.name+'_filesize').val(''); };
	                        	break;
	                        default:
	                        	if($input.type !== 'hidden'){ $input.value = ""; }
	                        	if($THIS.settings.placeholderFallback === 1 || ($THIS.settings.placeholderFallback === 2 && $('.body').hasClass('no-placeholder'))){
	                        		$($input).prev('.placeholder').show(0)
	                        	};
	                            break;
	                    }
	                }
	            }
			}


			/*
	--------------------------------------------------------------------------------
			sendData()
			sends form data
	--------------------------------------------------------------------------------
			*/
			,sendData: function()
			{
		        $.ajax({
		            url     : $THIS.actionPage
					,data   : $THIS.formData+'&is_ajax=1'
		            ,type   : 'POST'
		            ,dataType: 'json'
		            ,beforeSend: function(){
		            	$THIS.loading.fadeIn();
		            	$($THIS.objForm).find('.tooltip_msg').hide(300,function(){
		            		$(this).remove();
		            		$THIS.tooltipMsg = null;
		            	})
		            }
		            ,success: function(r){
		                $THIS.loading.fadeOut();

                    	// rimuovo tutti gli errori
                    	$('.has_error').removeClass('has_error');

						if(r.result > 0){

                            // adWords
                            if($THIS.sendPage !== null){
		                		var iframe = document.createElement('iframe');
	                            iframe.style.width = '0px';
	                            iframe.style.height = '0px';
	                            document.body.appendChild(iframe);
	                            iframe.src = $THIS.sendPage;
	                        }

	                        if ($THIS.settings.showMessageOnSuccess) {$THIS.showMessage(r.msg,'success');}
			            	$($THIS).trigger('submit_completed', [r.msg, (r.hasOwnProperty('redirect') ? r.redirect : null)]);
                            if ($THIS.settings.tooltipDurationOnSuccess) {
                            	$THIS.t = setTimeout(function(){ 
					                            	$THIS.clearErrors();
					                            },$THIS.settings.tooltipDurationOnSuccess);
                            }

                        } else {
                        	var callback = r.hasOwnProperty('callback') ? r.callback : {};
                        	$($THIS).trigger('submit_error',{callback: callback});
                        	$(r.data).each(function(idx,el){
                        		if(el['error'] != null){ $('input[name="'+i+'"],textarea[name="'+i+'"]',document).addClass('has_error'); };
                        	});
                       		$THIS.showMessage(r.msg,'error');
                        }
                    }
                    ,error  : function(a,b,c){
                    	$($THIS).trigger('submit_error');
		                $THIS.loading.fadeOut();
                       	$THIS.showMessage($THIS.arMsgs.errore_form_insert_data,'error');
                    }
                })

			}


			/*
	--------------------------------------------------------------------------------
			showMessage()
			shows response message
	--------------------------------------------------------------------------------
			*/
			,showMessage			: function(msg,type){

				if('undefined' == typeof(msg)){ return false; };

				switch(type){
					case 'success': 
						type = 'success'; 
						var fallback_msg = '<p class="tooltip_msg success"><a class="close" role="button">'+$THIS.arMsgs.chiudi+'</a>'+msg+'</p>'; 
						break;
					default: 
						type = 'error';
						msg = '<strong>'+$THIS.arMsgs.warning+'</strong><br>'+msg;
						var fallback_msg = '<p class="tooltip_msg errors" role="alertdialog"><a class="close" role="button">'+$THIS.arMsgs.chiudi+'</a><strong>'+$THIS.arMsgs.warning+'</strong><br>'+msg+'</p>';
				};



				if('undefined' != typeof(MESSENGER)){
					MESSENGER.setClass(type);
					MESSENGER.showMessenger(msg);
				} else {
		            $THIS.tooltipMsg = $(fallback_msg).appendTo($THIS.tooltipAnchor).show(300);
				};

				if(type == 'success'){
					$THIS.callback = $THIS.successCallback; 
				};

			}


			/*
	--------------------------------------------------------------------------------
			listenForTooltipRemoval()
			listen for closing tooltip
	--------------------------------------------------------------------------------
			*/
			,listenForTooltipRemoval: function()
			{
				
				$(document).on('messenger_closed',function(){
				    $THIS.clearErrors();
				});

			    $(this.objForm,document).on('click', '.tooltip_msg .close', function(e){
			        e.preventDefault();
			        $THIS.clearErrors();
			    });

			}


			/*
	--------------------------------------------------------------------------------
			clearErrors()
			removes errors from form
	--------------------------------------------------------------------------------
			*/
			,clearErrors 			: function(){

				var callback = $THIS.callback;
				$THIS.callback = null;
				$THIS.listenForSubmissionActive = true;							
				
				$('.has_error').removeClass('has_error');

				if('undefined' != typeof(MESSENGER) && MESSENGER.msg_box.hasClass('visible')){
					MESSENGER.closeMessenger();
				};

				if($THIS.tooltipMsg.length){
					$($THIS.tooltipMsg).hide(300,function(){
		        		$(this).remove();
		        		$THIS.tooltipMsg = '';
		        		if(callback){ callback(); }
		        	})
		        } else {
		        	if(callback){ callback(); };
		        };

			}


			/*
	--------------------------------------------------------------------------------
			validate()
			parse input and generates return messages
			@params: (html object) input to be validated
	--------------------------------------------------------------------------------
			*/
			,validate 				: function($input){
				if($input.type && $input.name){
	                var input_label 	= $($input).data('input_label') ? $($input).data('input_label') : $($input).prev('span').text();	// check for alternative label
	                var input_type 		= 'string' == typeof $input.type ? $input.type.toLowerCase() : 'text';								// define input type to perform validation
	                if($($input).data('required_if')) {
						var arRequiredIf = $($input).data('required_if').split(/\_AND\_/);
						for(var k in arRequiredIf){
							var required_type 	= arRequiredIf[k].substr(0,1) == '!' ? '!' : '';
							var input_if_name 	= required_type == '!' ? arRequiredIf[k].substr(1) : arRequiredIf[k];
							if(arRequiredIf[k].length <= 0){
								console.log($($input).data('required_if')+': (chiave '+k+') '+arRequiredIf[k]+' non ha lunghezza');
							}

							var input_if 		= $THIS.formInputs[input_if_name];

							var v = '';
							switch(true){
								case (!input_if && required_type != '!'): // se non ho il campo di confronto e mi serve la sua esistenza lancio errore
									console.log('Campo '+input_if_name+' inesistente');
									break;
								case (!!input_if):
									switch(input_if.type){
										case 'checkbox':
											var v = input_if.checked == true ? 'on' : '';
											break;
										default:
											var v = input_if.value;
									};
							}

							if(required_type == '!'){
								$input.required = !v.length ? 1 : 0;
							} else {
								$input.required = v.length ? 1 : 0;
							}

						}
	                };

	                if('undefined' == typeof $THIS.validationSupport[$input.name]){
	                	$THIS.validationSupport[$input.name] = [];
	                };

	                if('undefined' != typeof Modernizr){		// check for unsupported elements
	                	var break_for = false;
	                	for(var k in Modernizr.inputtypes){
	                		if(Modernizr.inputtypes[k] === false){
	  	              			var rk = new RegExp('type="'+k+'"','g');
	  	              			if(rk.test($input.outerHTML) === true){
				                	input_type = 'string' == typeof k ? k.toLowerCase() : 'text';
	  	              				break_for = true;
	  	              			}
	                		}
	                		if(break_for === true){ break; }
	                	}
	                };

	                if($($input).data('input_compare')){		// check for input compares
	                	var input_compare = document.querySelectorAll('*[name$="'+$($input).data('input_compare')+'"]')[0];
	                	if(input_compare != undefined && input_compare.value.length > 0 && $input.value != input_compare.value) {
	                		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_fields_compare+'<br>';
	                	}
	                };

	                // imposto una validazione custom su base espressione regolare
	                if($($input).data('custom_validate')){

                    	if($input.required && !$input.value){
                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_obbligatorio+'<br>'; 
                			$($input).addClass('has_error');
                    	} else {                		
	                    	var matcher = new RegExp($($input).data('custom_validate'));
	                    	if(!matcher.test($input.value))
	                    	{
	                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_8+'<br>';
	                    		$($input).addClass('has_error');
	                    	};
                    	}

	                } else {

	                	// creo la possibilità di definire un messaggio di errore custom
	                	var errore_form_obbligatorio_msg = 'undefined' != typeof $input.dataset && 'undefined' != typeof $input.dataset.required_custom_msg && null != $input.dataset.required_custom_msg && $input.dataset.required_custom_msg ? $input.dataset.required_custom_msg : this.arMsgs.errore_form_obbligatorio;

		                switch (input_type) {
		                    case "checkbox":
		                    	if($input.required && $input.checked === false)
		                    	{
		                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>';
		                			$($input).addClass('has_error');
		                   			break;
		                    	};
		                    	
		                    	if(this.validateText($input.value) === true) 
		                    	{
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_3+'<br>';
		                    		$($input).addClass('has_error');
		                    	};
		                        break;
		                    case "radio":

		                    	if($input.required && typeof $THIS.validationSupport[$input.name]['has_value'] == 'undefined'){
			                    	$THIS.validationSupport[$input.name]['has_value'] = $THIS.objForm.find('input[name="'+$input.name+'"]:checked').size();
			                    	if($THIS.validationSupport[$input.name]['has_value'] <= 0)
			                    	{
			                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>';
			                			$($input).addClass('has_error');
			                   			break;
			                    	}
			                    };
		                    	
		                    	if(
		                    		this.validateText($THIS.objForm.find('input[name="'+$input.name+'"]:checked').val()) === true 
		                    			&& 
		                    		typeof $THIS.validationSupport[$input.name]['ok_value'] == 'undefined'
		                    	) 
		                    	{
		                    		$THIS.validationSupport[$input.name]['ok_value'] = 1;
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_3+'<br>';
		                    		$($input).addClass('has_error');
		                    	};
		                        break;
		                    case "select":
		                    	if($input.required && $input.selected === false)
		                    	{
		                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>'; 
		                			$($input).addClass('has_error');
		                   			break;
		                    	};
		                    	if(this.validateText($input.value) === true) 
		                    	{
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_3+'<br>';
		                    		$($input).addClass('has_error');
		                    	}; 
		                        break;
		                    case "tel":
		                    	if($input.required && !$input.value){
		                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>'; 
		                			$($input).addClass('has_error');
		                   			break;
		                    	};
		                    	if(this.validateTel($input.value) === true) 
		                    	{
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_5+'<br>'; 
		                    		$($input).addClass('has_error');
		                    	};
		                    	break;
		                    case "number":
		                    	if($input.required && !$input.value){
		                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>'; 
		                			$($input).addClass('has_error');
		                   			break;
		                    	};
		                    	if($input.value && (($input.min && $input.value < $input.min) || ($input.max && $input.value > $input.max))){
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_9+'<br>'; 
		                    		$($input).addClass('has_error');		                    		
		                    	}
		                    	if(this.validateTel($input.value) === true) 
		                    	{
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_1+'<br>'; 
		                    		$($input).addClass('has_error');
		                    	};
		                    	break;
		                    case "url":
		                    	if($input.required && !$input.value)
		                    	{
		                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>'; 
		                			$($input).addClass('has_error');
		                   			break;
		                    	};
		                    	if($input.value.length && this.validateUrl($input.value) === false) 
		                    	{
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_6+'<br>'; 
		                    		$($input).addClass('has_error');
		                    	};
		                    	break;
		                    case "email":
		                    	if($input.required && !$input.value)
		                    	{
		                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>'; 
		                			$($input).addClass('has_error');
		                   			break;
		                    	};
		                    	if($input.value.length && this.validateEmail($input.value) === false)
		                    	{
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_mail_0+'<br>';
		                    		$($input).addClass('has_error');
		                    	}; 
		                    	break;
		                    case "date":
		                    	if($input.required && !$input.value)
		                    	{
		                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>'; 
		                			$($input).addClass('has_error');
		                   			break;
		                    	};
		                    	var format = undefined != $($input).data('format') ? $($input).data('format') : 'dd/mm/aaaa';
		                    	if($input.value.length && this.validateDate($input.value,format) === false)
		                    	{
		                    		//var date_err_msg = this.arMsgs.errore_form_date_format.replace(/\{VAR\:format\}/,format);
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_date_format+' '+format+'<br>';
		                    		$($input).addClass('has_error');
		                    	}; 
		                    	break;
		                    case "file":
		                    	if($input.required && !$input.value)
		                    	{
		                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>'; 
		                			$($input).addClass('has_error');
		                   			break;
		                    	};
		                    	if($('#'+$input.name+'_filesize').val() > $THIS.settings.maxFileSize){
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_filesize+' '+(Math.round($THIS.settings.maxFileSize*100/1048576)/100)+' MB<br>';
		                    		$($input).addClass('has_error');
		                    		break;
		                    	};

		                    	if($input.value){	                    		
			                    	switch(this.validateFile($input.value)){
			                    		case 1:
			                    			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_7+' '+$THIS.settings.arFileTypes.join(',')+'<br>';
			                    			$($input).addClass('has_error');
			                    			break;
			                    		case 2:
			                    			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_4+'<br>';
			                    			$($input).addClass('has_error');
			                    			break;
			                    	};
		                    	}
		                    	break;
							case "hidden":
								break;
								
							case "password":
								var mode = 'string' == typeof $input.dataset.mode && $input.dataset.mode ? $input.dataset.mode : 'Ln';	// composizione della password
								var minlength = 'undefined' != typeof $input.dataset.minlength && parseInt($input.dataset.minlength,10) > 0 ? parseInt($input.dataset.minlength,10) : 8;	// lunghezza minima della password
								var maxlength = 'undefined' != typeof $input.dataset.maxlength && parseInt($input.dataset.maxlength,10) > 0 ? parseInt($input.dataset.maxlength,10) : 50;	// lunghezza massima della password
								var validation_response = this.validatePassword($input.value,minlength,maxlength,mode);
								if(true !== validation_response){
									var psw_response_msg = this.arMsgs.errore_form_tipo_9;
									switch(validation_response.code){
										case 'empty':
											psw_response_msg = this.arMsgs.errore_form_password_empty;
											break;
										case 'spaces':
											psw_response_msg = this.arMsgs.errore_form_password_s;
											break;
										case 'minlength':
										case 'maxlength':
											psw_response_msg = this.arMsgs['errore_form_password_'+validation_response.code].replace(/\{\{n\}\}/gm,validation_response.data);
											break;
										case 'mode':
											var arMode = validation_response.data.split('');
											var explain = [];
											var explain_msg = '';
											for(var i of arMode){
												switch(i){
													case 'l':
														explain_msg = this.arMsgs.errore_form_password_small_caps.replace(/\{\{n\}\}/gm,'1');
														break;
													case 'L':
														explain_msg = this.arMsgs.errore_form_password_all_caps.replace(/\{\{n\}\}/gm,'1');
														break;
													case 'n':
													case 'N':
														explain_msg = this.arMsgs.errore_form_password_numbers.replace(/\{\{n\}\}/gm,'1');
														break;
													case 'c':
														explain_msg = this.arMsgs.errore_form_password_special.replace(/\{\{list\}\}/gm,'-.!()?');
														break;
													case 'C':
														explain_msg = this.arMsgs.errore_form_password_special.replace(/\{\{list\}\}/gm,'|-_.:!()/\?*');
														break;
												};
												explain.push(explain_msg);
											};
											console.log(explain.join(', '));
											psw_response_msg = this.arMsgs['errore_form_password_mode'].replace(/\{\{explain\}\}/gm,explain.join(', '));
											break;
									}
									this.tooltipMsg += '<strong>'+input_label+'</strong>: '+psw_response_msg+'<br>';
									$($input).addClass('has_error');
								}
								break;
							default:
		                    	if($input.required && !$input.value){
		                			this.tooltipMsg += '<strong>'+input_label+'</strong>: '+errore_form_obbligatorio_msg+'<br>'; 
		                			$($input).addClass('has_error');
		                   			break;
		                    	};
		                    	if(this.validateText($input.value) === true)
		                    	{
		                    		this.tooltipMsg += '<strong>'+input_label+'</strong>: '+this.arMsgs.errore_form_tipo_3+'<br>';
		                    		$($input).addClass('has_error');
		                    	};
		                        break;
		                }
		            }
				}
			}


			/*
	--------------------------------------------------------------------------------
			validateText() 
	--------------------------------------------------------------------------------
			*/
			,validateText 			: function(val){
				return /[\<\>]/.test(val);
			}



			/*
	--------------------------------------------------------------------------------
			validateTel() 
	--------------------------------------------------------------------------------
			*/
			,validateTel 			: function(val){
				return /[^0-9\s\+\-\/]/.test(val);
			}


			/*
	--------------------------------------------------------------------------------
			validateNumber() 
	--------------------------------------------------------------------------------
			*/
			,validateNumber 		: function(val){
				return /[^0-9]/.test(val);
			}


			/*
	--------------------------------------------------------------------------------
			validateUrl() 
	--------------------------------------------------------------------------------
			*/
			,validateUrl 			: function(val){
				return /^(https?\:\/\/)?([a-zA-Z0-9\-\_]+\.)+([a-zA-Z0-9]+)$/.test(val);
			}


			/*
	--------------------------------------------------------------------------------
			validateEmail()
	--------------------------------------------------------------------------------
			*/
			,validateEmail 			: function(val){
				return /^[\w\-\_\.]+@[\w\-\_\.]+\.[a-zA-Z]{2,}$/.test(val);
			}


			/*
	--------------------------------------------------------------------------------
			validateDate()
	--------------------------------------------------------------------------------
			*/
			,validateDate 			: function(val,format){
				var format_reg_expr = format.replace(/[dma]/g,'\\d');
				var r = new RegExp(format_reg_expr,'g');
				return r.test(val);
			}


			/*
	--------------------------------------------------------------------------------
			validateFile()
			checks if extension is allowed
			@params val: (string) filename 
	--------------------------------------------------------------------------------
			*/
			,validateFile 			: function(val){
				var ext = val.split('.').pop();
				var basename = val.replace(/\\/g,'/').replace(/.*\/{1}/, '');
				if($THIS.settings.arFileTypes.indexOf(ext) < 0){ return 1; };
				if(/([\+\*\?\[\^\]\$\{\}\=\!\<\>\|\:@])/.test(basename)){ return 2; };
				return true;
			}


			/**
			 * validatePassword()
			 * valido una password sulla base di alcuni parametri
			 * NB: la logica della risposta è demandata a chi riceve la risposta alla validazione
			 */
			,validatePassword		: function(val,minlength,maxlength,mode){
				if(!val || 'string' != typeof val){	// password empty
					return {code: 'empty'};
				}
		
				if(val.length < minlength){ // shorter than min lentgh
					return {code: 'minlength',data: minlength};
				}
		
				if(val.length > maxlength){ // greater than max lentgh
					return {code: 'maxlength',data: maxlength};
				}
		
				if(/[ \s]/.test(val)){ // there is any space
					return {code: 'spaces'};
				}
		
				// pattern validation
				var mode_response = true;
		
				// se ha un carattere non contemplato reinizializzo il mode e lato server verrà inviata una mail ad admin
				if(/[^lLnNcC]/.test(mode)){
					mode = 'Ln';
				}
		
				var arMode = mode.split('');
				var where_fails = [];
				for(var i of arMode){
					switch(i){
						case 'l':
							if(!/[a-z]/gm.test(val)){
								mode_response = false;
								where_fails.push(i);
							}
							break;
						case 'L':
							if(!/[a-z]/gm.test(val)){
								mode_response = false;
								where_fails.push(i+' [a-z]');
							}
							if(!/[A-Z]/gm.test(val)){
								mode_response = false;
								where_fails.push(i+' [A-Z]');
							}
							break;
						case 'n':
						case 'N':
							if(!/[0-9]/gm.test(val)){
								mode_response = false;
								where_fails.push(i);
							}
							break;
						case 'c':
							if(!/[\-\.!\(\)\?]/gm.test(val)){
								mode_response = false;
								where_fails.push(i);
							}
							break;
						case 'C':
							if(!/[|\-_\.:!\(\)\/\\\\?\*]/gm.test(val)){
								mode_response = false;
								where_fails.push(i);
							}
							break;
						default:
							mode_response = false;
							where_fails.push(i);
					}
				}
		
				return mode_response === true ? true : {code: 'mode',data : mode };
			}
		};

	$THIS = o;
	$THIS.construct();
	return $THIS;
};
