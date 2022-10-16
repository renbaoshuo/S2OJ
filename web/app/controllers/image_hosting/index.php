<?php
	use Gregwar\Captcha\PhraseBuilder;
	use Gregwar\Captcha\CaptchaBuilder;

	requirePHPLib('form');
	requireLib('bootstrap5');

	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	$limit = $myUser['images_size_limit'];
	$_result = DB::selectFirst("SELECT SUM(size), count(*) FROM `users_images` WHERE uploader = '{$myUser['username']}'");
	$used = $_result["SUM(size)"];
	$count = $_result["count(*)"];

	function throwError($msg) {
		die(json_encode(['status' => 'error', 'message' => $msg]));
	}

	$allowedTypes = [IMAGETYPE_PNG, IMAGETYPE_JPEG];
	if ($_POST['image_upload_file_submit'] == 'submit') {
		header('Content-Type: application/json');

		if (!crsf_check()) {
			throwError('expired');
		}

		if (!isset($_SESSION['phrase']) || !PhraseBuilder::comparePhrases($_SESSION['phrase'], $_POST['captcha'])) {
			throwError("bad_captcha");
		}

		if ($_FILES["image_upload_file"]["error"] > 0) {
			throwError($_FILES["image_upload_file"]["error"]);
		}

		if ($_FILES["image_upload_file"]["size"] > 5242880) { // 5 MB
			throwError('too_large');
		}

		if ($used + $_FILES["image_upload_file"]["size"] > $limit) {
			throwError('storage_limit_exceeded');
		}

		$size = getimagesize($_FILES['image_upload_file']['tmp_name']);

		if (!$size || !in_array($size[2], $allowedTypes)) {
			throwError('not_a_image');
		}

		list($width, $height, $type) = $size;
		$hash = hash_file("sha256", $_FILES['image_upload_file']['tmp_name']);
		$scale = ceil($height / 600.0);

		$watermark_text = UOJConfig::$data['profile']['oj-name-short'];
		if (isSuperUser($myUser) && $_POST['watermark'] == 'no_watermark') {
			$watermark_text = "";
			$hash .= "__no_watermark";
		} elseif ($_POST['watermark'] == 'site_shortname_and_username') {
			$watermark_text .= ' @'.Auth::id();
			$hash .= "__id_".Auth::id();
		}

		$existing_image = DB::selectFirst("SELECT * FROM users_images WHERE `hash` = '$hash'");

		if ($existing_image) {
			die(json_encode(['status' => 'success', 'path' => $existing_image['path']]));
		}

		$image = new Imagick($_FILES["image_upload_file"]["tmp_name"]);
		$draw = new ImagickDraw();
		$draw->setFont(UOJContext::documentRoot().'/fonts/roboto-mono/RobotoMono-Bold.ttf');
		$draw->setFontSize($scale * 14);
		$draw->setGravity(Imagick::GRAVITY_SOUTHEAST);
		$draw->setFillColor("rgba(100,100,100,0.5)");
		$image->annotateImage($draw, 15, 10, 0, $watermark_text);
		$draw->setFillColor("rgba(255,255,255,0.65)");
		$image->annotateImage($draw, 15 + $scale, 10 + $scale, 0, $watermark_text);
		$image->setImageFormat('png');
		$image->writeImage();

		if (($size = filesize($_FILES["image_upload_file"]["tmp_name"])) > 5242880) { // 5 MB
			throwError('too_large');
		}

		$filename = uojRandAvaiableFileName('/image_hosting/', 10, '.png');
		if (!move_uploaded_file($_FILES["image_upload_file"]["tmp_name"], UOJContext::storagePath().$filename)) {
			throwError('unknown error');
		}

		DB::insert("INSERT INTO users_images (`path`, uploader, width, height, upload_time, size, `hash`) VALUES ('$filename', '{$myUser['username']}', $width, $height, now(), {$_FILES["image_upload_file"]["size"]}, '$hash')");

		die(json_encode(['status' => 'success', 'path' => $filename]));
	} elseif ($_POST['image_delete_submit'] == 'submit') {
		crsf_defend();

		$id = $_POST['image_delete_id'];
		if (!validateUInt($id)) {
			becomeMsgPage('ID 不合法。<a href="'.UOJContext::requestURI().'">返回</a>');
		} else {
			$result = DB::selectFirst("SELECT * from users_images WHERE id = $id");
			if (!$result) {
				becomeMsgPage('图片不存在。<a href="'.UOJContext::requestURI().'">返回</a>');
			} else {
				unlink(UOJContext::storagePath().$result['path']);
				DB::delete("DELETE FROM users_images WHERE id = $id");

				header("Location: ". UOJContext::requestURI());
				die();
			}
		}
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('image hosting')) ?>

<style>
.drop {
	display: flex;
    align-items: center;
    flex-direction: column;
    justify-content: center;
    align-self: center;
    flex-grow: 0 !important;
    width: 9em;
    height: 8.75em;
    user-select: none;
    cursor: pointer;
    margin-left: 0;
    background: #fafafa;
    border: 1px solid #e8e8e8;
    box-sizing: border-box;
    border-radius: 4px;
}

.drop:hover {
    border-color: #89d1f5;
}
</style>

<h1 class="h2">
	<?= UOJLocale::get('image hosting') ?>
</h1>

<div class="card card-default">
<div class="card-body">
	<form class="row m-0" id="image-upload-form" method="post" enctype="multipart/form-data">
		<div class="col-12 col-md-3 col-lg-3 order-1 drop mx-auto mx-md-0" id="image-upload-form-drop">
			<svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="56" class="mb-2">
				<g>
					<path fill="#3498db" d="M424.49 120.48a12 12 0 0 0-17 0L272 256l-39.51-39.52a12 12 0 0 0-17 0L160 272v48h352V208zM64 336V128H48a48 48 0 0 0-48 48v256a48 48 0 0 0 48 48h384a48 48 0 0 0 48-48v-16H144a80.09 80.09 0 0 1-80-80z"></path>
					<path fill="#89d1f5" d="M528 32H144a48 48 0 0 0-48 48v256a48 48 0 0 0 48 48h384a48 48 0 0 0 48-48V80a48 48 0 0 0-48-48zM208 80a48 48 0 1 1-48 48 48 48 0 0 1 48-48zm304 240H160v-48l55.52-55.52a12 12 0 0 1 17 0L272 256l135.52-135.52a12 12 0 0 1 17 0L512 208z"></path>
				</g>
			</svg>
			<span id="select-image-text" class="small">点击此处选择图片</span>
		</div>
		<input id="image_upload_file" name="image_upload_file" type="file" accept="image/*" style="display: none;" />
		<div class="modal fade" id="image-upload-modal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h1 class="modal-title fs-5" id="exampleModalLabel">上传图片</h1>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="mb-3">
							您确定要上传图片吗？
						</div>
						<div class="mb-3" id="modal-file-info"></div>
						<div class="input-group">
							<input type="text" class="form-control" id="input-captcha" name="captcha" placeholder="<?= UOJLocale::get('enter verification code') ?>" maxlength="20" />
							<span class="input-group-text p-0 overflow-hidden rounded-0" style="border-bottom-right-radius: var(--bs-border-radius) !important">
								<img id="captcha" class="col w-100 h-100" src="/captcha">
							</span>
						</div>
						<div class="mt-3" id="modal-help-message" style="display: none"></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancel-upload">取消</button>
						<button type="submit" class="btn btn-primary">确定</button>
					</div>
				</div>
			</div>
		</div>
		<div class="col-12 col-md-4 col-lg-2 order-2 mt-3 mt-md-0 ms-md-2">
			<h2 class="h4">水印</h2>
			<?php if (isSuperUser($myUser)): ?>
			<div class="form-check d-inline-block d-md-block me-2">
				<input class="form-check-input" type="radio" name="watermark" id="watermark-no_watermark" data-value="no_watermark">
				<label class="form-check-label" for="watermark-no_watermark">
					无水印
				</label>
			</div>
			<?php endif ?>
			<div class="form-check d-inline-block d-md-block me-2">
				<input class="form-check-input" type="radio" name="watermark" id="watermark-site_shortname" data-value="site_shortname" checked>
				<label class="form-check-label" for="watermark-site_shortname">
					<?= UOJConfig::$data['profile']['oj-name-short'] ?>
				</label>
			</div>
			<div class="form-check d-inline-block d-md-block me-2">
				<input class="form-check-input" type="radio" name="watermark" id="watermark-site_shortname_and_username" data-value="site_shortname_and_username">
				<label class="form-check-label" for="watermark-site_shortname_and_username">
					<?= UOJConfig::$data['profile']['oj-name-short'] ?> @<?= Auth::id() ?>
				</label>
			</div>
		</div>
		<div class="col order-3 order-md-4 order-lg-3 mt-3 mt-lg-0 ms-lg-2">
			<h2 class="h4">上传须知</h2>
			<ul>
				<li>上传的图片必须符合法律与社会道德；</li>
				<li>图床仅供 S2OJ 站内使用，校外用户无法查看；</li>
				<li>图片上传后会被自动转码为 PNG 格式；</li>
				<li>在合适的地方插入图片即可引用。</li>
			</ul>
			<p class="small">更多信息可以查看 <a href="https://s2oj.github.io/#/user/apps/image_hosting" target="_blank">使用文档</a>。</p>
		</div>
		<div class="col-12 col-md-5 col-lg-3 order-4 order-md-3 order-lg-4 mt-3 mt-md-0 ms-md-2">
			<h2 class="h4">使用统计</h2>
			<div class="d-flex justify-content-between">
				<span class="small">已用空间</span>
				<span><?= round($used * 1.0 / 1024 / 1024, 2) ?> MB / <?= round($limit * 1.0 / 1024 / 1024, 2) ?> MB</span>
			</div>
			<div class="d-flex justify-content-between">
				<span class="small">上传总数</span>
				<span><?= $count ?> 张</span>
			</div>
		</div>
	</form>
</div>
</div>
<script>
var image_upload_modal = new bootstrap.Modal('#image-upload-modal');
var droppedFiles = false;

function refreshCaptcha() {
	var timestamp = new Date().getTime();
	$("#captcha").attr("src", "/captcha" + '?' + timestamp);
}

$("#captcha").click(function(e) {
	refreshCaptcha();
});

$('#image-upload-form').submit(function(event) {
	event.preventDefault();

	var data = new FormData();
	data.append('_token', "<?= crsf_token() ?>");
	data.append('image_upload_file_submit', 'submit');
	data.append('image_upload_file', $('#image_upload_file').prop('files')[0]);
	data.append('watermark', $('input[name=watermark]:checked', this).data('value'));
	data.append('captcha', $('#input-captcha').val());

	if ($('#image_upload_file').prop('files')[0].size > 5242880) {
		$('#modal-help-message').html('图片大小不能超过 5 MB。').show();

		return false;
	}

	$('#modal-help-message').html('上传中...').show();

	$.ajax({
		method: 'POST',
		processData: false,
		contentType: false,
		data: data,
		success: function(data) {
			if (data.status === 'success') {
				image_upload_modal.hide();
				location.reload();
			} else {
				if (data.message === 'bad_captcha') {
					refreshCaptcha();
					$('#modal-help-message').html('验证码错误。').show();
				} else if (data.message === 'expired') {
					$('#modal-help-message').html('页面过期，请刷新重试。').show();
				} else if (data.message === 'storage_limit_exceeded') {
					$('#modal-help-message').html('存储超限，请联系管理员提升限制。').show();
				} else if (data.message === 'not_a_image') {
					$('#modal-help-message').html('文件格式不受支持。').show();
				} else if (data.message === 'too_large') {
					$('#modal-help-message').html('图片大小不能超过 5 MB。').show();
				}
			}
		},
		error: function() {
			$('#modal-help-message').html('上传失败，请刷新页面重试。').addClass('text-danger').show();
		}
	});

	return false;
});
$('#image-upload-form-drop').click(function() {
	$('#image_upload_file').click();
});
$('#image-upload-form-drop').on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
	e.preventDefault();
	e.stopPropagation();
}).on('dragover dragenter', function() {
	$('#select-image-text').html('松开以上传');
}).on('dragleave dragend drop', function() {
	$('#select-image-text').html('点击此处选择图片');
}).on('drop', function(e) {
	$('#image_upload_file').prop('files', e.originalEvent.dataTransfer.files);
	$('#image_upload_file').trigger('change');
});
$('#image-upload-modal').on('hide.bs.modal', function() {
	$('#image-upload-form').trigger('reset');
});
$('#image_upload_file').change(function() {
	if ($(this).prop('files')) {
		refreshCaptcha();
		var watermark_type = $('input[name=watermark]:checked', '#image-upload-form').data('value');
		var html = '';

		html += '<p><img src="'+ URL.createObjectURL($(this).prop('files')[0]) +'" height="150" style="object-fit: contain"></p>';
		html += '<p class="small">大小：<b>'+($(this).prop('files')[0].size / 1024).toFixed(2)+'</b> KB。';

		if (watermark_type === 'no_watermark') {
			html += '不添加水印。';
		} else if (watermark_type === 'site_shortname_and_username') {
			html += '使用水印：<?= UOJConfig::$data['profile']['oj-name-short'] ?> @<?= Auth::id() ?>。';
		} else {
			html += '使用水印：<?= UOJConfig::$data['profile']['oj-name-short'] ?>。';
		}

		html += '</p>';

		$('#modal-file-info').html(html);
		$('#modal-help-message').html('').hide();
		image_upload_modal.show();
	}
});
</script>

<?php
$pag_config = [
	'page_len' => 40,
	'col_names' => ['*'],
	'table_name' => 'users_images',
	'cond' => "uploader = '{$myUser['username']}'",
	'tail' => 'order by upload_time desc',
];
	$pag = new Paginator($pag_config);
	?>

<h2 class="h3 mt-4 mb-3">
	我的图片
</h2>

<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
	<?php foreach ($pag->get() as $idx => $row): ?>
		<div class="col">
			<div class="card">
				<img src="<?= $row['path'] ?>" class="card-img-top" height="200" style="object-fit: contain">
				<div class="card-footer bg-transparent small px-2">
					<div class="d-flex flex-wrap justify-content-between">
						<time><?= $row['upload_time'] ?></time>
						<span>
							<?php if ($row['size'] < 1024 * 512): ?>
								<?= round($row['size'] * 1.0 / 1024, 1) ?> KB
							<?php else: ?>
								<?= round($row['size'] * 1.0 / 1024 / 1024, 1) ?> MB
							<?php endif ?>
						</span>
					</div>
					<div class="d-flex flex-wrap justify-content-between mt-2">
						<form method="post" onsubmit="return confirm('您确定要删除这张图片吗？');">
							<input type="hidden" name="image_delete_submit" value="submit">
							<input type="hidden" name="image_delete_id" value="<?= $row['id'] ?>">
							<input type="hidden" name="_token" value="<?= crsf_token() ?>">
							<button class="btn btn-sm btn-outline-danger image-delete-button" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="删除">
								<i class="bi bi-trash3"></i>
							</button>
						</form>
						<div class="btn-group">
							<button class="btn btn-sm btn-outline-secondary image-copy-url-button" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="复制链接" data-image-path="<?= $row['path'] ?>">
								<i class="bi bi-clipboard"></i>
							</button>
							<button class="btn btn-sm btn-outline-secondary image-copy-md-button" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="复制 Markdown 源码" data-image-path="<?= $row['path'] ?>">
								<i class="bi bi-markdown"></i>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach ?>
</div>

<?php if ($pag->isEmpty()): ?>
	<div class="mt-4 text-muted">
		<?= UOJLocale::get('none') ?>
	</div>
<?php endif ?>

<div class="text-end">
	<?= $pag->pagination() ?>
</div>

<div class="toast-container position-fixed bottom-0 start-0 ms-3 mb-4">
	<div id="copy-url-toast" class="toast text-bg-success align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="d-flex">
			<div class="toast-body">
				复制成功！
			</div>
			<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	[...document.querySelectorAll('[data-bs-toggle="tooltip"]')].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
});

var copy_url_toast = new bootstrap.Toast('#copy-url-toast', { delay: 2000 });

$('.image-copy-url-button').click(function() {
	var url = new URL($(this).data('image-path'), location.origin);
	navigator.clipboard.writeText(url);
	copy_url_toast.show();
});

$('.image-copy-md-button').click(function() {
	var url = new URL($(this).data('image-path'), location.origin);
	navigator.clipboard.writeText('![](' + url + ')');
	copy_url_toast.show();
});
</script>

<?php echoUOJPageFooter() ?>
