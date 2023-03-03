<?php
if (!isset($PageMainTitle)) {
	$PageMainTitle = UOJConfig::$data['profile']['oj-name'];
}
if (!isset($PageMainTitleOnSmall)) {
	$PageMainTitleOnSmall = UOJConfig::$data['profile']['oj-name-short'];
}
if (!isset($ShowPageHeader)) {
	$ShowPageHeader = true;
}
if (!isset($PageContainerClass)) {
	$PageContainerClass = 'container';
}

$new_user_msg_num = DB::selectCount("select count(*) from user_msg where receiver = '" . Auth::id() . "' and read_time is null");
$new_system_msg_num = DB::selectCount("select count(*) from user_system_msg where receiver = '" . Auth::id() . "' and read_time is null");
$new_msg_tot = $new_user_msg_num + $new_system_msg_num;
?>
<!-- Made with 💖 by Baoshuo ( https://baoshuo.ren ) -->
<!DOCTYPE html>
<html lang="<?= UOJLocale::locale() ?>">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php if (isset($_GET['locale'])) : ?>
		<meta name="robots" content="noindex, nofollow" />
	<?php endif ?>
	<title><?= isset($PageTitle) ? $PageTitle : UOJConfig::$data['profile']['oj-name-short'] ?> - <?= $PageMainTitle ?></title>

	<script type="text/javascript">
		uojHome = ''; // '<?= HTML::url('/') ?>';
	</script>

	<!-- Bootstrap 5 (CSS) -->
	<?= HTML::css_link('/css/bootstrap.min.css?v=5.3.0-alpha1') ?>
	<!-- Bootstrap Icons -->
	<?= HTML::css_link('/css/bootstrap-icons.min.css?v=2022.9.23') ?>

	<!-- Custom styles for this template -->
	<?= HTML::css_link('/css/uoj-bs5.css?v=' . UOJConfig::$data['profile']['s2oj-version']) ?>

	<!-- jQuery (necessary for Bootstrap\'s JavaScript plugins) -->
	<?= HTML::js_src('/js/jquery.min.js') ?>

	<!-- jQuery autosize -->
	<?= HTML::js_src('/js/jquery.autosize.min.js') ?>
	<script type="text/javascript">
		$(document).ready(function() {
			$('textarea:not([data-no-autosize])').autosize();
		});
	</script>

	<!-- jQuery cookie -->
	<?= HTML::js_src('/js/jquery.cookie.min.js') ?>

	<!-- Bootstrap 5: JavaScript -->
	<?= HTML::js_src('/js/bootstrap.bundle.min.js?v=5.3.0-alpha1') ?>

	<!-- Color converter -->
	<?= HTML::js_src('/js/color-converter.min.js') ?>

	<!-- Clipboard Polyfill -->
	<?= HTML::js_src('/js/clipboard-polyfill.overwrite-globals.es5.min.js') ?>

	<!-- uoj -->
	<?= HTML::js_src('/js/uoj.js?v=' . UOJConfig::$data['profile']['s2oj-version']) ?>

	<!-- readmore -->
	<?= HTML::js_src('/js/readmore/readmore.min.js') ?>

	<!-- LAB -->
	<?= HTML::js_src('/js/LAB.min.js') ?>

	<!-- favicon -->
	<link rel="shortcut icon" href="<?= HTML::url('/images/favicon.ico') ?>" />

	<?php if (isset($REQUIRE_LIB['blog-editor'])) : ?>
		<!-- UOJ blog editor -->
		<?php $REQUIRE_LIB['jquery.hotkeys'] = '' ?>
		<?php $REQUIRE_LIB['switch'] = '' ?>
		<?= HTML::css_link('/css/blog-editor.css?v=' . UOJConfig::$data['profile']['s2oj-version']) ?>
		<?= HTML::js_src('/js/blog-editor/blog-editor.js?v=' . UOJConfig::$data['profile']['s2oj-version']) ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['md5'])) : ?>
		<!-- MD5 -->
		<?= HTML::js_src('/js/md5.min.js') ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['dialog'])) : ?>
		<!-- Bootstrap dialog -->
		<?= HTML::css_link('/css/bootstrap-dialog.min.css') ?>
		<?= HTML::js_src('/js/bootstrap-dialog.min.js') ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['switch'])) : ?>
		<!-- Bootstrap switch -->
		<?= HTML::css_link('/css/bootstrap-switch.min.css') ?>
		<?= HTML::js_src('/js/bootstrap-switch.min.js') ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['mathjax'])) : ?>
		<!-- MathJax -->
		<script>
			MathJax = {
				tex: {
					inlineMath: [
						['$', '$'],
						['\\(', '\\)']
					],
					processEscapes: true
				},
				options: {
					skipHtmlTags: {
						'[-]': ['pre']
					},
					renderActions: {
						addCopyText: [
							155,
							(doc) => {
								for (const math of doc.math) {
									MathJax.config.addCopyText(math, doc);
								}
							},
							function(math, doc) {
								MathJax.config.addCopyText(math, doc);
							}
						]
					},
				},
				addCopyText(math, doc) {
					doc.adaptor.append(
						math.typesetRoot,
						doc.adaptor.node(
							'mjx-copytext', {
								'aria-hidden': true,
							},
							[
								doc.adaptor.text(
									math.start.delim +
									math.math +
									math.end.delim)
							]
						)
					);
				},
				startup: {
					ready() {
						MathJax._.output.chtml_ts.CHTML.commonStyles['mjx-copytext'] = {
							display: 'inline-block',
							position: 'absolute',
							top: 0,
							left: 0,
							width: 0,
							height: 0,
							opacity: 0,
						};
						MathJax.startup.defaultReady();
					}
				},
			};
		</script>
		<script id="MathJax-script" src="<?= HTML::url('/js/mathjax3/tex-mml-chtml.js') ?>"></script>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['jquery.form'])) : ?>
		<!-- jquery form -->
		<?= HTML::js_src('/js/jquery.form.min.js') ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['jquery.hotkeys'])) : ?>
		<!-- jquery hotkeys -->
		<?= HTML::js_src('/js/jquery.hotkeys.js') ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['jquery.datatables'])) : ?>
		<!-- jquery.datatable -->
		<?= HTML::js_src('/js/jquery.datatables.min.js') ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['morris'])) : ?>
		<!-- morris -->
		<?= HTML::js_src('/js/morris.min.js') ?>
		<?= HTML::css_link('/css/morris.css') ?>
		<?php $REQUIRE_LIB['raphael'] = "" ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['raphael'])) : ?>
		<!-- raphael -->
		<?= HTML::js_src('/js/raphael.min.js') ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['hljs'])) : ?>
		<?= HTML::css_link('/css/highlightjs.github.min.css?v=11.6.0-20230223') ?>
		<?= HTML::js_src('/js/highlightjs.min.js?v=11.6.0-20221005') ?>
		<script>
			$(document).ready(function() {
				hljs.highlightAll();
			});
		</script>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['dropzone'])) : ?>
		<?= HTML::css_link('/css/dropzone.min.css') ?>
		<?= HTML::js_src('/js/dropzone.min.js') ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['calendar_heatmap'])) : ?>
		<!-- jquery-calendar-heatmap -->
		<?= HTML::css_link('/css/jquery.calendar_heatmap.min.css') ?>
		<?= HTML::js_src('/js/jquery.calendar_heatmap.min.js') ?>
	<?php endif ?>

	<?php if (isset($REQUIRE_LIB['fontawesome'])) : ?>
		<!-- fontawesome -->
		<?= HTML::css_link('/css/font-awesome.min.css') ?>
	<?php endif ?>

	<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->

	<script type="text/javascript">
		before_window_unload_message = null;
		$(window).on('beforeunload', function() {
			if (before_window_unload_message !== null) {
				return before_window_unload_message;
			}
		});
	</script>

	<script>
		console.log(
			'S2OJ (build: <?= UOJConfig::$data['profile']['s2oj-version'] ?>)\n' +
			'https://github.com/renbaoshuo/S2OJ\n' +
			'\n' +
			'Made with 💖 by Baoshuo ( https://baoshuo.ren )\n'
		);
	</script>

	<script async data-domain="sjzezoj.com" src="https://stat.u.sb/js/script.js"></script>
</head>

<body class="d-flex flex-column min-vh-100
	<?php if ($ShowPageHeader) : ?>
		bg-body-tertiary
	<?php endif ?>
	<?php if (isset($PageBodyClass)) : ?>
		<?= $PageBodyClass ?>
	<?php endif ?>
	">
	<?php if ($ShowPageHeader) : ?>
		<?php uojIncludeView($PageNav, array('REQUIRE_LIB' => $REQUIRE_LIB)) ?>
	<?php endif ?>

	<div class="uoj-content <?= $PageContainerClass ?> flex-fill">
