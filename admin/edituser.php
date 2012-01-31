<?php
/**
	
	* @author Helena F Deus <helenadeus@gmail.com>
	* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	* @package S3DB http://www.s3db.org
*/
#edituser.php is the interface, for admin users, to change a specific user account, including adding the user to 1 or more groups.
	#Helena F Deus (helenadeus@gmail.com)
	
	include('adminheader.php');
	$section_num = '2';
	$website_title = $GLOBALS['s3db_info']['server']['site_title'].' - Edit Profile';

	#if(!$_SESSION['db']){
	//"How old is this key?";
	#$sql = "select expires,created from s3db_access_keys where key_id='".$key."' and expires>='".date('Y-m-d H:i:s')."'";
	#$db->query($sql);
	
	#if($db->next_record()){
	#$expires = $db->f('expires');
	#$created = $db->f('created_on');
	#}
	#echo (strtotime($expires)-strtotime($created));exit;
	#}
	
	
	#echo '<pre>';print_r($useredited);exit;
	#$useredited = s3info('user', $id, $db);
	$useredited =URIinfo('U'.$id, $user_id,$key, $db);
	
	$account_id= $id;
	$imp_user_id= $id;
	
	$account_addr_id = $useredited['account_addr_id'];	
	
	
	#find the groups where user belongs - the only one he can edit
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='groups';
	$s3ql['where']['user_id']=$user_id;

	$admin_groups = S3QLaction($s3ql);
	$admin_groups_ids = grab_id('group', $admin_groups);

	
	#add admin group to the list of groups.
	if($user_id=='1')
	{$mainGroup = array('groupname'=>'Admin', 'group_id'=>'1');
		
		if(is_array($admin_groups) && !empty($admin_groups))
		{array_push($admin_groups, array(0=>$mainGroup));
		array_push($admin_groups_ids, array('0'=>'1'));
		}
		else {
			$admin_groups = array('0'=>$mainGroup);
			$admin_groups_ids = array('0'=>'1');
		}
	}
	

	#now find teh groups where the user being edited is already part of (for "selected")
	$s3ql=compact('user_id','db');
	$s3ql['select']='group_id';
	$s3ql['from']='groups';
	$s3ql['where']['user_id']=$imp_user_id;

	
	$user_groups = S3QLaction($s3ql);
	$user_group_ids = grab_id('group', $user_groups);
	
	#$groups = list_groups($G);
	
	if($_POST['back'])
	{
		Header('Location: '.$action['listusers'].'');
	}
	
if($_POST['submit'])
	{
		
		$prot=$_REQUEST['protocol'];
		$auth_id=$_REQUEST['authentication_id'];
		$auth=$_REQUEST['authority'];
		
		if($auth_id!=''){
		##inserting a new authority
		#$user_uri =  (($prot!='http')?$prot.':':'').$auth.':'.$auth_id;
		$user_uri =  $prot.':'.$auth.':'.$auth_id;
		
		$s3qlA=array('user_id'=>'1','db'=>$db);#to be changed once this user can insert in this own item!!
		$s3qlA['insert']='authentication';
		$s3qlA['where']['user_id']=$imp_user_id;
		$s3qlA['where']['protocol']=$user_uri;
		$s3qlA['where']['authentication_id']=$user_uri;
		$s3qlA['format']='php';
		list($valid, $data)=apiQuery($s3qlA);
		
		
		}
		

		#check if login and email inserted
		if ($_POST['account_lid']=='') {
			$message='Please indicate a loginID';
		}
		elseif ($_POST['account_email']=='') {
			$message = 'Email is required for every account';
		}
		#check if pass1 = pass2
		elseif ($_POST['account_pwd']!='' && $_POST['account_pwd_2'] =='') {
				$password2_required='*';
				$message='Please re-type your password to confirm';
		}
		else if($_POST['account_pwd'] != $_POST['account_pwd_2'])
			{
				$password_required='*';
				$password2_required='*';
				$message='Re-typed password does not match';
			}
		
		else {
				
		#if ($_POST['account_pwd']=='') {
		#	$account_pwd = $useredited['account_pwd'];
		#}	
		#else {
			$account_pwd = $_POST['account_pwd'];
		#}
		if ($_POST['account_email']=='') {
			$email = $useredited['account_email'];
			
		}
		else {
			$email = $_POST['account_email'];
		}

		
		if ($_POST['Public']) {
			$account_type = 'p';
		}
		elseif(is_array($_POST['account_groups']) && in_array('1', $_POST['account_groups'])) {
			$account_type = 'a';
		}
		else {
			$account_type = 'u';
		}

		
		
		$s3ql=compact('user_id','db');
		$s3ql['edit']='user';
		$s3ql['where']['user_id']=$imp_user_id;

		
		$s3ql['set'] = array('account_lid'=>$_POST['account_lid'],
				 'account_uname'=>$_POST['account_uname'],
				 'account_pwd'=>$account_pwd,
				  'addr1'=>$_POST['addr1'],
				 'addr2'=>$_POST['addr2'],
				 'city'=>$_POST['city'],
				 'state'=>$_POST['state'],
				 'postal_code'=>$_POST['postal_code'],
				 'country'=>$_POST['country'],
				 'account_email'=>$email,
				'account_type'=>$account_type,
				 'account_phone'=>$_POST['account_phone']);
		
		if($_POST['inherit_permission']=='on' && $_POST['filter']!=""){
			$s3ql['set']['permission_level'] =$_POST['filter']; 
		}
		
		if (!$_POST['account_pwd']) {
			$s3ql['set'] = array_diff_key($s3ql['set'], array('password'=>''));
		}
		$s3ql['format']='php';
		
		$doneediting = S3QLaction($s3ql);
		#echo $doneediting;
		$doneediting = unserialize($doneediting);
		
		#ereg('<error>([0-9]+)</error><message>(.*)</message>', $doneediting , $s3qlout);
		if($doneediting[0]['error_code']!='0') 
			{$message .= $doneediting[0]['message'];}
		$selected_groups = $_POST['account_groups'];
		
		#all the groups minus the groups that are on the post should give the groups from which the user was deleted. If the admin had no permission to do so, s3ql will know it and not delete the user from the group.
		
		if (!is_array($selected_groups) ) {
			$selected_groups = array();
		}

		
		$deleted_groups = array_diff($user_group_ids, $selected_groups);
		
		
		#from all the possible groups where admin can add or remove this user, check: user used to be in group but does not exist in POST? then delete user from group; check: user did not used to exist in group but exists in post? then add user.
		
		if (!is_array($selected_groups)) {
			$selected_groups = array();
		}
		
		foreach ($selected_groups as $new_group_id) {
			#group in post but not in old group, add him
			if (!in_array($new_group_id, $user_group_ids)) {
				$s3ql=compact('user_id','db');
				$s3ql['insert']='user';
				$s3ql['where']['user_id']=$imp_user_id;
				$s3ql['where']['group_id']=$new_group_id;

				#echo '<pre>';print_r($s3ql);
				$inserted = S3QLaction($s3ql);


				
			}
		}
			#group used to be group, is NOT in the post AND admn has permission to remove him. S3QL will check if there is permission to delete, if not, nothing happens anyway
			
			foreach ($deleted_groups as $deleted_group) {
			
				$s3ql=compact('user_id','db');
				$s3ql['delete']='user';
				$s3ql['where']['user_id']=$imp_user_id;
				$s3ql['where']['group_id']=$deleted_group;

				
				#echo '<pre>';print_r($s3ql);
				$deleted = S3QLaction($s3ql);
				#echo $deleted;
				#exit;
				
			
				
			}
			
		
		
		#ereg('<error>([0-9]+)</error><message>(.*)</message>', $doneediting, $s3qlout);
		$message = $doneediting[0]['message'];
		


		
		
		}#close all is well with used edit
	}#close submited
	if($_REQUEST['action']=='delete_auth'){
	$s3ql=compact('user_id','db');
	$s3ql['delete']='authentication';
	if($_REQUEST['authentication_id']!=''){
	$s3ql['where']['authentication_id']=$_REQUEST['authentication_id'];
	list($valid,$deleted)=apiQuery($s3ql);
	header('Location: '.$action['edituser'].'&id='.$imp_user_id);
	exit;
	}
	
	}
	include(S3DB_SERVER_ROOT.'/s3style.php');
	include(S3DB_SERVER_ROOT.'/tabs.php');

	#re-query users groups, they might have changed
	$s3ql=compact('user_id','db');
	$s3ql['select']='group_id';
	$s3ql['from']='groups';
	$s3ql['where']['user_id']=$_REQUEST['id'];
	

	$user_groups = S3QLaction($s3ql);
	$user_group_ids = grab_id('group', $user_groups);
	
	
	#user and adming groups are way in the top of this script because they are also needed for submit
	if (is_array($admin_groups)) {
		
		foreach ($admin_groups as $group_info) {
			#which groups is the user already in?
			
			if (is_array($user_groups) && in_array($group_info['group_id'], $user_group_ids)) {
				
					$select = ' selected';
			}
			else {
				$select = '';
			}
			
			$group_select_list .= '<option value="'.$group_info['group_id'].'"'.$select.'>'.$group_info['groupname'].'</option>';
		}
	}
	#$group_select_list= create_group_list($groups, $useredited['account_id']);
		//echo $_POST['account_pwd'];
	
	//$manager= 'User Manager';
	$edit_message= 'Edit User Account';
	$content_width= '70%';
 
	$button= '<input type="submit" name="submit" value="Update User Account">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="back" value="Back to User Account List">';
	$account_lid= $useredited['account_lid'];		
	
	
	if($useredited['account_status'] =='A')
		$checked= 'checked';
	else
		$checked= '';
	
	$useredited = s3info('user', $id, $db);
	
	$account_uname= $useredited['account_uname'];
	$account_type= ($useredited['account_type']=='u')?'User':($useredited['account_type']=='p')?'Public User':'Group';
	$public_checked = ($useredited['account_type']=='p')?'Checked':'Unchecked';
	$account_lastname= $useredited['account_lastname'];
	$addr1= $useredited['addr1'];
	$addr2= $useredited['addr2'];
	$city= $useredited['city'];
	$state= $useredited['state'];
	$postal_code= $useredited['postal_code'];
	$country= $useredited['country'];
	$account_email= $useredited['account_email'];
	$account_phone= $useredited['account_phone'];
    
	$permission_info = array('uid'=>'U'.$user_id,'shared_with'=>'U'.$id);
	
	if($user_id==1 || $user_id == $useredited['created_by']){
		
		$has_permission = has_permission($permission_info, $db,$user_id);   
		if($has_permission){
		$filter = $has_permission;
		$inherit_checked = 'checked';

		}
		$inherit_disabled = 'enbled';
		$filter_disabled = 'enabled';
		}
	else {
		$inherit_disabled = 'disabled';
		$filter_disabled = 'disabled';
	}
	
#And finally, discover more authentication credentials

$s3ql=array('user_id'=>'1','db'=>$db);#to be changed once any user can query this collections, his own items
$s3ql['from']='authentication';
$s3ql['where']['user_id']=$id;
list($valid,$authentications)=apiQuery($s3ql);

		
#Discover also all authorities

$s3ql=array('user_id'=>'1','db'=>$db);#TO BE CHANGED ONCE ALL USERS CAN SEE AUTHORITIES
$s3ql['from']='authority';
list($valid,$authorities)=apiQuery($s3ql);
$json_auth='authorities='.json_encode($authorities).'';

#And the protocols because authroties don't have a protocol value, only an id
$s3ql=array('user_id'=>'1','db'=>$db);
$s3ql['from']='protocol';

list($valid,$protocols)=apiQuery($s3ql);
$json_prot='protocols='.json_encode($protocols).'';
$newProt=array();
if(is_array($protocols)){
foreach ($protocols as $prot) {
	$newProt[$prot['item_id']]=$prot['label'];
}
}
if(!$key)$key=get_user_key($user_id, $db);


#now encode the authorities for the javascript that is coming
//
//if(is_array($authorities)){
//foreach ($authorities as $authority) {
//	$json_auth_label[] = $authority['DisplayLabel'];
//	$json_auth_url[] = $authority['URI'];
//	$json_auth_protocol[] = $authority['Protocols'];
//	$json_auth_template[] = $authority['Template'];
//}
//$json_auth='authorities='.json_encode($json_auth).'';
//
//}

?>
<body>
<script type="text/javascript" src="../js/wz_tooltip.js"></script>
<script type="text/javascript" src="../js/s3dbcall.js"></script>
<script language="javascript">



function authentication_added(ans, authentication_id)
{

if(ans[0].error_code=='0'){
var tab = document.getElementById('other_authentications');
var tr = document.createElement('tr');
tr.setAttribute('bgcolor','#DDF0FF');
var td = document.createElement('td');
td.setAttribute('colspan','2');
td.innerHTML = authentication_id;

tr.appendChild(td);

var td = document.createElement('td');
td.setAttribute('colspan','2');
td.innerHTML = '<a href="edituser.php?id='+<?php echo $_REQUEST['id']; ?>+'&authentication_id='+authentication_id+'&action=delete_auth">Delete</a>';

tr.appendChild(td);

tab.appendChild(tr);

setTimeout('document.getElementById(\'auth_error_message\').innerHTML=""',5000);
}
else {
	
	var eD=document.getElementById('auth_error_message').innerHTML=ans[0].message;

}
}

function submitAuthentication()
{
	if(document.getElementById('authorities').value && document.getElementById('username').value)	
	{
		var authority =  document.getElementById('authorities').value;
		var username = 	document.getElementById('username').value;
		var authentication_id =  authority+':'+username;
		var key= <?php echo "'".$key."'"; ?>
		
		
		//insert it and display it in the end of the line
		var s3ql = <?php echo "'".S3DB_URI_BASE."'"; ?>+'/api.php?key='+key+'&query=<S3QL><insert>authentication</insert><where><user_id>U'+<?php echo $_REQUEST['id'];?>+'</user_id><authentication_id>'+authentication_id+'</authentication_id></where></S3QL>';
		
		//console.log(s3ql)
		
		s3dbcall(s3ql,"authentication_added(ans, '"+authentication_id+"')");
		
	
	}
	
}


</script>
<form method="POST" action="<?php echo $action['edituser']; ?>">
<?php



#echo $action['edituser'];exit;
#echo '<body onload="load()">';
#echo '<script type="text/javascript" src="../js/login.js"></script>
#<script type="text/javascript" src="../js/wz_tooltip.js"></script>';
#echo '<form method="POST" action="'.$action['edituser'].'">';

?>

<table class="top" align="center">
	<tr><td>
		<table class="insidecontents" align="center" width="80%">
			<tr><td class="message"><br /><?php echo $message ?></td></tr>
			<tr align="center"><td colspan="2" class="current_stage"><?php echo $current_stage ?></td></tr>
		</table>
	</td></tr>
</table>
<!-- END top -->
<!-- BEGIN user_info_edit -->
<table class="middle" width="100%"  align="center">
	<tr><td>
		<table class="insidecontents" width="80%"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td colspan="4" align="center">Edit User Account</td></tr>
			<tr class="odd">
				<td class="info">Login ID<sup class="required"><?php echo $loginid_required ?></sup></td>
				<td class="info"><input name="account_lid" value="<?php echo $account_lid ?>">&nbsp;<input type="hidden" name="account_addr_id" value="<?php echo $account_addr_id ?>"></td>
				<td class="info">Public</td>
				
				<td class="info"><input type="checkbox" name="Public" value="Public" <?php echo $public_checked?>> User cannot change password</td>
			</tr>
			<tr class="even">
				<td class="info">Real Name<sup class="required"><?php echo $uname_required ?></sup></td>
				<td class="info"><input name="account_uname" value="<?php echo $account_uname ?>">&nbsp;</td>
				<td class="info">Account Type</td>
				<td ass="info"><?php echo $account_type ?></td>
			</tr>
			<tr class="odd">
				<td class="info">Password<sup class="required"><?php echo $password_required ?></sup></td>
				<td class="info"><input type="password" name="account_pwd" value="">&nbsp;</td>
				<td class="info">Re-type Password<sup class="required"><?php echo $password2_required ?></sup></td>
				<td class="info"><input type="password" name="account_pwd_2" value="">&nbsp;</td>
			</tr>
			<tr class="even">
				<td class="info">Groups</td>
				<td class="info"><select name="account_groups[]" multiple><?php echo $group_select_list ?><option value="-100"></option></select></td>
				<td class="info">Email<sup><?php echo $email_warn ?></sup></td>
				<td class="info"><input name="account_email" value="<?php echo $account_email ?>">&nbsp;</td>
			</tr>
			
			<tr class="odd">
				<td class="info" colspan="4">Inherit permission<input type="checkbox" name="inherit_permission" <?php echo $inherit_checked.' '.$inherit_disabled.' '; ?>>&nbsp;&nbsp;&nbsp;&nbsp;Filter&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="filter" value="<?php echo $filter ?>" <?php echo $filter_disabled;?>></td>
				
			</tr>
		</table>
	</td></tr>	
	 <tr><td></td></tr>
	 <tr><td>
		<table id="other_authentications" class="insidecontents" width="80%"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td colspan="4" align="center">Other authentication credentials</td></tr>
			<?php
			if(is_array($authentications))
			foreach ($authentications as $auth_info) {
				echo "<tr bgcolor='#DDF0FF'><td colspan='2'>".$auth_info['authentication_id']."</td><td colspan='2'><a href='".$action['edituser']."&authentication_id=".$auth_info['authentication_id']."&action=delete_auth'>Delete</a></td></tr>";
			}

			?>
		</table>
		<table class="insidecontents" width="80%"  align="center" border="0">
			<tr>
			<td colspan="4" id="auth_error_message" color="red"></td>
			<td><BR></td>
			<td><BR></td>
			</tr>
			<tr bgcolor="#80BBFF"><td colspan="4" align="center">New authentication method</td>
				
			</tr>
			
			<tr>
				<td bgcolor='#DDF0FF'>
					<div style="text-decoration: underline; font-style: italic" onmouseover="Tip('Authority is the domain where you wish to be authenticated, for example, google.')" onmouseout="UnTip()">Authority</div>
					
						<?php
						if(is_array($authorities)){
							$auth_select = '<select id="authorities" name="authorities">';
							foreach ($authorities as $author) {
								 if($newProt[$author['Protocols']]) $protName =  $newProt[$author['Protocols']];
								 $auth_select .= "<option value='".$protName.':'.$author['DisplayLabel']."'>".$author['DisplayLabel'].'</option>';
							}
							 $auth_select .= '</select>';
							echo $auth_select;
							}

						?>
					
				</td>
				<td bgcolor='#DDF0FF'>
					<div style="text-decoration: underline; font-style: italic">Email or Username</div>
					<input type="text" name="username" id="username">
				</td>
				<td bgcolor='#DDF0FF'>
					
					<input type="button" value="Add" onClick="submitAuthentication()">
				</td>
				
				
			</tr>
			<tr>
				
			</tr>
		</table>
	</td></tr>
	
</table>


<!-- END user_info_edit -->
<table class="bottom" width="100%"  align="center">
	<tr><td>
	<table class="insidecontents" width="80%"  align="center">
	<tr><td align="left"><input type="submit" name="submit" value="Update User Account"></form>&nbsp;&nbsp;&nbsp;&nbsp;
	<?php #if ($user_info['account_group']=='a')
	#echo '<input type="button" name="back" value="User Account List" onClick="window.location='.$action['listusers'].'"><br /><br />';
	?>
	</td></tr>
	</table>
	</td></tr>

</table>

<?php
include(S3DB_SERVER_ROOT.'/footer.php');
?>