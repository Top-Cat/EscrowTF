var extra = {};
var obj = {login: true};
$('form').submit(function(e) {
	e.preventDefault();

	obj.ekey = $(this).find('#escrowKey').val();

	if (localStorage.getItem('done')) return init2();

	obj.user = $(this).find('#steamAccountName').val();
	obj.pass = $(this).find('#steamPassword').val();
	if (obj.ekey.length > 0 && obj.user.length == 0 && obj.pass.length == 0) {
		return alreadySetup();
	}

	for (x in extra) {
		obj[x] = extra[x].val();
	}

	$.post('', obj, function(r) {
		$('#extraLogin').empty();

		delete extra['2fa'];
		delete obj['2fa'];

		if (r.captcha_needed) {
			obj.gid = r.captcha_gid;
			$('#extraLogin').append($('<p />', {text: 'Type these characters below'}));
			$('#extraLogin').append($('<img />', {src: 'https://steamcommunity.com/login/rendercaptcha/?gid=' + r.captcha_gid}));
			$('#extraLogin').append(extra['captcha'] = $('<input />'));
		} else if (r.emailauth_needed) {
			obj.emailsteamid = r.emailsteamid;
			$('#extraLogin').append($('<p />', {text: 'Please enter your 2FA code'}));
			$('#extraLogin').append(extra['2fa'] = $('<input />'));
		} else if (r.phone_needed) {
			$('#extraLogin').append($('<p />')
				.append('Please ')
				.append($('<a />', {text: 'click here', href: 'https://store.steampowered.com/phone/manage', target: '_blank'}))
				.append(' to add a phone number to your steam account and try again!')
			);
		} else if (r.requires_twofactor) {
			alreadySetup();
		} else if (r.sms_needed) {
			localStorage.setItem('steamauth', r.authdata);
			$('#code i').text(r.revocation_code);

			obj.authdata = r.authdata;
			$('#extraLogin').append($('<p />', {text: 'Please enter your sms verification code'}));
			$('#extraLogin').append(extra['sms'] = $('<input />'));
		} else if (r.success) {
			localStorage.setItem('done', true);
			init2();
		}
	}, 'json');
});
function alreadySetup() {
	$('#extraLogin').empty();
	$('#extraLogin').append($('<p />')
		.append('2FA already set up on this account?<br/><br />Upload your backed up data to restore authenticator')
	).append($('<input />', {type: 'file', id: 'test'}).change(function(e) {
		var file = $(this)[0].files[0];
		var fileReader = new FileReader();
		fileReader.onload = function(fileLoadedEvent) {
			localStorage.setItem('steamauth', fileLoadedEvent.target.result);
			localStorage.setItem('done', true);
			init2();
		}
		fileReader.readAsText(file, "UTF-8");
	}));
}
function init() {
	if (!localStorage.getItem('done')) return;

	$('#steamlogin').hide();
	$('#cancel').css('display', 'inline-block').click(function() {
		if (confirm('Are you sure you want to destroy local encrypted data?')) {
			localStorage.removeItem('done');
			location.reload();
		}
	});
}
function init2() {
	$('form').hide();
	$('#code').show();
	$('#confwrap').show();

	steamData = localStorage.getItem('steamauth');
	escrowKey = $('#escrowKey').val();

	$('#reload').click(Confirmation.get);
	$('#revoke').click(revokeCode);
	$('#save')
		.attr('href', window.URL.createObjectURL(new Blob([steamData], {type:'text/plain'})))
		.attr('download', 'escrow.bak');

	getCode();
	Confirmation.get();
}
function revokeCode() {
	if (!confirm('Are you sure you want to remove this authenticator from your account?')) return;

	obj = {
		action: 'revoke_code',
		authdata: steamData,
		ekey: escrowKey
	};

	$.post('', obj, function(r) {
		localStorage.removeItem('done');
		location.reload();
	}, 'json');
}
function checkResult(r) {
	if (r.badcrypt) {
		location.reload();
	}
}
function getCode() {
	obj = {
		action: 'get_code',
		authdata: steamData,
		ekey: escrowKey
	};

	$.post('', obj, function(r) {
		checkResult(r);

		$('#code span').text(r.code);
		$('#code i').text(r.rcode);
		var rem = (30 - (r.time % 30)) * 1000;
		$('#counter').width(rem / 100).animate({width: 0}, rem, 'linear', getCode);
	}, 'json');
}
var Confirmation = function(conf) {
	this.conf = conf;
	$('#conf').append(this.elem = $('<div />')
		.append($('<input />', {class: 'btn', type: 'button', value: 'Cancel'}).click($.proxy(this.cancel, this)))
		.append($('<input />', {class: 'btn', type: 'button', value: 'Accept'}).click($.proxy(this.accept, this)))
		.append($('<span />', {text: this.conf.desc}))
	);
};
Confirmation.prototype.cancel = function() {
	this.callback('cancel');
};
Confirmation.prototype.accept = function() {
	this.callback('allow');
};
Confirmation.prototype.callback = function(op) {
	obj = {
		action: 'doconf',
		op: op,
		id: this.conf.id,
		key: this.conf.key,
		authdata: steamData,
		ekey: escrowKey
	};

	$.post('', obj, Confirmation.ajaxResponse, 'json');
};
Confirmation.ajaxResponse = function(r) {
	checkResult(r);

	if (r.authdata) {
		localStorage.setItem('steamauth', r.authdata);
		steamData = r.authdata;
	}

	$('#conf').empty();
	for (x in r.conf) {
		new Confirmation(r.conf[x]);
	}
};
Confirmation.get = function() {
	obj = {
		action: 'get_conf',
		authdata: steamData,
		ekey: escrowKey
	};

	$.post('', obj, Confirmation.ajaxResponse, 'json');
}
init();
