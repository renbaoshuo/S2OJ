<?php

Auth::check() || redirectToLogin();

function handleMsgPost() {
	if (!isset($_POST['message'])) {
		return 'fail';
	}
	if (0 > strlen($_POST['message']) || strlen($_POST['message']) > 65535) {
		return 'fail';
	}
	$receiver = UOJRequest::user(UOJRequest::POST, 'receiver');
	if (!$receiver) {
		return 'fail';
	}
	$message = $_POST['message'];

	if ($receiver['username'] === Auth::id()) {
		return 'fail';
	}

	DB::insert([
		"insert into user_msg",
		"(sender, receiver, message, send_time)",
		"values", DB::tuple([Auth::id(), $receiver['username'], $message, DB::now()])
	]);
	return "ok";
}

function getConversations() {
	$username = Auth::id();

	$res = DB::selectAll([
		"select * from user_msg",
		"where", DB::lor([
			"sender" => $username,
			"receiver" => $username,
		]),
		"order by send_time DESC"
	]);
	$ret = [];
	foreach ($res as $msg) {
		if ($msg['sender'] !== $username) {
			if (isset($ret[$msg['sender']])) {
				$ret[$msg['sender']][1] |= ($msg['read_time'] == null);
				continue;
			}

			$ret[$msg['sender']] = [$msg['send_time'], ($msg['read_time'] == null), $msg['message']];
		} else {
			if (isset($ret[$msg['receiver']])) continue;

			$ret[$msg['receiver']] = [$msg['send_time'], 0, $msg['message']];
		}
	}
	$res = [];
	foreach ($ret as $name => $con) {
		$user = UOJUser::query($name);
		$res[] = [
			$con[0],
			$con[1],
			$name,
			HTML::avatar_addr($user, 128),
			UOJUser::getRealname($user),
			UOJUser::getUserColor($user),
			$con[2],
		];
	}

	usort($res, function ($a, $b) {
		return -strcmp($a[0], $b[0]);
	});

	return json_encode($res);
}

function getHistory() {
	$username = Auth::id();
	$receiver = UOJRequest::user(UOJRequest::GET, 'conversationName');
	$page_num = UOJRequest::uint(UOJRequest::GET, 'pageNumber');
	if (!$receiver || $receiver['username'] === $username) {
		return '[]';
	}
	if (!$page_num) { // false, null, or zero
		return '[]';
	}

	DB::update([
		"update user_msg",
		"set", ["read_time" => DB::now()],
		"where", [
			"sender" => $receiver['username'],
			"receiver" => $username,
			"read_time" => null,
		]
	]);

	$result = DB::selectAll([
		"select * from user_msg",
		"where", DB::lor([
			DB::land([
				"sender" => $username,
				"receiver" => $receiver['username']
			]),
			DB::land([
				"sender" => $receiver['username'],
				"receiver" => $username
			])
		]),
		"order by send_time DESC", DB::limit(($page_num - 1) * 10, 11)
	]);
	$ret = [];
	foreach ($result as $msg) {
		$ret[] = [
			$msg['message'],
			$msg['send_time'],
			$msg['read_time'],
			$msg['id'],
			($msg['sender'] === $username),
		];
	}
	return json_encode($ret);
}

if (isset($_POST['user_msg'])) {
	die(handleMsgPost());
} elseif (isset($_GET['getConversations'])) {
	die(getConversations());
} elseif (isset($_GET['getHistory'])) {
	die(getHistory());
}
?>

<?php echoUOJPageHeader('私信') ?>

<h1>私信</h1>

<style>
	@media (min-width: 768px) {
		.chat-container {
			height: calc(100ch - 10rem);
		}
	}
</style>

<div class="card overflow-hidden-md chat-container">
	<div class="row gx-0 flex-grow-1 h-100">
		<div class="col-md-3 border-end h-100">
			<div class="list-group list-group-flush h-100 overflow-auto" id="conversations"></div>
		</div>

		<div class="col-md-9 h-100" id="history" style="display: none">
			<div class="card h-100 border-0 rounded-0">
				<div class="card-header">
					<button id="goBack" class="btn-close position-absolute" aria-label="关闭对话"></button>
					<div id="conversation-name" class="text-center"></div>
				</div>
				<div class="card-body overflow-auto" id="history-list-container">
					<div id="history-list" style="min-height: 200px;"></div>
				</div>
				<div class="card-footer bg-transparent">
					<ul class="pagination pagination-sm justify-content-between mt-1">
						<li class="page-item">
							<button class="page-link rounded" id="pageLeft">
								<i class="bi bi-chevron-left"></i>
								更早的消息
							</button>
						</li>
						<li class="page-item">
							<button class="page-link rounded" id="pageRight">
								更新的消息
								<i class="bi bi-chevron-right"></i>
							</button>
						</li>
					</ul>
					<form id="form-message" class="">
						<div id="form-group-message" class="flex-grow-1">
							<textarea id="input-message" class="form-control" style="resize: none;" data-no-autosize></textarea>
							<span id="help-message" class="help-block"></span>
						</div>
						<div class="text-end mt-2">
							<span class="text-muted small">按 Ctrl+Enter 键发送</span>
							<button type="submit" id="message-submit" class="btn btn-primary flex-shrink-0 ms-3">
								发送
								<i class="bi bi-send"></i>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var REFRESH_INTERVAL = 30 * 1000;

	$(document).ready(function() {
		$.ajaxSetup({
			async: false
		});

		refreshConversations();
		setInterval(refreshConversations, REFRESH_INTERVAL);

		<?php if (isset($_GET['enter'])) : ?>
			enterConversation(<?= json_encode($_GET['enter']) ?>);
		<?php endif ?>
	});
</script>

<script type="text/javascript">
	<?php $enter_user = UOJRequest::user(UOJRequest::GET, 'enter'); ?>
	var conversations = {};
	var intervalId = 0;
	var user_avatar = '<?= HTML::avatar_addr(Auth::user(), 80) ?>';
	var enter_user = ['<?= $enter_user['username'] ?>', '<?= UOJUser::getRealname($enter_user) ?>', '<?= UOJUser::getUserColor($enter_user) ?>'];

	function formatDate(date) {
		var d = new Date(date),
			month = '' + (d.getMonth() + 1),
			day = '' + d.getDate(),
			year = d.getFullYear();

		if (month.length < 2)
			month = '0' + month;
		if (day.length < 2)
			day = '0' + day;

		return [year, month, day].join('-');
	}

	function formatTime(date) {
		var d = new Date(date),
			hour = '' + d.getHours(),
			minute = '' + d.getMinutes();

		if (hour.length < 2)
			hour = '0' + hour;
		if (minute.length < 2)
			minute = '0' + minute;

		return [hour, minute].join(':');
	}

	function addButton(conversationName, send_time, type, avatar_addr, realname, color, last_message) {
		var now = new Date();
		var time = new Date(send_time);
		var timeStr = formatDate(send_time);

		if (formatDate(now) === timeStr) {
			timeStr = formatTime(send_time);
		}

		$("#conversations").append(
			'<div class="list-group-item list-group-item-action p-2 d-flex ' + (type ? 'list-group-item-warning' : '') + '" style="cursor: pointer; user-select: none;" ' +
			'onclick="enterConversation(\'' + conversationName + '\')">' +
			'<div class="flex-shrink-0 me-3">' +
			'<img class="rounded" width="56" height="56" src="' + avatar_addr + '" />' +
			'</div>' +
			'<div class="flex-grow-1 overflow-hidden">' +
			'<div class="d-flex justify-content-between">' +
			getUserSpan(conversationName, '', color) +
			'<span class="float-end text-muted small flex-shrink-0 lh-lg">' +
			timeStr +
			'</span>' +
			'</div>' +
			'<div class="text-muted text-nowrap text-truncate">' +
			htmlspecialchars(last_message) +
			'</div>' +
			'</div>' +
			'</div>'
		);
	}

	function addBubble(content, send_time, read_time, msgId, conversation, page, type) {
		$("#history-list").append(
			'<div class="d-flex align-items-end mt-3" style="' + (type ? 'margin-left:20%;' : 'margin-right:20%;') + '">' +
			(type ? '' : '<img class="flex-shrink-0 me-2 rounded" width="32" height="32" src="' + conversations[conversation][1] + '" style="user-select: none;" />') +
			'<div class="card flex-grow-1">' +
			'<div class="card-body px-3 py-2" style="white-space:pre-wrap">' +
			htmlspecialchars(content) +
			'</div>' +
			'<div class="card-footer text-muted px-3 py-1">' +
			'<span class="small">' +
			'<i class="bi bi-clock"></i> ' + send_time +
			'</span>' +
			(read_time == null ?
				'<span class="float-end" data-bs-toggle="tooltip" data-bs-title="未读"><i class="bi bi-check2"></i></span>' :
				'<span class="float-end" data-bs-toggle="tooltip" data-bs-title="' + read_time + '"><i class="bi bi-check2-all"></i></span>') +
			'</div>' +
			'</div>' +
			(type ? '<img class="flex-shrink-0 ms-2 rounded" width="32" height="32" src="' + user_avatar + '" style="user-select: none;" />' : '') +
			'</div>'
		);
	}

	function submitMessagePost(conversationName) {
		if ($('#input-message').val().length == 0 || $('#input-message').val().length >= 65536) {
			$('#help-message').text('私信长度必须在1~65535之间。');
			$('#form-group-message').addClass('has-error');
			return;
		}
		$('#help-message').text('');
		$('#form-group-message').removeClass('has-error');

		$.post('/user_msg', {
			user_msg: 1,
			receiver: conversationName,
			message: $('#input-message').val()
		}, function(msg) {
			$('#input-message').val("");
		});
	}

	function refreshHistory(conversation, page) {
		$("#history-list").empty();
		var ret = false;
		$('#conversation-name').html(getUserLink(conversation, conversation == enter_user[0] ? enter_user[1] : conversations[conversation][2], conversation == enter_user[0] ? enter_user[2] : conversations[conversation][3]));
		$('#pageShow').text("第" + page.toString() + "页");
		$.get('/user_msg', {
			getHistory: '',
			conversationName: conversation,
			pageNumber: page
		}, function(msg) {
			var result = JSON.parse(msg);
			var cnt = 0,
				flag = 0,
				F = 0;
			if (result.length == 11) flag = 1, F = 1;
			result.reverse();
			for (msg in result) {
				if (flag) {
					flag = 0;
					continue;
				}
				var message = result[msg];
				addBubble(message[0], message[1], message[2], message[3], conversation, page, message[4]);
				if ((++cnt) + 1 == result.length && F) {
					break;
				}
			}

			if (result.length == 11) {
				ret = true;
			}

			bootstrap.Tooltip.jQueryInterface.call($('#history-list [data-bs-toggle="tooltip"]'), {
				container: $('#history-list'),
			});
		});
		return ret;
	}

	function refreshConversations() {
		$("#conversations").empty();
		$.get('/user_msg', {
			getConversations: 1
		}, function(msg) {
			var result = JSON.parse(msg);
			for (i in result) {
				var conversation = result[i];
				if (conversation[1] == 1) {
					addButton(conversation[2], conversation[0], conversation[1], conversation[3], conversation[4], conversation[5], conversation[6]);
				}
				conversations[conversation[2]] = [conversation[0], conversation[3], conversation[4], conversation[5]];
			}
			for (i in result) {
				var conversation = result[i];
				if (conversation[1] == 0) {
					addButton(conversation[2], conversation[0], conversation[1], conversation[3], conversation[4], conversation[5], conversation[6]);
				}
			}
		});
	}

	function enterConversation(conversationName) {
		var slideTime = 300;
		var page = 1;
		var changeAble = refreshHistory(conversationName, page);
		clearInterval(intervalId);
		intervalId = setInterval(function() {
			changeAble = refreshHistory(conversationName, page);
		}, REFRESH_INTERVAL);
		$('#history').show();
		$('#conversations').addClass('d-none d-md-block')
		$("#history-list-container").scrollTop($("#history-list").height());
		$('#input-message').unbind('keydown').keydown(function(e) {
			if (e.keyCode == 13 && e.ctrlKey) {
				$('#message-submit').click();
			}
		});
		$('#form-message').unbind("submit").submit(function() {
			submitMessagePost(conversationName);
			page = 1;
			changeAble = refreshHistory(conversationName, page);
			refreshConversations();
			$("#history-list-container").scrollTop($("#history-list").height());
			return false;
		});
		$('#goBack').unbind("click").click(function() {
			clearInterval(intervalId);
			refreshConversations();
			$("#history").hide();
			$("#conversations").removeClass('d-none d-md-block');
			return;
		});
		$('#pageLeft').unbind("click").click(function() {
			if (changeAble) {
				page++;
				clearInterval(intervalId);
			}
			changeAble = refreshHistory(conversationName, page);
			return false;
		});
		$('#pageRight').unbind("click").click(function() {
			if (page > 1) {
				page--;
				clearInterval(intervalId);
			}
			changeAble = refreshHistory(conversationName, page);
			return false;
		});
	}
</script>

<?php echoUOJPageFooter() ?>
