<?php
class conversationsAPI extends CRUDAPI {

	protected $Blacklist = ['png','jpg'];

	public function get($request = null, $data = null){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
			$this->Auth->setLimit(0);
			// Load Event
			$get = parent::get('conversations', $data);
			// Build Relations
			$get = $this->buildRelations($get);
			// Return
			return $get;
		}
	}

	// public function get($request = null, $data = null){
	// 	if(isset($data)){
	// 		if(!is_array($data)){ $data = json_decode($data, true); }
	// 		if(!isset($data['key'])){ $data['key'] = 'id'; }
	// 		// Init Return
	// 		$return = false;
	// 		// Fetch Organization
	// 		$organization = $this->Auth->read($request,$data['id'],$data['key']);
	// 		if($organization != null){
	// 			$organization = $organization->all()[0];
	// 			foreach($organization as $key => $value){
	// 				if(!$this->Auth->valid('field',$key,1,$request)){
	// 					$organization[$key] = null;
	// 				}
	// 			}
	// 			// Fetch Assigned Organizations
	// 			$organizations = $this->Auth->query('SELECT * FROM `organizations` WHERE `assigned_to` = ? OR `assigned_to` LIKE ? OR `assigned_to` LIKE ? OR `assigned_to` LIKE ?',
	// 				$this->Auth->User['id'],
	// 				$this->Auth->User['id'].';%',
	// 				'%;'.$this->Auth->User['id'],
	// 				'%;'.$this->Auth->User['id'].';%'
	// 			)->fetchAll();
	// 			if(($organizations != null)&&(isset($organization['organization']))){
	// 				$organizations = $organizations->all();
	// 				foreach($organizations as $uniqueOrganization){
	// 					if($uniqueOrganization['id'] == $organization['organization']){ $return = true; }
	// 				}
	// 			}
	// 			// Fetch Relationships
	// 			$relationships = $this->getRelationships($request,$organization['id']);
	// 			// Build Organization Array
	// 			$organization = [
	// 				'raw' => $organization,
	// 				'dom' => $this->convertToDOM($organization),
	// 			];
	// 			// Init Details
	// 			$details = [];
	// 			// Fetch Details
	// 			foreach($relationships as $relations){
	// 				foreach($relations as $relation){
	// 					if(($relation['relationship'] == 'users')&&($relation['link_to'] == $this->Auth->User['id'])){ $return = true; }
	// 					if($this->Auth->valid('table',$relation['relationship'],1)){
	// 						$fetch = $this->Auth->read($relation['relationship'],$relation['link_to']);
	// 						if($fetch != null){
	// 							$details[$relation['relationship']]['raw'][$relation['link_to']] = $fetch->all()[0];
	// 							if($relation['relationship'] == "files"){ unset($details[$relation['relationship']]['raw'][$relation['link_to']]['file']); }
	// 							foreach($details[$relation['relationship']]['raw'][$relation['link_to']] as $key => $value){
	// 								if(!$this->Auth->valid('field',$key,1,$relation['relationship'])){
	// 									$details[$relation['relationship']]['raw'][$relation['link_to']][$key] = null;
	// 								}
	// 							}
	// 							$details[$relation['relationship']]['dom'][$relation['link_to']] = $this->convertToDOM($details[$relation['relationship']]['raw'][$relation['link_to']]);
	// 						}
	// 					}
	// 				}
	// 			}
	// 			// Fetch Messages Files Details
	// 			if(isset($details['messages']['raw'])){
	// 				foreach($details['messages']['raw'] as $message){
	// 					foreach(explode(';',$message['attachments']) as $file){
	// 						$file = $this->Auth->query('SELECT * FROM `files` WHERE `id` = ?',$file)->fetchAll();
	// 						if($file != null){
	// 							$file = $file->all();
	// 							if(!empty($file)){
	// 								$file = $file[0];
	// 								unset($file['file']);
	// 								$details['files']['raw'][$file['id']] = $file;
	// 								$details['files']['dom'][$file['id']] = $this->convertToDOM($file);
	// 							}
	// 						}
	// 					}
	// 				}
	// 			}
	// 			// Fetch Details Statuses
	// 			foreach($details as $table => $detail){
	// 				if($table != 'statuses'){
	// 					$statuses = $this->Auth->query('SELECT * FROM `statuses` WHERE `type` = ?',$table);
	// 					var_dump($table,$statuses);
	// 					if(!is_bool($statuses) && $statuses->numRows() > 0){
	// 						$statuses = $statuses->fetchAll()->all();
	// 						foreach($statuses as $status){
	// 							$details['statuses']['raw'][$status['id']] = $status;
	// 							$details['statuses']['dom'][$status['id']] = $this->convertToDOM($status);
	// 						}
	// 					}
	// 				}
	// 			}
	// 			// Test Permissions
	// 			if(($this->Auth->valid('plugin',$request,1))&&($this->Auth->valid('view','details',1,$request))){ $return = true; }
	// 			// Return
	// 			if($return){
	// 				return [
	// 					"success" => $this->Language->Field["This request was successfull"],
	// 					"request" => $request,
	// 					"data" => $data,
	// 					"output" => [
	// 						'this' => $organization,
	// 						'relationships' => $relationships,
	// 						'details' => $details,
	// 					],
	// 				];
	// 			} else {
	// 				return [
	// 					"error" => $this->Language->Field["You are not allowed to access this record"],
	// 					"request" => $request,
	// 					"data" => $data,
	// 				];
	// 			}
	// 		} else {
	// 			return [
	// 				"error" => $this->Language->Field["Unknown record"],
	// 				"request" => $request,
	// 				"data" => $data,
	// 			];
	// 		}
	// 	}
	// }

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
              $organization = $this->Auth->query('SELECT * FROM `organizations` WHERE `setDomain` LIKE ?',explode("@",$contact)[1])->fetchAll()->all();
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
								if(!in_array($file['type'],$this->Blacklist)){
									array_push($conversation['files'],$file['id']);
								}
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
              $organization = $this->Auth->query('SELECT * FROM `organizations` WHERE `setDomain` LIKE ?',explode("@",$contact)[1])->fetchAll()->all();
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
								if(!in_array($file['type'],$this->Blacklist)){
									array_push($conversation['files'],$file['id']);
								}
							}
						}
					}
          $conversation['files'] = trim(implode(";",$conversation['files']),';');
          $conversation['id'] = $this->saveConversation($conversation,$messages);
          $query = $this->Auth->query('UPDATE `messages` SET `isAttached` = ? WHERE `id` = ?',["true",$message["id"]])->dump();
        }
      }
    }
  }

  protected function parseReference($string){
    $replace = ['---','--','CID:','CNTR-','PARS-','UTF-8','CCN:','CCN#','CN:','CN#','OTHER:','PO:','PO#','MWB:','MWB#','STATUS#','REF:','NBR:','INV:','INV#','OTHER:','(',')','<','>','{','}','[',']',';','"',"'",'#','_','=','+','.',',','!','?','@','$','%','^','&','*','\\','/','|'];
    foreach($replace as $str1){ $string = str_replace($str1,'',strtoupper($string)); }
		if(strlen(str_replace('-','',$string))==14 && preg_match('/^[0-9]+$/', str_replace('-','',$string))){
			$organization = $this->Auth->query('SELECT * FROM `organizations` WHERE `setCodeHVS` LIKE ?',(substr(str_replace('-','',$string), 0, 5)))->fetchAll()->all();
			if(!empty($organization)){ return "TR:".strtoupper(str_replace('-','',$string)); }
		}
		if(strlen($string)>=10){
	    $organization = $this->Auth->query('SELECT * FROM `organizations` WHERE `setCodeCCN` LIKE ?',substr($string, 0, 4))->fetchAll()->all();
	    if(!empty($organization) || strtoupper(substr($string, 4, 4)) == "PARS"){ return "CCN:".strtoupper($string); }
		}
    if(strlen($string)>=10 && strlen($string)<=11 && preg_match('/^[A-Z,a-z]+$/', substr($string, 0, 4)) && preg_match('/^[0-9]+$/', substr($string, 4))){
      return "CN:".strtoupper($string);
    }
    return "OTHER:".strtoupper($string);
  }

  protected function saveConversation($conversation,$messages = []){
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
      set_time_limit(20);
    } else {
      $query = $this->Auth->query('UPDATE `conversations` SET
        `modified` = ?,
        `updated_by` = ?,
        `account` = ?,
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
        $conversation["messages"],
        $conversation["files"],
        $conversation["organizations"],
        $conversation["contacts"],
        $conversation["meta"],
        $conversation["hasNew"],
        $conversation["id"]
      ]);
      set_time_limit(20);
      $dump = $query->dump();
    }
		foreach(explode(';',trim($conversation['messages'],';')) as $mid){
			foreach($messages as $message){
				if($message['mid'] == $mid){
					$this->createRelationship([
						'relationship_1' => 'conversations',
						'link_to_1' => $conversation['id'],
						'relationship_2' => 'messages',
						'link_to_2' => $message['id'],
					]);
					$this->copyRelationships('messages',$message['id'],'conversations',$conversation['id']);
					break;
				}
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
				case"TR":
					if(isset($this->Settings['plugins']['b3']['status']) && $this->Settings['plugins']['b3']['status']){
						$B3 = new b3API();
						$B3->saveB3from('conversations',$conversation);
					}
				default: break;
			}
		}
		return $conversation["id"];
  }
}
