<?php

require_once dirname(__FILE__,3).'/plugins/conversations/api.php';

$API = new conversationsAPI();

$cron = $API->buildConversations();

if((isset($this->Settings['debug']))&&($this->Settings['debug'])&&($cron!=null)){ echo json_encode($cron, JSON_PRETTY_PRINT); }
