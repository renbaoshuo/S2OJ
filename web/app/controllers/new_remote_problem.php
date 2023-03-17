<?php
requirePHPLib('form');
requirePHPLib('data');

Auth::check() || redirectToLogin();
UOJProblem::userCanCreateProblem(Auth::user()) || UOJResponse::page403();

$new_remote_problem_form = new UOJForm('new_remote_problem');
$new_remote_problem_form->addSelect('remote_online_judge', [
	'label' => '远程 OJ',
	'options' => array_map(fn ($provider) => $provider['name'], UOJRemoteProblem::$providers),
]);
$new_remote_problem_form->addInput('remote_problem_id', [
	'div_class' => 'mt-3',
	'label' => '远程 OJ 上的题目 ID',
	'validator_php' => function ($id, &$vdata) {
		$remote_oj = $_POST['remote_online_judge'];
		if ($remote_oj === 'codeforces') {
			$id = trim(strtoupper($id));

			if (!validateCodeforcesProblemId($id)) {
				return '不合法的题目 ID';
			}

			$vdata['remote_problem_id'] = $id;

			return '';
		} else if ($remote_oj === 'atcoder') {
			$id = trim(strtolower($id));

			if (!validateAtCoderProblemId($id)) {
				return '不合法的字符串';
			}

			$vdata['remote_problem_id'] = $id;

			return '';
		} else if ($remote_oj === 'uoj') {
			if (!validateUInt($id)) {
				return '不合法的题目 ID';
			}

			$vdata['remote_problem_id'] = $id;

			return '';
		} else if ($remote_oj === 'loj') {
			if (!validateUInt($id)) {
				return '不合法的题目 ID';
			}

			$vdata['remote_problem_id'] = $id;

			return '';
		} else if ($remote_oj === 'luogu') {
			$id = trim(strtoupper($id));

			if (!validateLuoguProblemId($id)) {
				return '不合法的题目 ID';
			}

			$vdata['remote_problem_id'] = $id;

			return '';
		} else if ($remote_oj === 'qoj') {
			if (!validateUInt($id)) {
				return '不合法的题目 ID';
			}

			$vdata['remote_problem_id'] = $id;

			return '';
		}

		return '不合法的远程 OJ 类型';
	},
]);
$new_remote_problem_form->handle = function (&$vdata) {
	$remote_online_judge = $_POST['remote_online_judge'];
	$remote_problem_id = $vdata['remote_problem_id'];
	$remote_provider = UOJRemoteProblem::$providers[$remote_online_judge];

	try {
		$data = UOJRemoteProblem::getProblemBasicInfo($remote_online_judge, $remote_problem_id);
	} catch (Exception $e) {
		$data = null;
		UOJLog::error($e->getMessage());
	}

	if ($data === null) {
		UOJResponse::page500('题目抓取失败，可能是题目不存在或者没有题面！如果题目没有问题，请稍后再试。<a href="">返回</a>');
	}

	$submission_requirement = UOJRemoteProblem::getSubmissionRequirements($remote_online_judge);
	$enc_submission_requirement = json_encode($submission_requirement);

	$extra_config = [
		'remote_online_judge' => $remote_online_judge,
		'remote_problem_id' => $remote_problem_id,
		'time_limit' => $data['time_limit'],
		'memory_limit' => $data['memory_limit'],
	];
	$enc_extra_config = json_encode($extra_config);

	DB::insert([
		"insert into problems",
		"(title, uploader, is_hidden, submission_requirement, extra_config, difficulty, type)",
		"values", DB::tuple([$data['title'], Auth::id(), 1, $enc_submission_requirement, $enc_extra_config, $data['difficulty'] ?: -1, "remote"])
	]);

	$id = DB::insert_id();
	dataNewProblem($id);

	if ($data['type'] == 'pdf') {
		file_put_contents(UOJContext::storagePath(), "/problem_resources/$id/statement.pdf", $data['pdf_data']);
		$data['statement'] = "<div data-pdf data-src=\"/problem/$id/resources/statement.pdf\"></div>\n" . $data['statement'];
	}

	DB::insert([
		"insert into problems_contents",
		"(id, remote_content, statement, statement_md)",
		"values",
		DB::tuple([$id, HTML::purifier(['a' => ['target' => 'Enum#_blank']])->purify($data['statement']), '', ''])
	]);

	DB::insert([
		"insert into problems_tags",
		"(problem_id, tag)",
		"values",
		DB::tuple([$id, $remote_provider['name']]),
	]);

	UOJRemoteProblem::downloadImagesInRemoteContent(strval($id));

	redirectTo("/problem/{$id}");
	die();
};
$new_remote_problem_form->runAtServer();
?>

<?php echoUOJPageHeader('导入远程题库') ?>

<h1>导入远程题库</h1>

<div class="row">
	<div class="col-md-9">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-6">
						<?php $new_remote_problem_form->printHTML() ?>
					</div>
					<div class="col-md-6 mt-3 mt-md-0">
						<h4>使用帮助</h4>
						<ul>
							<li>
								<p>目前支持导入以下题库的题目作为远端评测题：</p>
								<ul class="mb-3">
									<li><a href="https://codeforces.com/problemset">Codeforces</a></li>
									<li><a href="https://codeforces.com/gyms">Codeforces::Gym</a>（题号前加 <code>GYM</code>）</li>
									<li><a href="https://atcoder.jp/contests/archive">AtCoder</a>（请以原题目链接中附带的题号为准，显示题号与实际题号的对应关系可能错误）</li>
									<li><a href="https://uoj.ac/problems">UniversalOJ</a></li>
									<li><a href="https://loj.ac/p">LibreOJ</a></li>
									<li><a href="https://www.luogu.com.cn/problem/list">洛谷</a>（不能使用公用账号提交）</li>
									<li><a href="https://qoj.ac/problems">Qingyu Online Judge</a></li>
								</ul>
							</li>
							<li>在导入题目前请先搜索题库中是否已经存在相应题目，避免重复添加。</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<?php uojIncludeView('sidebar') ?>
	</div>
</div>

<?php echoUOJPageFooter() ?>
