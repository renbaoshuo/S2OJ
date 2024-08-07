<?php
requireLib('hljs');
requireLib('mathjax');
?>

<?php echoUOJPageHeader(UOJLocale::get('help'))	?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<div class="card card-default">
			<article class="card-body">
				<h1 class="h2 card-title mb-3">常见问题及其解答 (FAQ)</h1>

				<h4 class="mt-4"><?= UOJConfig::$data['profile']['oj-name-short'] ?> 是什么</h4>
				<p class="card-text">
					见 <a href="https://sjzezoj.com/blogs/1">https://sjzezoj.com/blogs/1</a>。
				</p>

				<h4 class="mt-4">测评环境</h4>
				<p class="card-text">评测机的系统版本是 Ubuntu Server 22.04 LTS。</p>
				<div class="table-responsive">
					<table class="table table-bordered text-center align-middle">
						<thead>
							<th>语言</th>
							<th>版本</th>
							<th>编译命令</th>
						</thead>
						<tbody>
							<tr>
								<td>C</td>
								<td>gcc 11.2.0</td>
								<td><code>gcc -o code code.c -lm -O2 -DONLINE_JUDGE</code></td>
							</tr>
							<tr>
								<td>C++</td>
								<td>g++ 11.2.0</td>
								<td><code>g++ -o code code.cpp -lm -O2 -DONLINE_JUDGE</code>（语言版本默认为 C++14，如选择其他版本还会添加 <code>-std=</code> 参数）</td>
							</tr>
							<tr>
								<td>Pascal</td>
								<td>fpc 3.2.2</td>
								<td><code>fpc code.pas -O2</code></td>
							</tr>
							<tr>
								<td>Python 2</td>
								<td>Python 2.7.18</td>
								<td rowspan="2">预先编译为优化过的字节码 <code>.pyo</code> 文件</td>
							</tr>
							<tr>
								<td>Python 3</td>
								<td>Python 3.10.6</td>
							</tr>
							<tr>
								<td>Java 8</td>
								<td>OpenJDK 1.8.0_342</td>
								<td rowspan="3"><code>javac code.java</code></td>
							</tr>
							<tr>
								<td>Java 11</td>
								<td>OpenJDK 11.0.16</td>
							</tr>
							<tr>
								<td>Java 17</td>
								<td>OpenJDK 17.0.4</td>
							</tr>
						</tbody>
					</table>
				</div>
				<p class="card-text">以上信息仅供参考，实际评测环境可能会有变动。</p>

				<h4 class="mt-4">如何上传头像</h4>
				<p class="card-text">
					<?= UOJConfig::$data['profile']['oj-name-short'] ?> 不提供头像存储服务。每到一个网站都要上传一个头像挺烦的对不对？我们支持 Gravatar，请使用 Gravatar 吧！Gravatar 是一个全球的头像存储服务，你的头像将会与你的电子邮箱绑定。在各大网站比如各种 Wordpress 还有各种 OJ 比如 Vijos、Contest Hunter 上，只要你电子邮箱填对了，那么你的头像也就立即能显示了！
				</p>
				<p class="card-text">
					快使用 Gravatar 吧！Gravatar 地址：<a href="https://cn.gravatar.com" target="_blank">https://cn.gravatar.com</a>。进去后注册个帐号然后与邮箱绑定并上传头像，就 OK 啦！
				</p>
				<p class="card-text">
					上不去 Gravatar？没关系，我们现在也支持 QQ 头像了！你只需要前往 “更改个人信息” 页面填写自己的 QQ 号，并将 “头像来源” 选为 “QQ” 就可以让你的 QQ 头像显示在 S2OJ 上啦！
				</p>

				<h4 class="mt-4">递归 10<sup>7</sup> 层怎么没爆栈啊</h4>
				<p class="card-text">
					没错就是这样！除非是特殊情况，<?= UOJConfig::$data['profile']['oj-name-short'] ?> 测评程序时的栈大小与该题的空间限制是相等的！
				</p>

				<h4 class="mt-4">联系方式</h4>
				<p class="card-text">
					题目相关问题请联系各校区的竞赛教练以及题目管理员。
				</p>
				<p class="card-text">
					系统相关问题请邮件联系 <a href="https://sjzezoj.com/user/baoshuo" class="uoj-username">baoshuo</a>（<a href="mailto:i@baoshuo.ren">i@baoshuo.ren</a>）
					和 <a href="https://sjzezoj.com/user/nekko" class="uoj-username">nekko</a>（<a href="mailto:1139855151@qq.com">1139855151@qq.com</a>）。
				</p>

				<h4 class="mt-4">开源项目</h4>
				<p class="card-text">
					S2OJ 是采用 <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="nofollow noreferrer noopener">AGPLv3</a> 协议的自由软件。S2OJ 的源代码存放于
					<a href="https://github.com/renbaoshuo/S2OJ" target="_blank">https://github.com/renbaoshuo/S2OJ</a>。
					<br>
					<small>PS: 如果你网不太好，打不开 GitHub 的话，也可以点击 <a href="https://git.m.ac/baoshuo/S2OJ" target="_blank">https://git.m.ac/baoshuo/S2OJ</a> 查看哦！这两个仓库的内容是一模一样的。</small>
				</p>
				<div class="alert alert-warning mb-2 small" role="alert">
					<b>特别提醒</b>：本项目采用 <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="nofollow noreferrer noopener">AGPLv3</a> 协议。任何对本项目进行修改和衍生的行为都需要遵循该协议，并且需要将修改后的代码以相同协议公开发布。
				</div>

				<h4 class="mt-4">用户手册</h4>
				<p class="card-text">
					请移步 <a href="https://s2oj.github.io/">S2OJ 使用文档</a>。
				</p>
			</article>
		</div>
		<!-- end left col -->
	</div>

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>

</div>

<?php echoUOJPageFooter() ?>
