<div data-plugin="conversations" data-id="">
	<span style="display:none;" data-plugin="conversations" data-key="id"></span>
	<span style="display:none;" data-plugin="conversations" data-key="created"></span>
	<span style="display:none;" data-plugin="conversations" data-key="modified"></span>
	<span style="display:none;" data-plugin="conversations" data-key="owner"></span>
	<span style="display:none;" data-plugin="conversations" data-key="account"></span>
	<span style="display:none;" data-plugin="conversations" data-key="status"></span>
	<span style="display:none;" data-plugin="conversations" data-key="messages"></span>
	<span style="display:none;" data-plugin="conversations" data-key="files"></span>
	<span style="display:none;" data-plugin="conversations" data-key="organizations"></span>
	<span style="display:none;" data-plugin="conversations" data-key="contacts"></span>
	<span style="display:none;" data-plugin="conversations" data-key="meta"></span>
	<span style="display:none;" data-plugin="conversations" data-key="hasNew"></span>
	<div class="row">
		<div class="col-md-5">
			<div class="card" id="conversations_details">
	      <div class="card-header d-flex p-0">
	        <h3 class="card-title p-3">Conversation Details</h3>
	      </div>
	      <div class="card-body p-0">
					<div class="row">
						<div class="col-12 p-4 text-center">
							<img class="profile-user-img img-fluid img-circle" style="height:150px;width:150px;" src="/dist/img/building.png">
						</div>
						<div class="col-12 pt-2 pl-2 pr-2 pb-0 m-0">
							<table class="table table-striped table-hover m-0">
								<thead>
									<tr>
										<th colspan="2" class="p-3">
											<div class="btn-group btn-block">
                        <button type="submit" class="btn btn-success">Create Shipment</button>
                      </div>
										</th>
									</tr>
								</thead>
								<tbody>
                  <tr>
                    <td><b>Account</b></td>
                    <td data-plugin="conversations" data-key="account"></td>
                  </tr>
                  <tr>
                    <td><b>Status</b></td>
                    <td data-plugin="conversations" data-key="status"></td>
                  </tr>
                  <tr>
                    <td><b>Created</b></td>
                    <td id="conversations_created"><time class="timeago"></time></td>
                  </tr>
									<tr>
										<td><b>Organizations</b></td>
										<td data-plugin="conversations" data-key="organizations"></td>
                  </tr>
									<tr>
										<td><b>Contacts</b></td>
										<td data-plugin="conversations" data-key="contacts"></td>
									</tr>
									<tr>
										<td><b>Files</b></td>
										<td data-plugin="conversations" data-key="files"></td>
									</tr>
									<tr>
										<td><b>References</b></td>
										<td data-plugin="conversations" data-key="references"></td>
									</tr>
								</tbody>
							</table>
				    </div>
			    </div>
				</div>
	    </div>
      <div class="card" id="conversations_reference_form" style="display:none;">
	      <div class="card-body p-0">
          <div class="vertical-input-group">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-sitemap mr-1"></i>Type</span>
              </div>
              <select name="type" class="form-control select2bs4">
                <option value="ccn">Cargo Control Number</option>
                <option value="cn">Container</option>
                <option value="po">Purchase Order</option>
                <option value="inv">Invoice</option>
                <option value="tr">Transaction</option>
                <option value="ref">Client Reference</option>
                <option value="nbr">Shipment Number</option>
                <option value="time">Time</option>
                <option value="date">Date</option>
                <option value="eta">ETA</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-tags mr-1"></i>Reference(s)</span>
              </div>
              <select name="reference" multiple="" class="form-control select2bs4"></select>
            </div>
            <div class="input-group">
              <button type="button" class="btn btn-success btn-block"><i class="fas fa-plus"></i></button>
            </div>
          </div>
        </div>
      </div>
		</div>
		<div class="col-md-7">
			<div class="card" id="conversations_main_card">
	      <div class="card-header d-flex p-0">
	        <ul class="nav nav-pills p-2" id="conversations_main_card_tabs">
	          <li class="nav-item"><a class="nav-link active" href="#conversations_history" data-toggle="tab"><i class="fas fa-history mr-1"></i>History</a></li>
	          <li class="nav-item"><a class="nav-link" href="#conversations_comments" data-toggle="tab"><i class="fas fa-comment mr-1"></i>Comment</a></li>
	          <li class="nav-item"><a class="nav-link" href="#conversations_notes" data-toggle="tab"><i class="fas fa-sticky-note mr-1"></i>Note</a></li>
	        </ul>
					<div class="btn-group ml-auto">
						<button type="button" data-action="subscribe" class="btn"><i class="fas fa-bell"></i></button>
						<button type="button" data-action="unsubscribe" style="display:none;" class="btn"><i class="fas fa-bell-slash"></i></button>
					</div>
	      </div>
	      <div class="card-body p-0">
	        <div class="tab-content">
	          <div class="tab-pane p-3 active" id="conversations_history">
							<div class="timeline" id="conversations_timeline"></div>
						</div>
	          <div class="tab-pane p-0" id="conversations_comments">
							<div class="input-group">
	              <div class="input-group-prepend">
	                <span class="input-group-text"><i class="fas fa-address-card mr-1"></i>Contacts</span>
	              </div>
	              <select name="contacts" multiple="" class="form-control select2bs4"></select>
	            </div>
							<div id="conversations_comments_textarea">
								<textarea title="Comment" name="comment" class="form-control" data-plugin="conversations" data-form="comments"></textarea>
							</div>
							<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
						    <form class="form-inline my-2 my-lg-0 ml-auto">
						      <button class="btn btn-primary my-2 my-sm-0" type="button" data-action="reply"><i class="fas fa-reply mr-1"></i>Reply</button>
						    </form>
							</nav>
	          </div>
	          <div class="tab-pane p-0" id="conversations_notes">
							<div id="conversations_notes_textarea">
								<textarea title="Note" name="note" class="form-control"></textarea>
							</div>
							<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
						    <form class="form-inline my-2 my-lg-0 ml-auto">
									<select class="form-control mr-sm-2" name="status" style="width: 150px;"></select>
						      <button class="btn btn-warning my-2 my-sm-0" type="button" data-action="reply"><i class="fas fa-reply mr-1"></i>Add Note</button>
						    </form>
							</nav>
	          </div>
	        </div>
	      </div>
	    </div>
		</div>
	</div>
</div>
