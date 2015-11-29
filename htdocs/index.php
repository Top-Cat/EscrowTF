<?php

spl_autoload_register(function ($class) {
	include '../objects/' . str_replace('_', '/', $class) . '.php';
	include '../libs/' . str_replace('_', '/', $class) . '.php';
});

if (sizeof($_POST) > 0) {
	Post::run();
}

?><!DOCTYPE html>
<html lang="en">
	<head>
		<title>Escrow.TF - The worst idea I ever had</title>
		<link rel="stylesheet" href="/content/main.css">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	</head>
	<body>
		<div id="notsteam">Warning: This is not owned by valve. While I'm not harvesting login data, I totally could be.</div>
		<form>
			<div id="extraLogin"></div>
			<h1>Sign In</h1>
			<div id="steamlogin">
				<p>To an existing Steam account</p>
				<label for="steamAccountName">Steam username</label>
				<input id="steamAccountName" name="username" />
				<label for="steamPassword">Password</label>
				<input id="steamPassword" name="password" type="password" />
			</div>
			<p>All data stored on your device by this site will be secured with this key, preventing unwanted access.</p>
			<label for="escrowKey">Escrow Key</label>
			<input id="escrowKey" name="key" type="password" />
			<input class="btn" type="submit" value="Sign In" width="104" height="25" border="0" />
			<input class="btn" id="cancel" type="button" value="Sign Out" width="104" height="25" border="0" />
		</form>
		<div id="code">
			<span></span>
			<i></i>
			<div id="counter"></div>
		</div>
		<div id="confwrap">
			<div id="reload" class="icon" title="Reload">
				<i class="fa fa-refresh"></i>
			</div>
			<a id="save" class="icon" title="Backup Data">
				<i class="fa fa-floppy-o"></i>
			</a>
			<div id="revoke" class="icon" title="Revoke">
				<i class="fa fa-chain-broken"></i>
			</div>
			<h2>Confirmations</h2>
			<div id="conf"></div>
		</div>
		<script src="/content/main.js"></script>
	</body>
</html>
