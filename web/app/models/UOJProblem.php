<?php

// this class depends on getUOJConf from uoj-judger-lib.php sometimes
// be sure to include the lib
// TODO: move getUOJConf into a static class independent of uoj-judger-lib.php

class UOJProblem {
	use UOJDataTrait;
	use UOJArticleTrait;

	public static array $difficulty = [
		800,
		1000,
		1200,
		1400,
		1600,
		1800,
		1900,
		2000,
		2100,
		2200,
		2300,
		2400,
		2500,
		2600,
		2700,
		2900,
		3100,
		3300,
		3500,
	];

	public static array $difficulty_color = [
		800 => '#008000',
		1000 => '#008000',
		1200 => '#00c0c0',
		1400 => '#00c0c0',
		1600 => '#0000ff',
		1800 => '#0000ff',
		1900 => '#0000ff',
		2000 => '#aa00aa',
		2100 => '#aa00aa',
		2200 => '#aa00aa',
		2300 => '#ff8000',
		2400 => '#ff8000',
		2500 => '#ff8000',
		2600 => '#ff0000',
		2700 => '#ff0000',
		2900 => '#ff0000',
		3100 => '#aa0000',
		3300 => '#aa0000',
		3500 => '#aa0000',
	];

	public static array $categories = [
		'算法基础' => [
			'暴力',
			'枚举',
			'模拟',
			'递归与分治',
			'贪心',
			'排序',
			'前缀和与差分',
			'二分',
			'倍增',
			'构造',
			'打表',
		],
		'搜索' => [
			'深度优先搜索',
			'广度优先搜索',
			'双向搜索',
			'启发式搜索',
			'A*',
			'IDA*',
			'迭代加深',
			'回溯法',
			'Dancing Links',
		],
		'动态规划' => [
			'记忆化搜索',
			'线性 DP',
			'背包 DP',
			'区间 DP',
			'树形 DP',
			'状压 DP',
			'数位 DP',
			'DAG 上 DP',
			'插头 DP',
			'概率 DP',
			'单调队列优化 DP',
			'斜率优化 DP',
			'四边形不等式优化 DP',
		],
		'计算几何' => [
			'Pick 定理',
			'三角剖分',
			'凸包',
			'扫描线',
			'旋转卡壳',
			'半平面交',
			'平面最近点对',
			'随机增量法',
			'反演变换',
		],
		'数学' => [
			'位运算',
			'快速幂',
			'高精度',
			'生成函数',
			'指数生成函数',
			'向量',
			'矩阵',
			'高斯消元',
			'线性基',
			'线性规划',
			'容斥',
			'组合计数',
			'离散对数',
			'单纯形算法',
			'概率',
			'置换群',
			'斐波那契数列',
			'牛顿迭代法',
			'数值积分',
			'分块打表',
		],
		'数论' => [
			'最大公约数',
			'分解质因数',
			'欧拉函数',
			'筛法',
			'欧拉定理',
			'费马小定理',
			'类欧几里得算法',
			'翡蜀定理',
			'乘法逆元',
			'线性同余方程',
			'Meissel-Lehmer 算法',
			'二次剩余',
			'BSGS',
			'原根',
			'卢卡斯定理',
			'莫比乌斯反演',
			'拉格朗日反演',
			'杜教筛',
			'Powerful Number 筛',
			'Min_25 筛',
			'洲阁筛',
			'连分数',
			'Stern-Brocot 数与 Farey 序列',
			'Pell 方程',
		],
		'字符串' => [
			'字符串哈希',
			'字典树',
			'KMP',
			'Boyer-Moore',
			'Z 函数（扩展 KMP）',
			'AC 自动机',
			'后缀数组',
			'后缀自动机',
			'后缀平衡树',
			'广义后缀自动机',
			'Manacher',
			'回文树',
			'序列自动机',
			'最小表示法',
			'Lyndon 分解',
		],
		'图论' => [
			'拓扑排序',
			'最短路',
			'K 短路',
			'同余最短路',
			'虚树',
			'树分治',
			'动态树分治',
			'树哈希',
			'树上启发式合并',
			'AHU 算法',
			'矩阵树定理',
			'最小生成树',
			'最小树形图',
			'最小直径生成树',
			'斯坦纳树',
			'拆点',
			'差分约束',
			'强连通分量',
			'双连通分量',
			'割点与桥',
			'圆方树',
			'2-SAT',
			'欧拉图',
			'哈密顿图',
			'最小环',
			'平面图',
			'网络流',
			'最大流',
			'最小割',
			'费用流',
			'上下界网络流',
			'Stoer-Wagner 算法',
			'二分图',
			'二分图最大匹配',
			'二分图最大权匹配',
			'一般图最大匹配',
			'一般图最大权匹配',
			'Prufer 序列',
			'LGV 引理',
			'弦图',
		],
		'组合数学' => [
			'排列组合',
			'卡特兰数',
			'斯特林数',
			'贝尔数',
			'伯努利数',
			'康托展开',
			'容斥原理',
			'抽屉原理',
			'欧拉数',
		],
		'数据结构' => [
			'栈',
			'队列',
			'链表',
			'哈希表',
			'并查集',
			'二叉堆',
			'配对堆',
			'树状数组',
			'线段树',
			'平衡树',
			'左偏树',
			'块状数组',
			'块状链表',
			'树分块',
			'Sqrt Tree',
			'可持久化数据结构',
			'单调栈',
			'单调队列',
			'ST 表',
			'树套树',
			'李超线段树',
			'区间最值操作与区间历史最值',
			'划分树',
			'跳表',
			'K-D Tree',
			'珂朵莉树',
			'动态树',
			'析合树',
		],
		'多项式' => [
			'拉格朗日插值',
			'快速傅里叶变换',
			'快速数论变换',
			'快速沃尔什变换',
			'多项式求逆',
			'多项式开方',
			'多项式除法与取模',
			'多项式对数函数与指数函数',
			'多项式牛顿迭代',
			'多项式多点求值与快速插值',
			'多项式三角函数',
			'多项式反三角函数',
			'常系数齐次线性递推',
		],
		'博弈论' => [
			'不平等博弈',
			'SG 函数',
			'Nim 游戏',
			'Anti-Nim',
			'纳什均衡',
		],
		'杂项' => [
			'构造',
			'离散化',
			'CDQ 分治',
			'整体二分',
			'分块',
			'莫队',
			'分数规划',
			'随机化',
			'模拟退火',
			'爬山法',
			'悬线法',
			'编译原理',
			'复杂度分析',
			'语义分析',
			'底层优化',
		],
	];

	public static function query($id) {
		if (!isset($id) || !validateUInt($id)) {
			return null;
		}
		$info = DB::selectFirst([
			"select * from problems",
			"where", ['id' => $id]
		]);
		if (!$info) {
			return null;
		}
		return new UOJProblem($info);
	}

	public static function upgradeToContestProblem() {
		return (new UOJContestProblem(self::cur()->info, UOJContest::cur()))->setAsCur()->valid();
	}

	public static function userCanManageSomeProblem(array $user = null) {
		if (!$user) {
			return false;
		}

		if (isSuperUser($user) || UOJUser::checkPermission($user, 'problems.manage')) {
			return true;
		}

		return DB::selectFirst([
			DB::lc(), "select 1 from problems_permissions",
			"where", [
				'username' => $user['username']
			], DB::limit(1)
		]) != null || DB::selectFirst([
			DB::lc(), "select 1 from problems",
			"where", [
				"uploader" => $user['username'],
			], DB::limit(1),
		]) != null;
	}

	public static function userCanCreateProblem(array $user = null) {
		if (!$user) {
			return false;
		}

		return isSuperUser($user) || UOJUser::checkPermission($user, 'problems.create');
	}

	public function __construct($info) {
		$this->info = $info;
	}

	public function type() {
		return $this->info['type'];
	}

	public function getTitle(array $cfg = []) {
		$cfg += [
			'with' => 'id',
			'simplify' => false
		];
		$title = $this->info['title'];
		if ($cfg['simplify']) {
			$title = trim($title);
			$title = mb_ereg_replace('^(\[[^\]]*\]|【[^】]*】)', '', $title);
			$title = trim($title);
		}
		if ($cfg['with'] == 'id') {
			return "#{$this->info['id']}. {$title}";
		} else {
			return $title;
		}
	}

	public function getUri($where = '') {
		return "/problem/{$this->info['id']}{$where}";
	}

	public function getLink(array $cfg = []) {
		return HTML::link($this->getUri(), $this->getTitle($cfg), ['escape' => false]);
	}

	public function getAttachmentUri() {
		return '/download/problem/' . $this->info['id'] . '/attachment.zip';
	}

	public function getMainDataUri() {
		return '/download/problem/' . $this->info['id'] . '/data.zip';
	}

	public function getUploaderLink() {
		return UOJUser::getLink($this->info['uploader'] ?: "root");
	}

	public function getProviderLink() {
		if ($this->type() == 'local') {
			return HTML::tag('a', ['href' => HTML::url('/')], UOJConfig::$data['profile']['oj-name-short']);
		}

		$remote_oj = $this->getExtraConfig('remote_online_judge');
		$remote_id = $this->getExtraConfig('remote_problem_id');

		if (!$remote_oj || !array_key_exists($remote_oj, UOJRemoteProblem::$providers)) {
			return 'Error';
		}

		$provider = UOJRemoteProblem::$providers[$remote_oj];

		return HTML::tag('a', [
			'href' => UOJRemoteProblem::getProblemRemoteUrl($remote_oj, $remote_id),
			'target' => '_blank'
		], $provider['name']);
	}

	public function getDifficultyHTML() {
		$difficulty = (int)$this->info['difficulty'];
		$difficulty_text = in_array($difficulty, static::$difficulty) ? $difficulty : '?';
		$difficulty_color = in_array($difficulty, static::$difficulty) ? static::$difficulty_color[$difficulty] : '#7e7e7e';

		return HTML::tag('span', ['class' => 'uoj-difficulty', 'style' => "color: $difficulty_color"], $difficulty_text);
	}

	public function findInContests() {
		$res = DB::selectAll([
			"select contest_id from contests_problems",
			"where", ['problem_id' => $this->info['id']]
		]);
		$cps = [];
		foreach ($res as $row) {
			$cp = new UOJContestProblem($this->info, UOJContest::query($row['contest_id']));
			if ($cp->valid()) {
				$cps[] = $cp;
			}
		}
		return $cps;
	}

	public function userCanClickZan(array $user = null) {
		if ($this->userCanView($user)) {
			return true;
		}
		foreach ($this->findInContests() as $cp) {
			if ($cp->userCanClickZan($user)) {
				return true;
			}
		}
		return false;
	}

	public function getZanBlock() {
		return ClickZans::getBlock('P', $this->info['id'], $this->info['zan']);
	}

	public function getSubmissionRequirement() {
		return json_decode($this->info['submission_requirement'], true);
	}
	public function getExtraConfig($key = null) {
		$extra_config = json_decode($this->info['extra_config'], true);

		$extra_config += [
			'view_content_type' => 'ALL',
			'view_all_details_type' => 'ALL',
			'view_details_type' => 'ALL',
			'view_solution_type' => 'ALL',
			'submit_solution_type' => 'ALL_AFTER_AC',
			'need_to_review_hack' => false,
			'add_hack_as' => 'ex_test',
		];

		return $key === null ? $extra_config : $extra_config[$key];
	}
	public function getCustomTestRequirement() {
		if ($this->type() == 'remote') {
			return [];
		}

		$extra_config = json_decode($this->info['extra_config'], true);

		if (isset($extra_config['custom_test_requirement'])) {
			return $extra_config['custom_test_requirement'];
		}

		$answer = [
			'name' => 'answer',
			'type' => 'source code',
			'file_name' => 'answer.code'
		];
		foreach ($this->getSubmissionRequirement() as $req) {
			if ($req['name'] == 'answer' && $req['type'] == 'source code' && isset($req['languages'])) {
				$answer['languages'] = $req['languages'];
			}
		}
		return [
			$answer, [
				'name' => 'input',
				'type' => 'text',
				'file_name' => 'input.txt'
			]
		];
	}

	public function userCanView(array $user = null, array $cfg = []) {
		$cfg += ['ensure' => false];

		if ($this->info['is_hidden'] && !$this->userCanManage($user)) {
			$cfg['ensure'] && UOJResponse::page404();
			return false;
		}

		if (!UOJUser::checkPermission($user, 'problems.view')) {
			$cfg['ensure'] && UOJResponse::page403();
			return false;
		}

		return true;
	}

	/**
	 * Get a SQL cause to determine whether a user can view a problem
	 * Need to be consistent with the member function userCanView
	 */
	public static function sqlForUserCanView(array $user = null) {
		if (isSuperUser($user) || UOJUser::checkPermission($user, 'problems.manage')) {
			return "(1)";
		} elseif (UOJProblem::userCanManageSomeProblem($user)) {
			return DB::lor([
				"problems.is_hidden" => false,
				DB::land([
					"problems.is_hidden" => true,
					DB::lor([
						[
							"problems.id", "in", DB::rawbracket([
								"select problem_id from problems_permissions",
								"where", ["username" => $user['username']]
							])
						],
						[
							"problems.id", "in", DB::rawbracket([
								"select problem_id from problems",
								"where", ["uploader" => $user['username']]
							])
						],
					])
				])
			]);
		} else {
			return "(problems.is_hidden = false)";
		}
	}

	public function isUserOwnProblem(array $user = null) {
		if (!$user) {
			return false;
		}
		return $user['username'] === $this->info['uploader'];
	}

	public function userPermissionCodeCheck(array $user = null, $perm_code) {
		switch ($perm_code) {
			case 'ALL':
				return true;
			case 'ALL_AFTER_AC':
				return $this->userHasAC($user);
			case 'NONE':
				return false;
			default:
				return null;
		}
	}

	public function userCanUploadSubmissionViaZip(array $user = null) {
		foreach ($this->getSubmissionRequirement() as $req) {
			if ($req['type'] == 'source code') {
				return false;
			}
		}
		return true;
	}

	public function userCanDownloadAttachments(array $user = null) {
		if ($this->userCanView($user)) {
			return true;
		}
		foreach ($this->findInContests() as $cp) {
			if ($cp->userCanDownloadAttachments($user)) {
				return true;
			}
		}
		return false;
	}

	public function userCanManage(array $user = null) {
		if (!$user) {
			return false;
		}

		if (isSuperUser($user) || $this->isUserOwnProblem($user) || UOJUser::checkPermission($user, 'problems.manage')) {
			return true;
		}

		return DB::selectFirst([
			DB::lc(), "select 1 from problems_permissions",
			"where", [
				'username' => $user['username'],
				'problem_id' => $this->info['id']
			]
		]) != null;
	}

	public function userCanDownloadTestData(array $user = null) {
		if ($this->userCanManage($user)) {
			return true;
		}

		if (!UOJUser::checkPermission($user, 'problems.download_testdata')) {
			return false;
		}

		foreach ($this->findInContests() as $cp) {
			if ($cp->contest->userHasRegistered($user) && $cp->contest->progress() == CONTEST_IN_PROGRESS) {
				return false;
			}
		}

		return true;
	}

	public function preHackCheck(array $user = null) {
		return $this->info['hackable'] && (!$user || $this->userCanView($user));
	}

	public function needToReviewHack() {
		return $this->getExtraConfig('need_to_review_hack');
	}

	public function userHasAC(array $user = null) {
		if (!$user) {
			return false;
		}
		return DB::selectFirst([
			DB::lc(), "select 1 from best_ac_submissions",
			"where", [
				'submitter' => $user['username'],
				'problem_id' => $this->info['id']
			]
		]) != null;
	}

	public function preSubmitCheck() {
		return true;
	}

	public function additionalSubmissionComponentsCannotBeSeenByUser(array $user = null, UOJSubmission $submission) {
		foreach ($this->findInContests() as $cp) {
			if ($cp->contest->userHasRegistered($user) && $cp->contest->progress() == CONTEST_IN_PROGRESS) {
				if ($submission->userIsSubmitter($user)) {
					if ($cp->contest->getJudgeTypeInContest() == 'no-details') {
						return ['low_level_details'];
					} else {
						return [];
					}
				} else {
					return ['content', 'high_level_details', 'low_level_details'];
				}
			}
		}

		return [];
	}

	public function getDataFolderPath() {
		return "/var/uoj_data/{$this->info['id']}";
	}

	public function getDataZipPath() {
		return "/var/uoj_data/{$this->info['id']}.zip";
	}

	public function getDataFilePath($name = '') {
		// return "zip://{$this->getDataZipPath()}#{$this->info['id']}/$name";
		return "{$this->getDataFolderPath()}/$name";
	}

	public function getProblemConfArray(string $where = 'data') {
		if ($where === 'data') {
			return getUOJConf($this->getDataFilePath('problem.conf'));
		} else {
			return null;
		}
	}

	public function getProblemConf(string $where = 'data') {
		if ($where === 'data') {
			return UOJProblemConf::getFromFile($this->getDataFilePath('problem.conf'));
		} else {
			return null;
		}
	}

	public function getNonTraditionalJudgeType() {
		$conf = $this->getProblemConf();
		if (!($conf instanceof UOJProblemConf)) {
			return false;
		}
		return $conf->getNonTraditionalJudgeType();
	}
}

UOJProblem::$table_for_content = 'problems_contents';
UOJProblem::$key_for_content = 'id';
UOJProblem::$fields_for_content = ['*'];
UOJProblem::$table_for_tags = 'problems_tags';
UOJProblem::$key_for_tags = 'problem_id';
