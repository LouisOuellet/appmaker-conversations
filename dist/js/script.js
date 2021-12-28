API.Plugins.conversations = {
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
						for(const [key, value] of Object.entries(dataset.output.dom)){ API.Helper.set(API.Contents,['data','dom','conversations',value.id],value); }
						for(const [key, value] of Object.entries(dataset.output.raw)){ API.Helper.set(API.Contents,['data','raw','conversations',value.id],value); }
						API.Builder.table(card.children('.card-body'), dataset.output.dom, {
							headers:dataset.output.headers,
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
			API.request(url.searchParams.get("p"),'get',{data:{id:id,key:'id'}},function(result){
				var dataset = JSON.parse(result);
				if(dataset.success != undefined){
					container.attr('data-id',dataset.output.this.raw.id);
					// GUI
					// Adding Layout
					bgImage = '/plugins/conversations/dist/img/conversation.png';
					API.GUI.Layouts.details.build(dataset.output,container,{title:"Conversation Details",image:bgImage},function(data,layout){
						if(layout.main.parents().eq(2).parent('.modal-body').length > 0){
							var modal = layout.main.parents().eq(2).parent('.modal-body').parents().eq(2);
							if(API.Auth.validate('plugin', 'conversations', 3)){
								modal.find('.modal-header').find('.btn-group').find('[data-control="update"]').off().click(function(){
									API.CRUD.update.show({ container:layout.main.parents().eq(2), keys:data.this.raw });
								});
							} else {
								modal.find('.modal-header').find('.btn-group').find('[data-control="update"]').remove();
							}
						}
						// History
						API.GUI.Layouts.details.tab(data,layout,{icon:"fas fa-history",text:API.Contents.Language["History"]},function(data,layout,tab,content){
							API.Helper.set(API.Contents,['layouts','conversations',data.this.raw.id,layout.main.attr('id')],layout);
							content.addClass('p-3');
							content.append('<div class="timeline" data-plugin="conversations"></div>');
							layout.timeline = content.find('div.timeline');
							var today = new Date();
							API.Builder.Timeline.add.date(layout.timeline,today);
							layout.timeline.find('.time-label').first().html('<div class="btn-group"></div>');
							layout.timeline.find('.time-label').first().find('div.btn-group').append('<button class="btn btn-primary" data-trigger="all">'+API.Contents.Language['All']+'</button>');
							var options = {plugin:"conversations"}
							// Debug
							if(API.debug){
								API.GUI.Layouts.details.button(data,layout,{icon:"fas fa-stethoscope"},function(data,layout,button){
									button.off().click(function(){
										console.log(data);
										console.log(layout);
									});
								});
							}
							// Clear
							if(API.Auth.validate('custom', 'conversations_clear', 1)){
								API.GUI.Layouts.details.control(data,layout,{color:"danger",icon:"fas fa-snowplow",text:API.Contents.Language["Clear"]},function(data,layout,button){
									button.off().click(function(){
										API.request('conversations','clear',{ data:data.this.raw },function(){
											API.Plugins.conversations.load.details();
										});
									});
								});
							}
							// Notes
							if(API.Helper.isSet(API.Plugins,['notes']) && API.Auth.validate('custom', 'conversations_notes', 1)){
								API.Plugins.notes.Layouts.details.tab(data,layout);
							}
							// Contacts
							if(API.Helper.isSet(API.Plugins,['contacts']) && API.Auth.validate('custom', 'conversations_contacts', 1)){
								API.Plugins.contacts.Layouts.details.tab(data,layout);
							}
							// Created
							options.field = "created";
							options.td = '<td><time class="timeago" datetime="'+data.this.raw.created.replace(/ /g, "T")+'" title="'+data.this.raw.created+'">'+data.this.raw.created+'</time></td>';
							API.GUI.Layouts.details.data(data,layout,options,function(data,layout,tr){ tr.find('time').timeago(); });
							// Subscription
							var icon = "fas fa-bell";
							if(API.Helper.isSet(data,['relations','users',API.Contents.Auth.User.id])){ var icon = "fas fa-bell-slash"; }
							API.GUI.Layouts.details.button(data,layout,{icon:icon},function(data,layout,button){
								button.off().click(function(){
									if(button.find('i').hasClass( "fa-bell" )){
										button.find('i').removeClass("fa-bell").addClass("fa-bell-slash");
										API.request("conversations",'subscribe',{data:{id:data.this.raw.id}},function(answer){
											var subscription = JSON.parse(answer);
											if(subscription.success != undefined){
												var sub = {};
												for(var [key, value] of Object.entries(API.Contents.Auth.User)){ sub[key] = value; }
												sub.created = subscription.output.relationship.created;
												sub.name = '';
												if((sub.first_name != '')&&(sub.first_name != null)){ if(sub.name != ''){sub.name += ' ';} sub.name += sub.first_name; }
												if((sub.middle_name != '')&&(sub.middle_name != null)){ if(sub.name != ''){sub.name += ' ';} sub.name += sub.middle_name; }
												if((sub.last_name != '')&&(sub.last_name != null)){ if(sub.name != ''){sub.name += ' ';} sub.name += sub.last_name; }
												API.Builder.Timeline.add.subscription(layout.timeline,sub,'bell','lightblue',function(item){
													if((API.Auth.validate('plugin','users',1))&&(API.Auth.validate('view','details',1,'users'))){
														item.find('i').first().addClass('pointer');
														item.find('i').first().off().click(function(){
															API.CRUD.read.show({ key:'username',keys:data.relations.users[item.attr('data-id')], href:"?p=users&v=details&id="+data.relations.users[item.attr('data-id')].username, modal:true });
														});
													}
												});
											}
										});
									} else {
										button.find('i').removeClass("fa-bell-slash").addClass("fa-bell");
										API.request(url.searchParams.get("p"),'unsubscribe',{data:{id:dataset.output.this.raw.id}},function(answer){
											var subscription = JSON.parse(answer);
											if(subscription.success != undefined){
												layout.timeline.find('[data-type="bell"][data-id="'+API.Contents.Auth.User.id+'"]').remove();
											}
										});
									}
								});
							});
							// Timeline
							API.Builder.Timeline.render(data,layout,{prefix:"conversations_"});
						});
					});
				}
			});
		},
	},
	GUI:{
		buttons:{
			details:function(dataset,options = {}){
				var defaults = {
					icon:{details:"fas fa-building",remove:"fas fa-unlink"},
					action:{details:"details",remove:"unlink"},
					color:{details:"primary",remove:"danger"},
					key:"name",
					id:"id",
					content:"",
					remove:false,
				};
				if(API.Helper.isSet(options,['icon','details'])){ defaults.icon.details = options.icon.details; }
				if(API.Helper.isSet(options,['icon','remove'])){ defaults.icon.remove = options.icon.remove; }
				if(API.Helper.isSet(options,['color','details'])){ defaults.color.details = options.color.details; }
				if(API.Helper.isSet(options,['color','remove'])){ defaults.color.remove = options.color.remove; }
				if(API.Helper.isSet(options,['action','details'])){ defaults.action.details = options.action.details; }
				if(API.Helper.isSet(options,['action','remove'])){ defaults.action.remove = options.action.remove; }
				if(API.Helper.isSet(options,['key'])){ defaults.key = options.key; }
				if(API.Helper.isSet(options,['id'])){ defaults.id = options.id; }
				if(API.Helper.isSet(options,['remove'])){ defaults.remove = options.remove; }
				if(API.Helper.isSet(options,['content'])){ defaults.content = options.content; }
				else { defaults.content = dataset[defaults.key]; }
				var html = '';
				html += '<div class="btn-group m-1" data-id="'+dataset[defaults.id]+'">';
					html += '<button type="button" class="btn btn-xs bg-'+defaults.color.details+'" data-id="'+dataset[defaults.id]+'" data-action="'+defaults.action.details+'"><i class="'+defaults.icon.details+' mr-1"></i>'+defaults.content+'</button>';
					if(defaults.remove){
						html += '<button type="button" class="btn btn-xs bg-'+defaults.color.remove+'" data-id="'+dataset[[defaults.id]]+'" data-action="'+defaults.action.remove+'"><i class="'+defaults.icon.remove+'"></i></button>';
					}
				html += '</div>';
				return html;
			},
		},
	},
	Events:{},
}

API.Plugins.conversations.init();
