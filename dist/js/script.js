Engine.Plugins.conversations = {
	init:function(){
		Engine.GUI.Sidebar.Nav.add('conversations', 'main_navigation');
	},
	load:{
		index:function(){
			Engine.Builder.card($('#pagecontent'),{ title: 'conversations', icon: 'conversations'}, function(card){
				Engine.request('conversations','read',{
					data:{options:{ link_to:'conversationsIndex',plugin:'conversations',view:'index' }},
				},function(result) {
					var dataset = JSON.parse(result);
					if(dataset.success != undefined){
						for(const [key, value] of Object.entries(dataset.output.dom)){ Engine.Helper.set(Engine.Contents,['data','dom','conversations',value.id],value); }
						for(const [key, value] of Object.entries(dataset.output.raw)){ Engine.Helper.set(Engine.Contents,['data','raw','conversations',value.id],value); }
						Engine.Builder.table(card.children('.card-body'), dataset.output.dom, {
							headers:['id','status','messages','files','organizations','contacts','meta'],
							id:'conversationsIndex',
							modal:true,
							key:'id',
							clickable:{ enable:true, view:'details'},
							controls:{ toolbar:true},
							import:{ key:'id', },
							load:false,
						},function(response){});
					}
				});
			});
		},
		details:function(){
			var container = $('div[data-plugin="conversations"][data-id]').last();
			var url = new URL(window.location.href);
			var id = url.searchParams.get("id");
			Engine.request(url.searchParams.get("p"),'get',{data:{id:id,key:'id'}},function(result){
				var dataset = JSON.parse(result);
				if(dataset.success != undefined){
					container.attr('data-id',dataset.output.this.raw.id);
					// GUI
					// Adding Layout
					bgImage = '/plugins/conversations/dist/img/conversation.png';
					Engine.GUI.Layouts.details.build(dataset.output,container,{title:"Conversation Details",image:bgImage},function(data,layout){
						if(layout.main.parents().eq(2).parent('.modal-body').length > 0){
							var modal = layout.main.parents().eq(2).parent('.modal-body').parents().eq(2);
							if(Engine.Auth.validate('plugin', 'conversations', 3)){
								modal.find('.modal-header').find('.btn-group').find('[data-control="update"]').off().click(function(){
									Engine.CRUD.update.show({ container:layout.main.parents().eq(2), keys:data.this.raw });
								});
							} else {
								modal.find('.modal-header').find('.btn-group').find('[data-control="update"]').remove();
							}
						}
						// History
						Engine.GUI.Layouts.details.tab(data,layout,{icon:"fas fa-history",text:Engine.Contents.Language["History"]},function(data,layout,tab,content){
							Engine.Helper.set(Engine.Contents,['layouts','conversations',data.this.raw.id,layout.main.attr('id')],layout);
							content.addClass('p-3');
							content.append('<div class="timeline" data-plugin="conversations"></div>');
							layout.timeline = content.find('div.timeline');
							var today = new Date();
							Engine.Builder.Timeline.add.date(layout.timeline,today);
							layout.timeline.find('.time-label').first().html('<div class="btn-group"></div>');
							layout.timeline.find('.time-label').first().find('div.btn-group').append('<button class="btn btn-primary" data-trigger="all">'+Engine.Contents.Language['All']+'</button>');
							var options = {plugin:"conversations"}
							// Debug
							if(Engine.debug){
								Engine.GUI.Layouts.details.button(data,layout,{icon:"fas fa-stethoscope"},function(data,layout,button){
									button.off().click(function(){
										console.log(data);
										console.log(layout);
									});
								});
							}
							// Clear
							if(Engine.Auth.validate('custom', 'conversations_clear', 1)){
								Engine.GUI.Layouts.details.control(data,layout,{color:"danger",icon:"fas fa-snowplow",text:Engine.Contents.Language["Clear"]},function(data,layout,button){
									button.off().click(function(){
										Engine.request('conversations','clear',{ data:data.this.raw },function(){
											Engine.Plugins.conversations.load.details();
										});
									});
								});
							}
							// Merge
							if(Engine.Auth.validate('custom', 'conversations_merge', 1)){
								Engine.GUI.Layouts.details.control(data,layout,{color:"primary",icon:"fas fa-mail-bulk",text:Engine.Contents.Language["Merge"]},function(data,layout,button){
									button.off().click(function(){
										Engine.Plugins.conversations.merge(data,layout);
									});
								});
							}
							// Close
							if(Engine.Auth.validate('custom', 'conversations_close', 1)){
								Engine.GUI.Layouts.details.control(data,layout,{color:"danger",icon:"fas fa-envelope",text:Engine.Contents.Language["Close"]},function(data,layout,button){
									button.off().click(function(){
										Engine.Plugins.conversations.close(data,layout);
									});
								});
							}
							// ID
							Engine.GUI.Layouts.details.data(data,layout,{field:"id"});
							// Status
							if(Engine.Helper.isSet(Engine.Plugins,['statuses']) && Engine.Auth.validate('custom', 'conversations_statuses', 1)){
								Engine.Plugins.statuses.Layouts.details.detail(data,layout);
							}
							// Organizations
							if(Engine.Helper.isSet(Engine.Plugins,['organizations']) && Engine.Auth.validate('custom', 'conversations_organizations', 1)){
								Engine.Plugins.organizations.Layouts.details.detail(data,layout);
							}
							// Tags
							if(Engine.Helper.isSet(Engine.Plugins,['tags']) && Engine.Auth.validate('custom', 'conversations_tags', 1)){
								Engine.Plugins.tags.Layouts.details.detail(data,layout);
							}
							// Notes
							if(Engine.Helper.isSet(Engine.Plugins,['notes']) && Engine.Auth.validate('custom', 'conversations_notes', 1)){
								Engine.Plugins.notes.Layouts.details.tab(data,layout);
							}
							// Contacts
							if(Engine.Helper.isSet(Engine.Plugins,['contacts']) && Engine.Auth.validate('custom', 'conversations_contacts', 1)){
								Engine.Plugins.contacts.Layouts.details.tab(data,layout);
							}
							// Files
							if(Engine.Helper.isSet(Engine.Plugins,['files']) && Engine.Auth.validate('custom', 'conversations_files', 1)){
								Engine.Plugins.files.Layouts.details.tab(data,layout);
							}
							// Created
							options.field = "created";
							options.td = '<td><time class="timeago" datetime="'+data.this.raw.created.replace(/ /g, "T")+'" title="'+data.this.raw.created+'">'+data.this.raw.created+'</time></td>';
							Engine.GUI.Layouts.details.data(data,layout,options,function(data,layout,tr){ tr.find('time').timeago(); });
							// Subscription
							var icon = "fas fa-bell";
							if(Engine.Helper.isSet(data,['relations','users',Engine.Contents.Auth.User.id])){ var icon = "fas fa-bell-slash"; }
							Engine.GUI.Layouts.details.button(data,layout,{icon:icon},function(data,layout,button){
								button.off().click(function(){
									if(button.find('i').hasClass( "fa-bell" )){
										button.find('i').removeClass("fa-bell").addClass("fa-bell-slash");
										Engine.request("conversations",'subscribe',{data:{id:data.this.raw.id}},function(answer){
											var subscription = JSON.parse(answer);
											if(subscription.success != undefined){
												var sub = {};
												for(var [key, value] of Object.entries(Engine.Contents.Auth.User)){ sub[key] = value; }
												sub.created = subscription.output.relationship.created;
												sub.name = '';
												if((sub.first_name != '')&&(sub.first_name != null)){ if(sub.name != ''){sub.name += ' ';} sub.name += sub.first_name; }
												if((sub.middle_name != '')&&(sub.middle_name != null)){ if(sub.name != ''){sub.name += ' ';} sub.name += sub.middle_name; }
												if((sub.last_name != '')&&(sub.last_name != null)){ if(sub.name != ''){sub.name += ' ';} sub.name += sub.last_name; }
												Engine.Builder.Timeline.add.subscription(layout.timeline,sub,'bell','lightblue',function(item){
													if((Engine.Auth.validate('plugin','users',1))&&(Engine.Auth.validate('view','details',1,'users'))){
														item.find('i').first().addClass('pointer');
														item.find('i').first().off().click(function(){
															Engine.CRUD.read.show({ key:'username',keys:data.relations.users[item.attr('data-id')], href:"?p=users&v=details&id="+data.relations.users[item.attr('data-id')].username, modal:true });
														});
													}
												});
											}
										});
									} else {
										button.find('i').removeClass("fa-bell-slash").addClass("fa-bell");
										Engine.request(url.searchParams.get("p"),'unsubscribe',{data:{id:dataset.output.this.raw.id}},function(answer){
											var subscription = JSON.parse(answer);
											if(subscription.success != undefined){
												layout.timeline.find('[data-type="bell"][data-id="'+Engine.Contents.Auth.User.id+'"]').remove();
											}
										});
									}
								});
							});
							// Timeline
							Engine.Builder.Timeline.render(data,layout,{prefix:"conversations_"});
						});
					});
				}
			});
		},
	},
	merge:function(data,layout){
		Engine.Builder.modal($('body'), {
			title:'Type the conversation ID to be merged:',
			icon:'merging',
			zindex:'top',
			css:{ header: "bg-primary", body: "p-3"},
		}, function(modal){
			modal.on('hide.bs.modal',function(){ modal.remove(); });
			var dialog = modal.find('.modal-dialog');
			var header = modal.find('.modal-header');
			var body = modal.find('.modal-body');
			var footer = modal.find('.modal-footer');
			header.find('button[data-control="hide"]').remove();
			header.find('button[data-control="update"]').remove();
			Engine.Builder.input(body, 'id', null,{plugin:'conversations',type:'input'}, function(input){});
			// body.html(Engine.Contents.Language['Are you sure you want to close this conversation?']);
			footer.append('<button class="btn btn-primary" data-action="merge"><i class="fas fa-mail-bulk mr-1"></i>'+Engine.Contents.Language['Merge']+'</button>');
			footer.find('button[data-action="merge"]').off().click(function(){
				Engine.request('conversations','merge',{data:{id:data.this.raw.id,conversation:body.find('input').val()}},function(result){
					var dataset = JSON.parse(result);
					if(dataset.success != undefined){
						// Organizations
						if(Engine.Helper.isSet(Engine.Plugins,['organizations']) && Engine.Auth.validate('custom', 'conversations_organizations', 1)){
							Engine.Plugins.organizations.Layouts.details.detail(dataset.output.get.output,layout);
						}
						// Tags
						if(Engine.Helper.isSet(Engine.Plugins,['tags']) && Engine.Auth.validate('custom', 'conversations_tags', 1)){
							Engine.Plugins.tags.Layouts.details.detail(dataset.output.get.output,layout);
						}
						// Notes
						if(Engine.Helper.isSet(Engine.Plugins,['notes']) && Engine.Auth.validate('custom', 'conversations_notes', 1)){
							Engine.Plugins.notes.Layouts.details.tab(dataset.output.get.output,layout);
						}
						// Contacts
						if(Engine.Helper.isSet(Engine.Plugins,['contacts']) && Engine.Auth.validate('custom', 'conversations_contacts', 1)){
							Engine.Plugins.contacts.Layouts.details.tab(dataset.output.get.output,layout);
						}
						// Files
						if(Engine.Helper.isSet(Engine.Plugins,['files']) && Engine.Auth.validate('custom', 'conversations_files', 1)){
							Engine.Plugins.files.Layouts.details.tab(dataset.output.get.output,layout);
						}
						// Timeline
						Engine.Builder.Timeline.render(dataset.output.get.output,layout,{prefix:"conversations_"});
					}
				});
				modal.modal('hide');
			});
			modal.modal('show');
		});
	},
	close:function(data,layout){
		Engine.Builder.modal($('body'), {
			title:'Are you sure?',
			icon:'close',
			zindex:'top',
			css:{ header: "bg-danger", body: "p-3"},
		}, function(modal){
			modal.on('hide.bs.modal',function(){ modal.remove(); });
			var dialog = modal.find('.modal-dialog');
			var header = modal.find('.modal-header');
			var body = modal.find('.modal-body');
			var footer = modal.find('.modal-footer');
			header.find('button[data-control="hide"]').remove();
			header.find('button[data-control="update"]').remove();
			body.html(Engine.Contents.Language['Are you sure you want to close this conversation?']);
			footer.append('<button class="btn btn-danger" data-action="close"><i class="fas fa-envelope mr-1"></i>'+Engine.Contents.Language['Close']+'</button>');
			footer.find('button[data-action="close"]').off().click(function(){
				Engine.request('conversations','close',{data:{id:data.this.raw.id}},function(result){
					var dataset = JSON.parse(result);
					if(dataset.success != undefined){
						data.this.raw.status = 3;
						data.this.dom.status = 3;
						Engine.Plugins.statuses.update(data,layout);
					}
				});
				modal.modal('hide');
			});
			modal.modal('show');
		});
	},
	Timeline:{
		icon:"comments",
		object:function(dataset,layout,options = {},callback = null){
			if(options instanceof Function){ callback = options; options = {}; }
			var defaults = {icon: Engine.Plugins.conversations.Timeline.icon,color: "info"};
			for(var [key, option] of Object.entries(options)){ if(Engine.Helper.isSet(defaults,[key])){ defaults[key] = option; } }
			if(typeof dataset.id !== 'undefined'){
				var dateItem = new Date(dataset.created);
				var dateUS = dateItem.toLocaleDateString('en-US', {day: 'numeric', month: 'short', year: 'numeric'}).replace(/ /g, '-').replace(/,/g, '');
				Engine.Builder.Timeline.add.date(layout.timeline,dataset.created);
				var checkExist = setInterval(function() {
					if(layout.timeline.find('div.time-label[data-dateus="'+dateUS+'"]').length > 0){
						clearInterval(checkExist);
						Engine.Builder.Timeline.add.filter(layout,'conversations','Conversations');
						var html = '';
						html += '<div data-plugin="conversations" data-id="'+dataset.id+'" data-account="'+dataset.account+'" data-date="'+dateItem.getTime()+'">';
							html += '<i class="fas fa-'+defaults.icon+' bg-'+defaults.color+'"></i>';
							html += '<div class="timeline-item">';
								html += '<span class="time"><i class="fas fa-clock mr-2"></i><time class="timeago" datetime="'+dataset.created.replace(/ /g, "T")+'">'+dataset.created+'</time></span>';
								html += '<h3 class="timeline-header border-0">A Conversation from '+dataset.account+' was linked</h3>';
							html += '</div>';
						html += '</div>';
						layout.timeline.find('div.time-label[data-dateus="'+dateUS+'"]').after(html);
						var element = layout.timeline.find('[data-plugin="conversations"][data-id="'+dataset.id+'"]');
						element.find('time').timeago();
						var items = layout.timeline.children('div').detach().get();
						items.sort(function(a, b){
							return new Date($(b).data("date")) - new Date($(a).data("date"));
						});
						layout.timeline.append(items);
						element.find('i').first().addClass('pointer');
						element.find('i').first().off().click(function(){
							Engine.CRUD.read.show({ key:'id',keys:dataset, href:"?p=conversations&v=details&id="+dataset.id, modal:true });
						});
						if(callback != null){ callback(element); }
					}
				}, 100);
			}
		},
	},
}

Engine.Plugins.conversations.init();
