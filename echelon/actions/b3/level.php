<?php
## NOTE: this page deals with both the request from the change client user level as well the requests to change a user's mask level ##
if($_POST['level-sub']) // what kind of request is it
	$is_mask = false;
else
	$is_mask = true;

if(!$is_mask) // check which auth level is needed
	$auth_name = 'edit_client_level';
else
	$auth_name = 'edit_mask';
	
$b3_conn = true; // this page needs to connect to the B3 database
require '../../inc.php';

if($_POST['level-sub'] || $_POST['mlevel-sub']) : // if the form is submitted

	## check that the sent form token is corret
	if(!$is_mask) {
		if(verifyFormToken('level', $tokens) == false) // verify token
			ifTokenBad('Change client level');
	} else {
		if(verifyFormToken('mask', $tokens) == false) // verify token
			ifTokenBad('Change client mask level');
	}
	
	## Set and clean vars ##
	$level = cleanvar($_POST['level']);
	$client_id = cleanvar($_POST['cid']);
	$old_level = cleanvar($_POST['old-level']);
	
	## Check Empties ##
	emptyInput($level, 'data not sent');
	emptyInput($client_id, 'data not sent');
	emptyInput($old_level, 'data not sent');
	
	## Check if the client_id is numeric ##
	if(!is_numeric($client_id))
		sendBack('Invalid data sent, greeting not changed');
	
	## Do some mojo with the B3 group information ##
	$b3_groups = $db->getB3Groups();
	
	// change around the recieved data
	$b3_groups_id = array();
	foreach($b3_groups as $group) :
		array_push($b3_groups_id, $group['id']); // make an array of all the group_bits that exsist
		$b3_groups_name[$group['id']] = $group['name']; // make an array of group_bits to matching names
	endforeach;
	
	// Check if the group_bits provided match a known group (Known groups is a list of groups pulled from the DB -- this allow more control for custom groups)
	if(!in_array($level, $b3_groups_id))
		sendBack('That group does not exist, please submit a real group');
	
	## Add Echelon Log ##
	$level_name = $b3_groups_name[$level];
	$old_level_name = $b3_groups_name[$old_level];
	//die($level_name);
	//var_dump($b3_groups_name);
	//exit;
	
	if(!$is_mask) {
		$comment = 'User level changed from '. $old_level_name .' to '. $level_name;
	} else {
		$comment = 'Mask level changed from '. $old_level_name .' to '. $level_name;
	}
	$user_id = $_SESSION['user_id'];
	$type = 'Level Change';
	
	$dbl->addEchLog($type, $comment, $client_id, $user_id);
	
	## Query Section ##
	if(!$is_mask)
		$query = "UPDATE clients SET group_bits = ? WHERE id = ? LIMIT 1";
	else
		$query = "UPDATE clients SET mask_level = ? WHERE id = ? LIMIT 1";
	$stmt = $db->mysql->prepare($query) or die('Database Error: '.$db->mysql->error);
	$stmt->bind_param('ii', $level, $client_id);
	$stmt->execute();
	if($stmt->affected_rows)
		sendGood('User level has been changed');
	else
		sendBack('User level was not changed');
	
	$stmt->close(); // close connection

else :

	set_error('Please do not call that page directly, thank you.');
	send('../../index.php');

endif;