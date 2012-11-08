<?php
	#proxy user changes one user session with another
	#Helena F Deus <helenadeus@gmail.com>
	include('adminheader.php');
	if(!empty($_GET['id'])) {
		$user_proxied = get_user_info($_GET['id']);
		$db = $_SESSION['db'];
		$_SESSION = '';
		$_SESSION['user']['account_id'] = $user_proxied['account_id'];	
		$_SESSION['user']['account_lid'] = $user_proxied['account_lid'];	
		$_SESSION['user']['account_uname'] = $user_proxied['account_uname'];	
		$_SESSION['user']['account_group'] = $user_proxied['account_group'];	
		$_SESSION['user']['account_type'] = $user_proxied['account_type'];
		$_SESSION['db'] = $db; 
		Header('Location: ../home.php');
	} else {
		Header('Location: user.php');
	}
?>
