<?php
class conversationsAPI extends CRUDAPI {

	public function read($request = null, $data = null){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
			$this->Auth->setLimit(0);
			// Load Conversations
			$conversations = parent::read('conversations', $data);
			// Return
			return $conversations;
		}
	}

	public function get($request = null, $data = null){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
			$this->Auth->setLimit(0);
			// Load Conversation
			$get = parent::get('conversations', $data);
			// Build Relations
			$get = $this->buildRelations($get);
			// Return
			return $get;
		}
	}

	public function merge($request = null, $data = null){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
			if(isset($data['id'])){
				$conversations = $this->Auth->query('SELECT * FROM `conversations` WHERE `id` = ?',$data['id'])->fetchAll()->all();
		    if(!empty($conversations)){
					$conversation = $conversations[0];
					if(isset($data['conversation'])){
						$conversations = $this->Auth->query('SELECT * FROM `conversations` WHERE `id` = ?',$data['conversation'])->fetchAll()->all();
						if(!empty($conversations)){
							$merge = $conversations[0];
							$this->copyRelationships('conversations',$merge['id'],'conversations',$conversation['id']);
							$merge['status'] = 2;
							$status = $this->Auth->query('SELECT * FROM `statuses` WHERE `relationship` = ? AND `order` = ?','conversations',$conversation['status'])->fetchAll()->all();
							if(!empty($status)){
								$this->createRelationship([
									'relationship_1' => 'conversations',
									'link_to_1' => $merge['id'],
									'relationship_2' => 'statuses',
									'link_to_2' => $status[0]['id'],
								],true);
							}
							$this->createRelationship([
								'relationship_1' => 'conversations',
								'link_to_1' => $merge['id'],
								'relationship_2' => 'conversations',
								'link_to_2' => $conversation['id'],
							],true);
							$this->Auth->update('conversations',$merge,$merge['id']);
							$conversation['meta'] = json_encode(array_unique(array_merge(json_decode($conversation['meta'], true),json_decode($merge['meta'], true)), SORT_REGULAR), JSON_PRETTY_PRINT);
							$this->Auth->update('conversations',$conversation,$conversation['id']);
							// Return
							$return = [
								"success" => $this->Language->Field["Conversation merged"],
								"request" => $request,
								"data" => $data,
								"output" => [
									'this' => $conversation,
									'get' => $this->get('conversations', ['id' => $conversation['id']]),
								],
							];
						} else {
							// Return
							$return = [
								"error" => $this->Language->Field["Unable to find the conversation to merge"],
								"request" => $request,
								"data" => $data,
							];
						}
					} else {
						// Return
						$return = [
							"error" => $this->Language->Field["No conversation provided"],
							"request" => $request,
							"data" => $data,
						];
					}
				} else {
					// Return
					$return = [
						"error" => $this->Language->Field["Unable to find the conversation"],
						"request" => $request,
						"data" => $data,
					];
				}
			}
		} else {
			// Return
			$return = [
				"error" => $this->Language->Field["Unable to complete the request"],
				"request" => $request,
				"data" => $data,
			];
		}
		// Return
		return $return;
	}

	public function close($request = null, $data = null){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
			if(isset($data['id'])){
				$conversations = $this->Auth->query('SELECT * FROM `conversations` WHERE `id` = ?',$data['id'])->fetchAll()->all();
		    if(!empty($conversations)){
					$conversation = $conversations[0];
					$conversation['status'] = 3;
					$status = $this->Auth->query('SELECT * FROM `statuses` WHERE `relationship` = ? AND `order` = ?','conversations',$conversation['status'])->fetchAll()->all();
					if(!empty($status)){
						$this->createRelationship([
							'relationship_1' => 'conversations',
							'link_to_1' => $conversation['id'],
							'relationship_2' => 'statuses',
							'link_to_2' => $status[0]['id'],
						],true);
					}
					$this->Auth->update('conversations',$conversation,$conversation['id']);
					// Return
					$return = [
						"success" => $this->Language->Field["Conversation closed"],
						"request" => $request,
						"data" => $data,
						"output" => [
							'this' => $conversation,
						],
					];
				} else {
					// Return
					$return = [
						"error" => $this->Language->Field["Unable to find the conversation"],
						"request" => $request,
						"data" => $data,
					];
				}
			}
		} else {
			// Return
			$return = [
				"error" => $this->Language->Field["Unable to complete the request"],
				"request" => $request,
				"data" => $data,
			];
		}
		// Return
		return $return;
	}

	public function buildConversations(){
    $messages = $this->Auth->query('SELECT * FROM `messages` WHERE `isAttached` <> ? OR `isAttached` IS NULL','true')->fetchAll()->all();
    if(!empty($messages)){
      foreach($messages as $message){
        $found = false;
        $conversation = $this->Auth->query('SELECT * FROM `conversations` WHERE `account` = ? AND `messages` LIKE ?',$message['account'],'%'.$message['mid'].'%')->fetchAll()->all();
        if(!empty($conversation)){
					if(isset($this->Settings['debug']) && $this->Settings['debug']){ echo "[".count($conversation)."]Match by mail ID: ".$message['mid']."\n"; }
          $found = true;
        } else {
          $conversation = $this->Auth->query('SELECT * FROM `conversations` WHERE `account` = ? AND `messages` LIKE ?',$message['account'],'%'.$message['reply_to_id'].'%')->fetchAll()->all();
          if($message['reply_to_id'] != null && $message['reply_to_id'] != '' && !empty($conversation)){
						if(isset($this->Settings['debug']) && $this->Settings['debug']){ echo "[".count($conversation)."]Match by reply-to ID: ".$message['reply_to_id']."\n"; }
            $found = true;
          } else {
						if($message['reference_id'] != null && $message['reference_id'] != ''){
	            foreach(explode(";",$message['reference_id']) as $mid){
	              $conversation = $this->Auth->query('SELECT * FROM `conversations` WHERE `account` = ? AND `messages` LIKE ?',$message['account'],'%'.$mid.'%')->fetchAll()->all();
	              if(!empty($conversation)){
									if(isset($this->Settings['debug']) && $this->Settings['debug']){ echo "[".count($conversation)."]Match by mail reference: ".$mid."\n"; }
	                $found = true;
	                break;
	              }
	            }
						}
						if($message['meta'] != null && $message['meta'] != ''){
							$uniqueREF = ["CCN","TR"];
		          foreach(json_decode($message['meta'], true) as $ref){
								$ref = explode(":",$this->parseReference($ref));
								if(in_array($ref[0],$uniqueREF)){
									$conversation = $this->Auth->query('SELECT * FROM `conversations` WHERE `account` = ? AND `meta` LIKE ?',$message['account'],'%'.$ref[0].':'.$ref[1].'%')->fetchAll()->all();
		              if(!empty($conversation)){
										if(isset($this->Settings['debug']) && $this->Settings['debug']){ echo "[".count($conversation)."]Match by meta reference: ".$ref[0].':'.$ref[1]."\n"; }
		                $found = true;
		                break;
		              }
								}
	            }
						}
          }
        }
        if($found){
          // Add Message to Conversation
          $conversation = $conversation[0];
          $conversation['mid'] = $message['mid'];
          $conversation['messages'] = explode(";",$conversation['messages']);
          if(!in_array($message['mid'], $conversation['messages'])){
						array_push($conversation['messages'],$message['mid']);
					}
          $conversation['messages'] = trim(implode(";",$conversation['messages']),';');
          $conversation['contacts'] = explode(";",$conversation['contacts']);
          if(!in_array($message['from'], $conversation['contacts'])){array_push($conversation['contacts'],$message['from']);}
          if(!in_array($message['sender'], $conversation['contacts'])){array_push($conversation['contacts'],$message['sender']);}
          foreach(explode(";",$message['to']) as $contact){if(!in_array($contact, $conversation['contacts'])){array_push($conversation['contacts'],$contact);}}
          foreach(explode(";",$message['cc']) as $contact){if(!in_array($contact, $conversation['contacts'])){array_push($conversation['contacts'],$contact);}}
          foreach(explode(";",$message['bcc']) as $contact){if(!in_array($contact, $conversation['contacts'])){array_push($conversation['contacts'],$contact);}}
          $conversation['organizations'] = explode(";",$conversation['organizations']);
          foreach($conversation['contacts'] as $contact){
            if(!empty($contact) && $contact != '' && isset(explode("@",$contact)[1])){
              $organization = $this->Auth->query('SELECT * FROM `organizations` WHERE `setDomain` = ? OR `setDomain` LIKE ? OR `setDomain` LIKE ? OR `setDomain` LIKE ?',
								explode("@",$contact)[1],
								"%;".explode("@",$contact)[1],
								explode("@",$contact)[1].";%",
								"%;".explode("@",$contact)[1].";%"
							)->fetchAll()->all();
              if(!empty($organization)){
                if(isset($organization[0]['id']) && !in_array($organization[0]['id'], $conversation['organizations'])){
									array_push($conversation['organizations'],$organization[0]['id']);
								}
              }
            }
          }
          $conversation['organizations'] = trim(implode(";",$conversation['organizations']),';');
          $conversation['contacts'] = trim(implode(";",$conversation['contacts']),';');
          $conversation['files'] = explode(";",$conversation['files']);
          foreach(explode(";",$message['attachments']) as $file){
						if(!in_array($file, $conversation['files'])){
							$file = $this->Auth->read('files',$file);
							if($file != null){
								$file = $file->all()[0];
								array_push($conversation['files'],$file['id']);
							}
						}
					}
          $conversation['files'] = trim(implode(";",$conversation['files']),';');
          $conversation['meta'] = json_decode($conversation['meta'], true);
          if($conversation['meta'] == null || $conversation['meta'] == ''){$conversation['meta'] = [];}
          $message['meta'] = json_decode($message['meta'], true);
          if($message['meta'] == null || $message['meta'] == ''){$message['meta'] = [];}
          foreach($message['meta'] as $ref){if(!in_array($this->parseReference($ref), $conversation['meta'])){array_push($conversation['meta'],$this->parseReference($ref));}}
          $conversation['meta'] = json_encode($conversation['meta'], JSON_PRETTY_PRINT);
          $conversation['hasNew'] = "true";
          $conversation['id'] = $this->saveConversation($conversation);
          $query = $this->Auth->query('UPDATE `messages` SET `isAttached` = ? WHERE `id` = ?',["true",$message["id"]])->dump();
					$this->copyRelationships('messages',$message['id'],'conversations',$conversation['id']);
        } else {
          // Create a new Conversation
          $conversation = [
            "account" => $message['account'],
            "messages" => $message['mid'],
            "files" => [],
            "organizations" => [],
            "contacts" => [],
            "meta" => [],
            "hasNew" => "true"
          ];
          $message['meta'] = json_decode($message['meta'], true);
          if($message['meta'] == null || $message['meta'] == ''){$message['meta'] = [];}
          foreach($message['meta'] as $ref){if(!in_array($this->parseReference($ref), $conversation['meta'])){array_push($conversation['meta'],$this->parseReference($ref));}}
          $conversation['meta'] = json_encode($conversation['meta'], JSON_PRETTY_PRINT);
          if(!in_array($message['from'], $conversation['contacts'])){array_push($conversation['contacts'],$message['from']);}
          if(!in_array($message['sender'], $conversation['contacts'])){array_push($conversation['contacts'],$message['sender']);}
          foreach(explode(";",$message['to']) as $contact){if(!in_array($contact, $conversation['contacts'])){array_push($conversation['contacts'],$contact);}}
          foreach(explode(";",$message['cc']) as $contact){if(!in_array($contact, $conversation['contacts'])){array_push($conversation['contacts'],$contact);}}
          foreach(explode(";",$message['bcc']) as $contact){if(!in_array($contact, $conversation['contacts'])){array_push($conversation['contacts'],$contact);}}
          foreach($conversation['contacts'] as $contact){
            if(!empty($contact) && $contact != '' && isset(explode("@",$contact)[1])){
							$organization = $this->Auth->query('SELECT * FROM `organizations` WHERE `setDomain` = ? OR `setDomain` LIKE ? OR `setDomain` LIKE ? OR `setDomain` LIKE ?',
								explode("@",$contact)[1],
								"%;".explode("@",$contact)[1],
								explode("@",$contact)[1].";%",
								"%;".explode("@",$contact)[1].";%"
							)->fetchAll()->all();
              if(!empty($organization)){
                if(isset($organization[0]['id']) && !in_array($organization[0]['id'], $conversation['organizations'])){array_push($conversation['organizations'],$organization[0]['id']);}
              }
            }
          }
          $conversation['organizations'] = trim(implode(";",$conversation['organizations']),';');
          $conversation['contacts'] = strtolower(trim(implode(";",$conversation['contacts']),';'));
          foreach(explode(";",$message['attachments']) as $file){
						if(!in_array($file, $conversation['files'])){
							$file = $this->Auth->read('files',$file);
							if($file != null){
								$file = $file->all()[0];
								array_push($conversation['files'],$file['id']);
							}
						}
					}
          $conversation['files'] = trim(implode(";",$conversation['files']),';');
          $conversation['id'] = $this->saveConversation($conversation);
          $query = $this->Auth->query('UPDATE `messages` SET `isAttached` = ? WHERE `id` = ?',["true",$message["id"]])->dump();
        }
      }
    }
  }

  protected function parseReference($string){
		// Sanitize reference
		$reference = $string;
    $replace = ['CNTR-','PARS-','CCN:','CCN#','PKG#','WEIGHT#','CLIENT#','CN:','CN#','TR#','OTHER:','PO:','PO#','MWB:','MWB#','STATUS#','REF:','NBR:','INV:','INV#','OTHER:','---','--','CID:','UTF-8','(',')','<','>','{','}','[',']',';','"',"'",'#','_','=','+','!','?','@','$','%','^','&','*','\\','/','|'];
    foreach($replace as $str1){ $reference = str_replace($str1,'',strtoupper($reference)); }
		// Return based on string
		if(substr($string, 0, 5) == "CNTR-" || substr($string, 0, 3) == "CN:" || substr($string, 0, 3) == "CN#"){
			return "CN:".strtoupper($reference);
		}
		if(substr($string, 0, 5) == "PARS-" || substr($string, 0, 4) == "CCN:" || substr($string, 0, 4) == "CCN#"){
			return "CCN:".strtoupper($reference);
		}
		if(substr($string, 0, 3) == "PO-" || substr($string, 0, 3) == "PO:" || substr($string, 0, 3) == "PO#"){
			return "PO:".strtoupper($reference);
		}
		if(substr($string, 0, 4) == "MWB:" || substr($string, 0, 4) == "MWB#"){
			return "MWB:".strtoupper($reference);
		}
		if(substr($string, 0, 4) == "INV-" || substr($string, 0, 4) == "INV:" || substr($string, 0, 4) == "INV#"){
			return "INV:".strtoupper($reference);
		}
		if(substr($string, 0, 4) == "PKG#"){
			return "PKG:".strtoupper($reference);
		}
		if(substr($string, 0, 7) == "CLIENT#"){
			return "CLIENT:".strtoupper($reference);
		}
		if(substr($string, 0, 7) == "WEIGHT#"){
			return "WEIGHT:".strtoupper($reference);
		}
		// Return based on reference
		if(strlen(str_replace('-','',$reference))==14 && preg_match('/^[0-9]+$/', str_replace('-','',$reference))){
			$organization = $this->Auth->query('SELECT * FROM `organizations` WHERE `setCodeHVS` LIKE ? OR `setCodeLVS` LIKE ?',(substr(str_replace('-','',$reference), 0, 5)),(substr(str_replace('-','',$reference), 0, 5)))->fetchAll()->all();
			if(!empty($organization)){ return "TR:".strtoupper(str_replace('-','',$reference)); }
		}
		if(strlen($reference)>=10){
	    $organization = $this->Auth->query('SELECT * FROM `organizations` WHERE `setCodeCCN` LIKE ?',substr($reference, 0, 4))->fetchAll()->all();
	    if(!empty($organization) || strtoupper(substr($reference, 4, 4)) == "PARS"){ return "CCN:".strtoupper($reference); }
		}
    if(strlen($reference)>=10 && strlen($reference)<=11 && preg_match('/^[A-Z,a-z]+$/', substr($reference, 0, 4)) && preg_match('/^[0-9]+$/', substr($reference, 4))){
      return "CN:".strtoupper($reference);
    }
		$organization = $this->Auth->query('SELECT * FROM `organizations` WHERE `setCodeITMR4` = ?',$reference)->fetchAll()->all();
		if(!empty($organization)){
			if(isset($organization[0]['isClient']) && $organization[0]['isClient'] == "true"){
				return "CLIENT:".strtoupper($reference);
			}
			if(isset($organization[0]['isVendor']) && $organization[0]['isVendor'] == "true"){
				return "VENDOR:".strtoupper($reference);
			}
			if(isset($organization[0]['isFreightForwarder']) && $organization[0]['isFreightForwarder'] == "true"){
				return "FREIGHTFORWARDER:".strtoupper($reference);
			}
			if(isset($organization[0]['isCarrier']) && $organization[0]['isCarrier'] == "true"){
				return "CARRIER:".strtoupper($reference);
			}
			if(isset($organization[0]['isBroker']) && $organization[0]['isBroker'] == "true"){
				return "BROKER:".strtoupper($reference);
			}
		} else {
			return "OTHER:".strtoupper($reference);
		}
  }

  protected function saveConversation($conversation){
    $conversation["modified"] = date("Y-m-d H:i:s");
    $conversation["updated_by"] = $this->Auth->User['id'];
    if(!isset($conversation['id'])){
      $conversation["created"] = date("Y-m-d H:i:s");
      $conversation["owner"] = $this->Auth->User['id'];
	    $conversation["status"] = 1;
      $query = $this->Auth->query('INSERT INTO `conversations` (
        `created`,
        `modified`,
        `owner`,
        `updated_by`,
        `account`,
        `status`,
        `messages`,
        `files`,
        `organizations`,
        `contacts`,
        `meta`,
        `hasNew`
      ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        $conversation["created"],
        $conversation["modified"],
        $conversation["owner"],
        $conversation["updated_by"],
        $conversation["account"],
        $conversation["status"],
        $conversation["messages"],
        $conversation["files"],
        $conversation["organizations"],
        $conversation["contacts"],
        $conversation["meta"],
        $conversation["hasNew"]
      );
      $dump = $query->dump();
			$conversation['id'] = $dump['insert_id'];
			if(isset($this->Settings['debug']) && $this->Settings['debug']){ echo "[".$conversation['id']."]Conversation created\n"; }
    } else {
      $query = $this->Auth->query('UPDATE `conversations` SET
        `modified` = ?,
        `updated_by` = ?,
        `account` = ?,
        `status` = ?,
        `messages` = ?,
        `files` = ?,
        `organizations` = ?,
        `contacts` = ?,
        `meta` = ?,
        `hasNew` = ?
      WHERE `id` = ?',[
        $conversation["modified"],
        $conversation["updated_by"],
        $conversation["account"],
        $conversation["status"],
        $conversation["messages"],
        $conversation["files"],
        $conversation["organizations"],
        $conversation["contacts"],
        $conversation["meta"],
        $conversation["hasNew"],
        $conversation["id"]
      ]);
      $dump = $query->dump();
			if(isset($this->Settings['debug']) && $this->Settings['debug']){ echo "[".$conversation['id']."]Conversation updated\n"; }
    }
		set_time_limit(20);
		$status = $this->Auth->query('SELECT * FROM `statuses` WHERE `relationship` = ? AND `order` = ?','conversations',$conversation['status'])->fetchAll()->all();
		if(!empty($status)){
			$this->createRelationship([
				'relationship_1' => 'conversations',
				'link_to_1' => $conversation['id'],
				'relationship_2' => 'statuses',
				'link_to_2' => $status[0]['id'],
			]);
		}
		foreach(explode(';',trim($conversation['messages'],';')) as $mid){
			$message = $this->Auth->Read('messages',$mid,'mid');
			if($message != null){
				$message = $message->all()[0];
				$this->createRelationship([
					'relationship_1' => 'conversations',
					'link_to_1' => $conversation['id'],
					'relationship_2' => 'messages',
					'link_to_2' => $message['id'],
				]);
				$this->copyRelationships('messages',$message['id'],'conversations',$conversation['id']);
			}
		}
		foreach(explode(';',trim($conversation['files'],';')) as $file){
			if($file != '' && $file != null){
				$this->createRelationship([
					'relationship_1' => 'conversations',
					'link_to_1' => $conversation['id'],
					'relationship_2' => 'files',
					'link_to_2' => $file,
				]);
			}
		}
		foreach(explode(';',trim($conversation['organizations'],';')) as $organization){
			if($organization != '' && $organization != null){
				$this->createRelationship([
					'relationship_1' => 'conversations',
					'link_to_1' => $conversation['id'],
					'relationship_2' => 'organizations',
					'link_to_2' => $organization,
				]);
			}
		}
		foreach(json_decode($conversation['meta'], true) as $meta){
			$meta = explode(":",$meta);
			switch($meta[0]){
				default: break;
			}
		}
		return $conversation["id"];
  }
}
