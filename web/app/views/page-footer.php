<?php
	if (!isset($ShowPageFooter)) {
		$ShowPageFooter = true;
	}
	?>
			</div>
			<?php if ($ShowPageFooter): ?>
			<div class="uoj-footer">
				<div class="btn-group dropright mb-3">
					<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
						<span class="glyphicon glyphicon-globe"></span> <?= UOJLocale::get('_common_name') ?>
					</button>
					<div class="dropdown-menu">
						<a class="dropdown-item" href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'zh-cn'))) ?>">中文</a>
						<a class="dropdown-item" href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'en'))) ?>">English</a>
					</div>
				</div>

				<p><?= UOJLocale::get('server time') ?>: <?= UOJTime::$time_now_str ?></p>
				<p>
					<a href="https://github.com/renbaoshuo/S2OJ<?= UOJConfig::$data['profile']['s2oj-version'] == "dev" ? '' : '/tree/' . UOJConfig::$data['profile']['s2oj-version'] ?>">S2OJ (build: <?= UOJConfig::$data['profile']['s2oj-version'] ?>)</a>
					<?php if (UOJConfig::$data['profile']['ICP-license'] != ''): ?>
						| <a target="_blank" href="https://beian.miit.gov.cn" style="text-decoration:none;"><?= UOJConfig::$data['profile']['ICP-license'] ?></a>
					<?php endif ?>
				</p>
			</div>
			<?php endif ?>
		</div>
		<!-- /container -->
	</body>
</html>
