/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	/**
	 * Condition group management
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 */
	function GroupHandler() {
		/**
		 * Container element
		 * @type {Object}
		 */
		this._$groupContainer = $('#cas-groups');

		/**
		 * Current condition group
		 * @type {Object}
		 */
		this._currentGroup = null;

		/**
		 * CSS class for current group
		 * @type {string}
		 */
		this._activeClass = 'cas-group-active';

		/**
		 * Add a group to gui
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @param  {Object}  obj
		 */
		this.add = function(obj) {
			var canSet = this.setCurrent(obj);
			if(canSet) {
				cas_alert.dismiss();
				if(!this.hasGroups()) {
					this.getGroupContainer().addClass('cas-has-groups');
				}
				$('ul', this._$groupContainer).first().append(obj);
			}		
		}

		/**
		 * Remove a group from gui
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @param  {Object}  obj
		 * @return {void}
		 */
		this.remove = function(obj) {
			var that = this;
			obj
			.css('background','#FFEBE8')
			.fadeOut('slow', function() { 
				obj.remove();
				if(!that.hasGroups()) {
					that.getGroupContainer().removeClass('cas-has-groups');
				}
			});
		}

		/**
		 * Set a group as current group
		 * Will reset former current group
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @param  {Object}  obj
		 * @return {Boolean}
		 */
		this.setCurrent = function(obj) {
			var retval = true;

			if(obj.length == 0) {
				retval =  false;
			} else if(this.getCurrent()) { 
				retval = this.resetCurrent();
			}
			if(retval) {
				this._currentGroup = obj;
				this._setActive(true);
			}
			return retval;
		}

		/**
		 * Reset current group if any
		 * Will take care of confirmation on unsaved rules
		 * and strip unsaved changes
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @return {Boolean}
		 */
		this.resetCurrent = function() {
			var retval = true;
			cas_alert.dismiss();
			if(this.getCurrent()) {

				if(this.isNewGroup()) {
					var remove = true;
					//Confirm if there are unsaved rules
					if(this.hasUnsavedRules()) {
						remove = confirm(CASAdmin.confirmCancel);
					}
					if(remove) {
						this._setActive(false);
						this.remove(this.getCurrent());
						this._currentGroup = null;
					} else {
						retval = false;
					}
					
				} else {

					var remove = true;
					//Confirm if there are unsaved rules
					if(this.hasUnsavedRules()) {
						remove = confirm(CASAdmin.confirmCancel);
					}
					if(remove) {
						//Remove new content that should not be saved
						$("li.cas-new",this.getCurrent()).remove();
						//Remove conditional headlines
						$(".cas-condition",this.getCurrent()).each( function() {
							if($(this).find('input').length == 0) {
								$(this).remove();
							}
						});
						//Show all again
						$('li').fadeIn('slow');
						this._setActive(false);
						this._currentGroup = null;					
					} else {
						retval = false;
					}

					
				}

			}
			return retval;		
		}

		/**
		 * Get current group
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @return {Object}
		 */
		this.getCurrent = function() {
			return this._currentGroup;
		}

		/**
		 * Determines if current group is a new one
		 * I.e. it is not saved to database
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @return {Boolean}
		 */
		this.isNewGroup = function() {
			return this.getCurrent().find('.cas_group_id').length == 0;
		}

		/**
		 * Determines if current group has unsaved changes
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @return {Boolean}
		 */
		this.hasUnsavedRules = function() {
			return this.getCurrent().find('li.cas-new').length > 0;
		}

		/**
		 * Determines if there are any condition groups
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @return {Boolean}
		 */
		this.hasGroups = function() {
			return $('.cas-group-single',this.getGroupContainer()).length > 0;
		}

		/**
		 * Manages CSS class for group and
		 * the ability to add and edit rules
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @param  {Boolean}  active
		 */
		this._setActive = function(active) {
			$('.js-cas-condition-add, .accordion-section-content input:checkbox').attr('disabled',!active);
			$('.accordion-container').toggleClass('accordion-disabled',!active);
			this.getCurrent().toggleClass(this._activeClass,active);
			var checkboxes = $("input:checkbox",this.getCurrent());
			checkboxes.attr('disabled',!active);
			if(active) {
				checkboxes.attr('checked',true);
			}
		}

		/**
		 * Get condition group container
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @return {Object}
		 */
		this.getGroupContainer = function() {
			return this._$groupContainer;
		}
	}

	/**
	 * Alert handler for sidebar editor
	 * @type {Object}
	 */
	var cas_alert = {

		/**
		 * Message object
		 * @type {Object}
		 */
		_$message: null,

		/**
		 * Set and print alert message
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @param  {string}  text
		 * @param  {string}  cssClass
		 */
		set: function(text,cssClass) {
			if(this._$message) {
				this._$message.remove();
			}
			this._$message = $('<div class="cas-alert"><div class="'+cssClass+'"><p>'+text+'</p></div></div>');
			this._$message
			.fadeIn('slow')
			.appendTo('body');			
		},

		/**
		 * Remove a current alert message
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 * @return {void}
		 */
		dismiss: function() {
			if(this._$message) {
				this._$message.fadeOut('slow',function() {
					$(this).remove();
				});				
			}
		}
	}

	var cas_admin = {

		groups:new GroupHandler(),
		nonce: $('#_ca-sidebar-nonce').val(),
		sidebarID: $('#current_sidebar').val(),

		init: function() {

			var new_current_group = $('.cas-group-single',this.groups.getGroupContainer()).first();
			if(new_current_group.length == 0) {
				$('.js-cas-condition-add').attr('disabled',true);
			} else {
				this.groups.setCurrent(new_current_group);
			}

			$('.cas-groups-body').on('change', 'input:checkbox', function(e) {
				var $this = $(this);
				console.log("change");
				if(!$this.is('checked')) {
					var $li = $this.closest('li');
					if($li.hasClass('cas-new')) {
						$li.remove();
					} else {
						$li.hide();
					}
				}
			});

			this.addPaginationListener();	
			this.addTabListener();
			this.addPublishListener();

			//this.addCheckboxListener();
			this.addHandleListener();
			this.addSearchListener();
			this.addNewGroupListener();
			this.addSetGroupListener();
			this.addAddContentListener();

		},

		/**
		 * Listen to publish click to remind
		 * user of unsaved changes
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 */
		addPublishListener: function() {
			$('#publish').click( function(e) {
				var canSave = cas_admin.groups.resetCurrent();
				if(!canSave) {
					e.preventDefault();
				}
			});
		},

		/**
		 * Listen to pagination for select boxes
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 */
		addPaginationListener: function() {
			$('.cas-contentlist').on('click','.page-numbers', function(e) {
				e.preventDefault();

				var link = $(this);
				var action = link.closest('.cas-rule-content');

				$.ajax({
					url: ajaxurl,
					data:link.attr('href').split('?')[1]+'&nonce='+cas_admin.nonce+'&sidebar_id='+cas_admin.sidebarID+'&action=cas-module-'+action.attr('data-cas-module'),
					dataType: 'JSON',
					type: 'POST',
					success:function(data){
						link.closest('.cas-contentlist').html(data);
					},
					error: function(xhr, desc, e) {
						console.log(xhr.responseText);
					}
				});

			});
		},

		/**
		 * Listen to and handle adding
		 * content to current group
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.0
		 */
		addAddContentListener: function() {
			$("#cas-accordion").on("click",".js-cas-condition-add", function(e) {

				e.preventDefault();

				if(cas_admin.groups.getCurrent() != null) {

					var button = $(this);

					var old_checkboxes = $("input:checkbox:checked, .cas-text-input", button.closest('.cas-rule-content'));
					var condition_elem = $('.cas-condition-'+button.attr('data-cas-condition'), cas_admin.groups.getCurrent());
					var data = [];

					if(condition_elem.length == 0) {
						condition_elem = $('<div class="cas-condition cas-condition-'+button.attr('data-cas-condition')+'"><h4>'+button.closest('.accordion-section').find('.accordion-section-title').text()+'</h4><ul></ul></div>');
						cas_admin.groups.getCurrent().find('.cas-content').append(condition_elem);
					}
					
					//Check if checkbox with value already exists
					old_checkboxes.each( function() {
						var elem = $(this);
						if(condition_elem.find("input[value='"+elem.val()+"']").length == 0) {
							if(elem.attr('type') != 'checkbox') {
								if(!elem.val()) return true;
								var temp = $('<li class="cas-new"><label><input value="'+elem.val()+'" name="'+elem.attr('name')+'" type="checkbox" checked="checked" />'+elem.val()+'</label></li>');
							} else {
								var temp = elem.closest('li').clone().addClass('cas-new');
							}
							temp.append("&nbsp;"); //add whitespace to make it look nice
							//jQuery 1.7 fix
							data.push(temp[0]);
							//temp.find('input').show();
						}
					});
					old_checkboxes.attr('checked',false);
					
					$('ul',condition_elem).append(data);					
				}
				
			});
		},

		/**
		 * Listen to and handle Add New Group clicks
		 * Uses AJAX to create a new group
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.0
		 */
		addNewGroupListener: function() {
			this.groups.getGroupContainer().on('click', '.js-cas-group-new', function(e) {

				e.preventDefault();

				var group = $('<li>', {class: 'cas-group-single cas-group-single-new', html: '<div class="cas-group-body"><span class="cas-group-control cas-group-control-active">'+
							'<input type="button" class="button js-cas-group-save" value="'+CASAdmin.save+'" /> | <a class="js-cas-group-cancel" href="#">'+CASAdmin.cancel+'</a>'+
							'</span>'+
							'<span class="cas-group-control">'+
							'<a class="js-cas-group-edit" href="#">'+CASAdmin.edit+'</a> | <a class="submitdelete trash js-cas-group-remove" href="#">'+CASAdmin.remove+'</a>'+
							'</span>'+
							'<div class="cas-content"></div></div><div class="cas-group-sep">'+CASAdmin.or+'</div>'});

				cas_admin.groups.add(group);
			});
		},

		/**
		 * Listen to and manage
		 * group saving, editing, removal and cancelling
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.0
		 */
		addSetGroupListener: function() {
			this.groups.getGroupContainer().on("click", ".js-cas-group-save", function(e){
				e.preventDefault();

				var button = $(this);
				button.attr('disabled',true);

				var data = cas_admin.groups.getCurrent().find("input").serializeArray();
				data.push({name:"action",value:"cas_add_rule"});
				data.push({name:"token",value:cas_admin.nonce});
				data.push({name:"current_id",value:cas_admin.sidebarID});

				$.ajax({
					url: ajaxurl,
					data:$.param(data),
					dataType: 'JSON',
					type: 'POST',
					success:function(data){

						cas_alert.set(data.message,'updated')

						var content = $("input:checkbox",cas_admin.groups.getCurrent()).closest('li');
						if(content.length > 0) {
							$("input:checkbox:not(:checked)",cas_admin.groups.getCurrent()).closest('li').remove();
							content.removeClass('cas-new');
						}

						$(".cas-condition",cas_admin.groups.getCurrent()).each( function() {
							if($(this).find('input').length == 0) {
								$(this).remove();
							}
						});

						if(data.new_post_id) {
							cas_admin.groups.getCurrent().append('<input type="hidden" class="cas_group_id" name="cas_group_id" value="'+data.new_post_id+'" />');
						}
						button.attr('disabled',false);
						
					},
					error: function(xhr, desc, e) {
						cas_alert.set(xhr.responseText,'error');
						button.attr('disabled',false);
					}
				});		
			})
			.on("click", ".js-cas-group-cancel", function(e){	
				e.preventDefault();
				cas_admin.groups.resetCurrent();
			})
			.on("click", ".js-cas-group-edit", function(e){
				e.preventDefault();
				cas_admin.groups.setCurrent($(this).parents('.cas-group-single'));
			})
			.on("click", ".js-cas-group-remove", function(e){
				e.preventDefault();

				if(confirm(CASAdmin.confirmRemove) == true) {

					var button = $(this);
					button.attr('disabled',true);
					var group = $(this).closest('.cas-group-single');
					$.ajax({
						url: ajaxurl,
						data:{
							action: 'cas_remove_group',
							token: cas_admin.nonce,
							cas_group_id: group.find('.cas_group_id').val(),
							current_id: cas_admin.sidebarID
						},
						dataType: 'JSON',
						type: 'POST',
						success:function(data){
							cas_alert.set(data.message,'updated');
							cas_admin.groups.remove(group);
							button.attr('disabled',false);
						},
						error: function(xhr, desc, e) {
							cas_alert.set(xhr.responseText,'error');
							button.attr('disabled',false);
						}
					});	

				}	
			});
		},

		/**
		 * Listen to and manage tab clicks
		 * Based on code from WordPress Core
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.0
		 */
		addTabListener: function() {
			var class_active = 'tabs-panel-active',
			class_inactive = 'tabs-panel-inactive';

			$("#cas-accordion .accordion-section:not(.hide-if-js)").first().addClass('open');

			$('.nav-tab-link').on('click', function(e) {
				e.preventDefault();

				panelId = $(this).data( 'type' );

				wrapper = $(this).closest('.accordion-section-content');

				// upon changing tabs, we want to uncheck all checkboxes
				$('input', wrapper).removeAttr('checked');

				//Change active tab panel
				$('.' + class_active, wrapper).removeClass(class_active).addClass(class_inactive);
				$('#' + panelId, wrapper).removeClass(class_inactive).addClass(class_active);

				$('.tabs', wrapper).removeClass('tabs');
				$(this).parent().addClass('tabs');

				// select the search bar
				$('.quick-search', wrapper).focus();
					
			});
		},
		
		/**
		 * Set tickers if at least one checkbox is checked
		 */
		addCheckboxListener: function() {
			$('.cas-rule-content :input').change( function() {
				var parent = $(this).parents('.cas-rule-content'); 
				cas_admin.toggleTick(parent);
				if($(this).attr('class') == 'cas-chk-all')
					cas_admin.toggleSelectAll(this, parent);
			}).change(); //fire change event on page load
		},
		toggleTick: function(parent) {
			//Toggle on any selected checkbox
			parent.prev().toggleClass('cas-tick',parent.find('input:checked').length > 0);
		},
		/**
		 * Toggle specific input depending on "show with all" checkbox
		 */
		toggleSelectAll: function(checkbox, parent) {
			var checkboxes = parent.find("input[type=checkbox]").not(checkbox);
			checkboxes.attr("disabled", $(checkbox).is(":checked"));
		},
		/**
		 * The value of Handle selection will control the
		 * accessibility of the host sidebar selection
		 * If Handling is manual, selection of host sidebar will be disabled
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 */
		addHandleListener: function() {
			var host = $("select[name='host']");
			var code = $('<p>Shortcode:</p><code>[ca-sidebar id='+this.sidebarID+']</code>'+
				'<p>Template Tag:</p><code>display_ca_sidebar();</code>');
			var merge_pos = $('span.merge-pos');
			host.parent().append(code);
			$("select[name='handle']").change(function(){
				var handle = $(this);
				host.attr("disabled", handle.val() == 2);
				if(handle.val() == 2) {
					host.hide();
					code.show();
				} else {
					host.show();
					code.hide();
				}
				if(handle.val() == 3) {
					merge_pos.hide();
				} else {
					merge_pos.show();
				}
			}).change(); //fire change event on page load
		},
		/**
		 * Use AJAX to search for content from a specific module
		 */
		addSearchListener: function() {
			var searchTimer;

			$('.cas-autocomplete').keypress(function(e){
				var t = $(this);

				//If Enter (13) is pressed, search immediately
				if( 13 == e.which ) {
					cas_admin.updateSearchResults( t );
					return false;
				}

				//If timer is already in progress, stop it
				if( searchTimer ) clearTimeout(searchTimer);

				searchTimer = setTimeout(function(){
					cas_admin.updateSearchResults( t );
				}, 400);
			}).attr('autocomplete','off');

		},
		/**
		 * Make AJAX request to get results
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2
		 * @param  {Object}  input
		 * @return {void}
		 */
		updateSearchResults: function(input) {
			var panel,
			minSearchLength = 2,
			q = input.val();

			if( q.length < minSearchLength ) return;

			panel = input.parents('.tabs-panel');
			var spinner = $('.spinner', panel);

			spinner.show();

			var action = input.closest('.cas-rule-content');

			$.ajax({
				url: ajaxurl,
				data:{
					'action': 'cas-module-'+action.attr('data-cas-module'),
					'nonce': cas_admin.nonce,
					'sidebar_id': cas_admin.sidebarID,
					'item_object': input.attr('data-cas-item_object'),
					'search': q
				},
				dataType: 'JSON',
				type: 'POST',
				success:function(response){
					if(response) {
						data = response;
					} else {
						data = '<li><p>'+CASAdmin.noResults+'</p></li>';
					}
					panel.find('.cas-contentlist').html(data);
					spinner.hide();
				},
				error: function(xhr, desc, e) {
					console.log(xhr.responseText);
				}
			});

		},
	}

	$(document).ready(function(){ cas_admin.init(); });

})(jQuery);
