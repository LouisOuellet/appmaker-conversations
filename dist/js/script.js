API.Plugins.conversations = {
	element:{
		table:{
			index:{},
			clients:{},
		},
		count:0,
	},
	init:function(){
		API.GUI.Sidebar.Nav.add('conversations', 'main_navigation');
	},
	load:{
		index:function(){
			API.Builder.card($('#pagecontent'),{ title: 'conversations', icon: 'conversations'}, function(card){
				API.request('conversations','read',{
					data:{options:{ link_to:'conversationsIndex',plugin:'conversations',view:'index' }},
				},function(result) {
					var dataset = JSON.parse(result);
					if(dataset.success != undefined){
						for(const [key, value] of Object.entries(dataset.output.results)){ API.Helper.set(API.Contents,['data','dom','conversations',value.id],value); }
						for(const [key, value] of Object.entries(dataset.output.raw)){ API.Helper.set(API.Contents,['data','raw','conversations',value.id],value); }
						API.Builder.table(card.children('.card-body'), dataset.output.results, {
							headers:dataset.output.headers,
							id:'conversationsIndex',
							modal:true,
							key:'id',
							clickable:{ enable:true, view:'details'},
							controls:{ toolbar:true},
							import:{ key:'id', },
							load:false,
						},function(response){
							API.Plugins.conversations.element.table.index = response.table;
						});
					}
				});
			});
		},
		details:function(){
			var container = $('div[data-plugin="conversations"][data-id]').last();
			var url = new URL(window.location.href);
			var id = url.searchParams.get("id"), values = '', main = container.find('#conversations_main_card'), timeline = container.find('#conversations_timeline'),details = container.find('#conversations_details').find('table');
			if(container.parent('.modal-body').length > 0){
				var thisModal = container.parent('.modal-body').parent().parent().parent();
			}
			API.request(url.searchParams.get("p"),'get',{data:{id:id}},function(result){
				var dataset = JSON.parse(result);
				if(dataset.success != undefined){
					container.attr('data-id',dataset.output.this.raw.id);
					API.GUI.insert(dataset.output.this.dom);
					// GUI
					// Conversation
					container.find('#conversations_created').find('time').attr('datetime',dataset.output.this.raw.created.replace(/ /g, "T"));
					container.find('#conversations_created').find('time').html(dataset.output.this.raw.created);
					container.find('#conversations_created').find('time').timeago();
					main.find('textarea').summernote({
						toolbar: [
							['font', ['fontname', 'fontsize']],
							['style', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
							['color', ['color']],
							['paragraph', ['style', 'ul', 'ol', 'paragraph', 'height']],
						],
						height: 250,
					});
					container.find('#conversations_reference_form select').each(function(){
			      switch($(this).attr('name')){
			        case"reference":
			          $(this).select2({
			            theme: 'bootstrap4',
			            tags: true,
			            createTag: function (params) {
			              return {
			                id: params.term,
			                text: params.term,
			                newOption: true
			              }
			            },
			            templateResult: function (data) {
			              var $result = $("<span></span>");
			              $result.text(data.text);
			              if (data.newOption) {
			                $result.append(" <em>(new)</em>");
			              }
			              return $result;
			            }
			          });
			          $(this).on("select2:select", function (evt) {
			            var element = evt.params.data.element;
			            var $element = $(element);

			            $element.detach();
			            $(this).append($element);
			            $(this).trigger("change");
			          });
			          break;
			        default: $(this).select2({ theme: 'bootstrap4' });break;
			      }
			    });
					// Subscribe BTN
					if(API.Helper.isSet(dataset.output.details,['users','raw',API.Contents.Auth.User.id])){
						main.find('.card-header').find('button[data-action="unsubscribe"]').show();
					} else { main.find('.card-header').find('button[data-action="subscribe"]').show(); }
					// Events
					main.find('.card-header').find('button[data-action="unsubscribe"]').click(function(){
						API.request(url.searchParams.get("p"),'unsubscribe',{data:{id:dataset.output.this.raw.id}},function(answer){
							var subscription = JSON.parse(answer);
							if(subscription.success != undefined){
								main.find('.card-header').find('button[data-action="unsubscribe"]').hide();
								main.find('.card-header').find('button[data-action="subscribe"]').show();
								timeline.find('[data-type=user][data-id="'+API.Contents.Auth.User.id+'"]').remove();
							}
						});
					});
					main.find('.card-header').find('button[data-action="subscribe"]').click(function(){
						API.request(url.searchParams.get("p"),'subscribe',{data:{id:dataset.output.this.raw.id}},function(answer){
							var subscription = JSON.parse(answer);
							if(subscription.success != undefined){
								main.find('.card-header').find('button[data-action="subscribe"]').hide();
								main.find('.card-header').find('button[data-action="unsubscribe"]').show();
								var sub = {
									id:API.Contents.Auth.User.id,
									created:subscription.output.relationship.created,
									email:API.Contents.Auth.User.email,
								};
								API.Builder.Timeline.add.subscription(timeline,sub,'user','lightblue');
							}
						});
					});
					// Status
					if(API.Auth.validate('custom', 'conversations_status', 1)){
						if(API.Helper.isSet(API.Contents.Statuses,["conversations"])){
							if(API.Helper.isSet(API.Contents.Statuses,["conversations",dataset.output.this.dom.status])){
								details.find('td[data-plugin="conversations"][data-key="status"]').html('<span class="badge bg-'+API.Contents.Statuses.conversations[dataset.output.this.dom.status].color+'"><i class="'+API.Contents.Statuses.conversations[dataset.output.this.dom.status].icon+' mr-1" aria-hidden="true"></i>'+API.Contents.Language[API.Contents.Statuses.conversations[dataset.output.this.dom.status].name]+'</span>');
							}
							if(dataset.output.this.raw.status == 2){
								container.find('#conversations_details').prepend('<div class="ribbon-wrapper ribbon-xl"><div class="ribbon bg-danger text-xl">Closed</div></div>');
							}
							if(dataset.output.this.raw.status == 3){
								container.find('#conversations_details').prepend('<div class="ribbon-wrapper ribbon-xl"><div class="ribbon bg-primary text-xl">Merged</div></div>');
							}
							for(var [statusOrder, status] of Object.entries(API.Contents.Statuses.conversations)){
						    var newOption = new Option(status.name, statusOrder, false, false);
						    main.find('select[name="status"]').append(newOption).trigger('change');
							}
							main.find('select[name="status"]').val(dataset.output.this.raw.status).trigger('change');
						} else {
							details.find('td[data-plugin="conversations"][data-key="status"]').parent('tr').remove();
							main.find('select[name="status"]').remove();
						}
					} else {
						details.find('td[data-plugin="conversations"][data-key="status"]').parent('tr').remove();
						main.find('select[name="status"]').remove();
					}
					// Organizations
					if(API.Auth.validate('custom', 'conversations_organizations', 1)){
						details.find('td[data-plugin="conversations"][data-key="organizations"]').html('');
						if(API.Helper.isSet(dataset.output.details,['organizations','dom'])){
							for(var [organizationID, organization] of Object.entries(dataset.output.details.organizations.dom)){
								var found = false;
								for(var [relationID, relations] of Object.entries(dataset.output.relationships)){
									for(var [relationKey, relation] of Object.entries(relations)){
										if(relation.relationship == "organizations" && relation.link_to == organization.id){
											organization.created = relation.created;
											found = true;
											break;
										}
									}
									if(found){ break; }
								}
								API.Builder.Timeline.add.organization(timeline,organization);
								API.Plugins.conversations.GUI.add.organization(details,{dom:dataset.output.details.organizations.dom[organization.id],raw:dataset.output.details.organizations.raw[organization.id]});
							}
							if(API.Auth.validate('custom', 'conversations_organizations', 2)){
								var html = '';
								html += '<div class="btn-group m-1">';
									html += '<button type="button" class="btn btn-xs btn-success" data-action="add">';
										html += '<i class="fas fa-plus"></i>';
									html += '</button>';
								html += '</div>';
								details.find('td[data-plugin="conversations"][data-key="organizations"]').append(html);
							}
						}
					} else {
						details.find('td[data-plugin="conversations"][data-key="organizations"]').parent('tr').remove();
					}
					// Contacts
					if(API.Auth.validate('custom', 'conversations_contacts', 1)){
						details.find('td[data-plugin="conversations"][data-key="contacts"]').html('');
						main.find('#conversations_comments select').select2({ theme: 'bootstrap4' });
						for(var [contactKey, contact] of Object.entries(dataset.output.this.dom.contacts.split(';'))){
							var html = '';
							html += '<div class="btn-group m-1" data-id="'+contact+'">';
								html += '<button type="button" class="btn btn-xs btn-info" data-action="send">';
									html += '<i class="fas fa-paper-plane mr-1"></i>'+contact;
								html += '</button>';
								if(API.Auth.validate('custom', 'conversations_contacts', 4)){
									html += '<button type="button" class="btn btn-xs btn-danger" data-action="remove">';
										html += '<i class="fas fa-backspace"></i>';
									html += '</button>';
								}
							html += '</div>';
							details.find('td[data-plugin="conversations"][data-key="contacts"]').append(html);
					    var newOption = new Option(contact, contact, false, false);
					    main.find('select[name="contacts"]').append(newOption).trigger('change');
						}
						if(API.Auth.validate('custom', 'conversations_contacts', 2)){
							var html = '';
							html += '<div class="btn-group m-1">';
								html += '<button type="button" class="btn btn-xs btn-success" data-action="add">';
									html += '<i class="fas fa-plus"></i>';
								html += '</button>';
							html += '</div>';
							details.find('td[data-plugin="conversations"][data-key="contacts"]').append(html);
						}
					} else {
						details.find('td[data-plugin="conversations"][data-key="contacts"]').parent('tr').remove();
					}
					// Files
					if(API.Auth.validate('custom', 'conversations_files', 1)){
						details.find('td[data-plugin="conversations"][data-key="files"]').html('');
						if(API.Helper.isSet(dataset.output.details,['files','raw'])){
							for(var [fileKey, fileID] of Object.entries(dataset.output.this.dom.files.split(';'))){
								var file = dataset.output.details.files.raw[fileID];
								var html = '';
								html += '<div class="btn-group m-1" data-id="'+file.id+'">';
									html += '<button type="button" class="btn btn-xs btn-primary" data-action="details">';
										html += '<i class="fas fa-file mr-1"></i>'+file.name;
									html += '</button>';
									html += '<button type="button" class="btn btn-xs btn-warning" data-action="download">';
										html += '<i class="fas fa-file-download mr-1"></i>'+API.Helper.getFileSize(file.size,true,2);
									html += '</button>';
									if(API.Auth.validate('custom', 'conversations_files', 4)){
										html += '<button type="button" class="btn btn-xs btn-danger" data-action="remove">';
											html += '<i class="fas fa-trash-alt"></i>';
										html += '</button>';
									}
								html += '</div>';
								details.find('td[data-plugin="conversations"][data-key="files"]').append(html);
							}
						}
						if(API.Auth.validate('custom', 'conversations_files', 2)){
							var html = '';
							html += '<div class="btn-group m-1">';
								html += '<button type="button" class="btn btn-xs btn-success" data-action="add">';
									html += '<i class="fas fa-plus"></i>';
								html += '</button>';
							html += '</div>';
							details.find('td[data-plugin="conversations"][data-key="files"]').append(html);
						}
					} else {
						details.find('td[data-plugin="conversations"][data-key="files"]').parent('tr').remove();
					}
					// References
					if(API.Auth.validate('custom', 'conversations_references', 1)){
						details.find('td[data-plugin="conversations"][data-key="references"]').html('');
						for(var [referenceKey, referenceObj] of Object.entries(JSON.parse(dataset.output.this.dom.meta))){
							var reference = referenceObj.split(':');
							var html = '';
							html += '<div class="btn-group m-1" data-id="'+reference[1]+'" data-type="'+reference[0]+'">';
								html += '<button type="button" class="btn btn-xs btn-info" data-action="details">';
									html += '<i class="fas fa-tag mr-1"></i>'+referenceObj;
								html += '</button>';
								if(API.Auth.validate('custom', 'conversations_references', 4)){
									html += '<button type="button" class="btn btn-xs btn-danger" data-action="untag">';
										html += '<i class="fas fa-backspace"></i>';
									html += '</button>';
								}
							html += '</div>';
							details.find('td[data-plugin="conversations"][data-key="references"]').append(html);
						}
						if(API.Auth.validate('custom', 'conversations_references', 2)){
							container.find('#conversations_reference_form').show();
						} else { container.find('#conversations_reference_form').remove(); }
					} else {
						details.find('td[data-plugin="conversations"][data-key="references"]').parent('tr').remove();
					}
					// Messages
					if(API.Auth.validate('custom', 'conversations_messages', 1)){
						if(API.Helper.isSet(dataset.output.details,['messages','dom'])){
							for(var [messageKey, message] of Object.entries(dataset.output.details.messages.dom)){
								message.contacts = [];
								if(jQuery.inArray(message.sender, message.contacts) === -1){ message.contacts.push(message.sender); }
								if(jQuery.inArray(message.from, message.contacts) === -1){ message.contacts.push(message.from); }
								for(var [index, email] of Object.entries(message.to.split(';'))){
									if(jQuery.inArray(email, message.contacts) === -1){ message.contacts.push(email); }
								}
								for(var [index, email] of Object.entries(message.cc.split(';'))){
									if(jQuery.inArray(email, message.contacts) === -1){ message.contacts.push(email); }
								}
								for(var [index, email] of Object.entries(message.bcc.split(';'))){
									if(jQuery.inArray(email, message.contacts) === -1){ message.contacts.push(email); }
								}
								message.contacts = message.contacts.filter(contact => contact);
								message.files = [];
								for(var [index, file] of Object.entries(message.attachments.split(';'))){
									message.files.push(dataset.output.details.files.raw[file])
								}
								message.files = message.files.filter(file => file);
								if(jQuery.inArray(API.Contents.Auth.User.email, message.contacts) !== -1 || API.Auth.validate('custom', 'conversations_messages_all', 1)){
									API.Builder.Timeline.add.message(timeline,message);
								}
							}
						}
					}
					// Comments
					// Notes
				}
			});
		},
	},
	GUI:{
		add:{
			organization:function(details,organization){
				var html = '', ctn = details.find('td[data-plugin="conversations"][data-key="organizations"]');
				html += '<div class="btn-group m-1" data-id="'+organization.raw.id+'">';
					html += '<button type="button" class="btn btn-xs btn-primary" data-action="details">';
						html += '<i class="fas fa-building mr-1"></i>'+organization.dom.name;
					html += '</button>';
					if(API.Auth.validate('custom', 'conversations_organizations', 4)){
						html += '<button type="button" class="btn btn-xs btn-danger" data-action="remove">';
							html += '<i class="fas fa-backspace"></i>';
						html += '</button>';
					}
				html += '</div>';
				if(ctn.find('button[data-action="add"]').length > 0){
					ctn.find('button[data-action="add"]').parent().before(html);
				} else { ctn.append(html); }
				API.Plugins.conversations.EVENTS.organization(ctn.find('.btn-group[data-id="'+organization.raw.id+'"]'),organization);
			},
		},
	},
	EVENTS:{
		organization:function(group,organization){
			group.find('button').each(function(){
				switch($(this).attr('data-action')){
					case"details":
						$(this).off().click(function(){
							API.CRUD.read.show({ key:'name',keys:organization.dom, href:"?p=organizations&v=details&id="+organization.raw.name, modal:true });
						});
						break;
					case"remove":break;
					default:break;
				}
			});
		},
	},
}

API.Plugins.conversations.init();
