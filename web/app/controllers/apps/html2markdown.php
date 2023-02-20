<?php echoUOJPageHeader(UOJLocale::get('html to markdown')) ?>

<h1>
	<?= UOJLocale::get('html to markdown') ?>
</h1>

<style>
	#html,
	#markdown {
		font-family: Cascadia Mono, Ubuntu Mono, Roboto Mono, Jetbrains Mono, Fira Code, Consolas, '思源黑体 Regular', '思源宋体 Light', '宋体', 'Courier New', monospace;
		width: 100%;
		min-height: 300px;
	}
</style>

<div class="card">
	<div class="card-body">
		<div class="row row-cols-1 row-cols-md-2">
			<div class="col">
				<textarea class="form-control" id="html" placeholder="input html here"></textarea>
			</div>
			<div class="col">
				<textarea data-no-autosize readonly class="form-control" id="markdown" placeholder="output markdown here" style="height: 100%"></textarea>
			</div>
		</div>
	</div>
	<div class="card-footer bg-transparent text-end">
		<a href="https://s2oj.github.io/#/user/apps/html2markdown" target="_blank">使用教程</a>
	</div>
</div>

<?= HTML::js_src('/js/turndown.js') ?>
<?= HTML::js_src('/js/turndown-plugin-gfm.js') ?>

<script>
	function mathjaxScriptBlockType(node) {
		if (node.nodeName !== 'SCRIPT') return null;

		const a = node.getAttribute('type');
		if (!a || a.indexOf('math/tex') < 0) return null;

		return a.indexOf('display') >= 0 ? 'block' : 'inline';
	}

	var turndownService = new TurndownService({
		headingStyle: 'atx',
		hr: '---',
		bulletListMarker: '-',
		codeBlockStyle: 'fenced',
		fence: '```',
		emDelimiter: '_',
		strongDelimiter: '**',
		linkStyle: 'inlined',
		linkReferenceStyle: 'full',
		preformattedCode: false,
	});

	turndownService.use(turndownPluginGfm.gfm);
	turndownService.addRule('mathjaxRendered', {
		filter: function(node) {
			return node.nodeName === 'SPAN' && node.getAttribute('class') === 'MathJax';
		},
		replacement: function(content) {
			return '';
		}
	});
	turndownService.addRule('mathjaxScriptInline', {
		filter: function(node) {
			return mathjaxScriptBlockType(node) === 'inline';
		},

		escapeContent: function() {
			// We want the raw unescaped content since this is what Katex will need to render
			// If we escape, it will double the \\ in particular.
			return false;
		},

		replacement: function(content, node, options) {
			return '$' + content + '$';
		}
	});
	turndownService.addRule('mathjaxScriptBlock', {
		filter: function(node) {
			return mathjaxScriptBlockType(node) === 'block';
		},

		escapeContent: function() {
			return false;
		},

		replacement: function(content, node, options) {
			return '$$\n' + content + '\n$$';
		}
	});

	turndownService.addRule('katexInline', {
		filter: function(node) {
			return node.nodeName === 'SPAN' && node.getAttribute('class') === 'katex';
		},

		escapeContent: function() {
			return false;
		},

		replacement: function(content, node, options) {
			return '$' + $('.katex-mathml annotation[encoding="application/x-tex"]', node).html() + '$';
		}
	});
	turndownService.addRule('katexInline', {
		filter: function(node) {
			return node.nodeName === 'SPAN' && node.getAttribute('class') === 'katex-display';
		},

		escapeContent: function() {
			return false;
		},

		replacement: function(content, node, options) {
			return '$$\n' + $('.katex-mathml annotation[encoding="application/x-tex"]', node).html() + '\n$$';
		}
	});

	$(document).ready(function() {
		$('#html').on('input', function() {
			$('#markdown').val(turndownService.turndown($('#html').val()));
		});
	});
</script>

<?php echoUOJPageFooter() ?>
