function blog_editor_init(name, editor_config) {
	if (editor_config === undefined) {
		editor_config = {};
	}
	
	editor_config = $.extend({
		type: 'blog'
	}, editor_config);
	
	var input_title = $("#input-" + name + "_title");
	var input_tags = $("#input-" + name + "_tags");
	var input_content_md = $("#input-" + name + "_content_md");
	var input_is_hidden = $("#input-" + name + "_is_hidden");
	var this_form = input_is_hidden[0].form;
	var div_container_editor = $("#div_container-" + name + "_content_md");

	var is_saved;
	var last_save_done = true;
	
	// init buttons
	var save_btn = $('<button type="button" class="btn btn-sm"></button>');
	var preview_btn = $('<button type="button" class="btn btn-secondary btn-sm"><i class="bi bi-eye"></i></button>');
	var bold_btn = $('<button type="button" class="btn btn-secondary btn-sm ml-2"><i class="bi bi-type-bold"></i></button>');
	var italic_btn = $('<button type="button" class="btn btn-secondary btn-sm"><i class="bi bi-type-italic"></i></button>');
	var strike_btn = $('<button type="button" class="btn btn-secondary btn-sm"><i class="bi bi-type-strikethrough"></i></button>');
	var divider_btn = $('<button type="button" class="btn btn-secondary btn-sm"><i class="bi bi-dash"></i></button>');

	bootstrap.Tooltip.jQueryInterface.call(save_btn, { container: 'body', title: '保存 (Ctrl-S)' });
	bootstrap.Tooltip.jQueryInterface.call(preview_btn, { container: 'body', title: '预览 (Ctrl-D)'	});
	bootstrap.Tooltip.jQueryInterface.call(bold_btn, { container: 'body', title: '粗体 (Ctrl-B)' });
	bootstrap.Tooltip.jQueryInterface.call(italic_btn, { container: 'body', title: '斜体 (Ctrl-I)' });
	bootstrap.Tooltip.jQueryInterface.call(strike_btn, { container: 'body', title: '删除线 (Alt-S)' });
	bootstrap.Tooltip.jQueryInterface.call(divider_btn, { container: 'body', title: '分割线 (Alt-D)' });

	var all_btn = [save_btn, preview_btn, bold_btn, italic_btn, strike_btn, divider_btn];
	
	// init toolbar
	var toolbar = $('<div class="btn-toolbar"></div>');
	toolbar.append($('<div class="btn-group me-2"></div>')
		.append(save_btn)
		.append(preview_btn)
	);
	toolbar.append($('<div class="btn-group"></div>')
		.append(bold_btn)
		.append(italic_btn)
		.append(strike_btn)
		.append(divider_btn)
	);
	
	function set_saved(val) {
		is_saved = val;
		if (val) {
			save_btn.removeClass('btn-warning');
			save_btn.addClass('btn-success');
			save_btn.html('<i class="bi bi-save-fill"></i>');
			before_window_unload_message = null;
		} else {
			save_btn.removeClass('btn-success');
			save_btn.addClass('btn-warning');
			save_btn.html('<i class="bi bi-save"></i>');
			before_window_unload_message = '您所编辑的内容尚未保存';
		}
	}
	function set_preview_status(status) {
		// 0: normal
		// 1: loading
		// 2: loaded
		if (status == 0) {
			preview_btn.removeClass('active');
			for (var i = 0; i < all_btn.length; i++) {
				if (all_btn[i] != preview_btn) {
					all_btn[i].prop('disabled', false);
				}
			}
		} else if (status == 1) {
			for (var i = 0; i < all_btn.length; i++) {
				if (all_btn[i] != preview_btn) {
					all_btn[i].prop('disabled', true);
				}
			}
			preview_btn.addClass('active');
		}
	}
	
	set_saved(true);
	
	// init editor
	if (input_content_md[0]) {
		div_container_editor.empty();
		div_container_editor.wrap('<div class="blog-content-md-editor border rounded" />');
		var blog_contend_md_editor = div_container_editor.parent();
		blog_contend_md_editor.prepend($('<div class="blog-content-md-editor-toolbar bg-secondary-subtle px-2 py-1 border-bottom rounded-top" />').append(toolbar));
		div_container_editor.wrap('<div class="blog-content-md-editor-in rounded-bottom overflow-hidden" />');
		div_container_editor.append($('<div class="d-flex justify-content-center align-items-center" style="width: 100%; height: 500px;" />').append('<div class="spinner-border text-muted" style="width: 3rem; height: 3rem;" />'));

		require_monaco({
			markdown: true,
		}, function() {
			$(div_container_editor).empty();

			var monaco_editor_instance = monaco.editor.create(div_container_editor[0], {
				language: editor_config.type == 'slide' ? 'yaml' : 'markdown-math',
				automaticLayout: true,
				fontSize: "16px",
				minimap: {
					enabled: false,
				},
				wordWrap: 'on',
				unicodeHighlight: {
					ambiguousCharacters: false,
				},
			});

			monaco_editor_instance.getModel().setValue(input_content_md.val());

			monaco_editor_instance.onDidChangeModelContent(function () {
				set_saved(false);
				input_content_md.val(monaco_editor_instance.getModel().getValue());
			});

			bold_btn.click(function() {
				monaco_editor_instance.trigger('', 'markdown.extension.editing.toggleBold');
				monaco_editor_instance.focus();
			});

			italic_btn.click(function() {
				monaco_editor_instance.trigger('', 'markdown.extension.editing.toggleItalic');
				monaco_editor_instance.focus();
			});

			strike_btn.click(function() {
				monaco_editor_instance.trigger('', 'markdown.extension.editing.toggleStrikethrough');
				monaco_editor_instance.focus();
			});

			divider_btn.click(function() {
				monaco_editor_instance.trigger('keyboard', 'type', { text: "\n---\n" });
				monaco_editor_instance.focus();
			});

			monaco_editor_instance.addAction({
				id: 'save',
				label: 'Save',
				keybindings: [
					monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS,
				],
				precondition: null,
				keybindingContext: null,
				run: function(ed) {
					save_btn.click();
				},
			});

			monaco_editor_instance.addAction({
				id: 'preview',
				label: 'Preview',
				keybindings: [
					monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyD,
				],
				precondition: null,
				keybindingContext: null,
				run: function(ed) {
					preview_btn.click();
				},
			});

			monaco_editor_instance.addAction({
				id: 'divider',
				label: 'Divider',
				keybindings: [
					monaco.KeyMod.Alt | monaco.KeyCode.KeyD,
				],
				precondition: null,
				keybindingContext: null,
				run: function(ed) {
					divider_btn.click();
				},
			});

			require(['MonacoMarkdown'], function(MonacoMarkdown) {
				var extension = new MonacoMarkdown.MonacoMarkdownExtension();
				extension.activate(monaco_editor_instance);
			});
		});
	}

	function preview(html) {
		var iframe = $('<iframe frameborder="0"></iframe>');
		blog_contend_md_editor.append(
			$('<div class="blog-content-md-editor-preview" style="display: none;"></div>')
				.append(iframe)
		);
		var iframe_document = iframe[0].contentWindow.document;
		iframe_document.open();
		iframe_document.write(html);
		iframe_document.close();
		$(iframe_document).bind('keydown', 'ctrl+d', function() {
			preview_btn.click();
			return false;
		});
		
		blog_contend_md_editor.find('.blog-content-md-editor-in').slideUp('fast');
		blog_contend_md_editor.find('.blog-content-md-editor-preview').slideDown('fast', function() {
			set_preview_status(2);
			iframe.focus(); 
			iframe.find('body').focus();
		});
	}
	function save(config) {
		if (config == undefined) {
			config = {};
		}
		config = $.extend({
			need_preview: false,
			fail: function() {
			},
			done: function() {
			}
		}, config);
		
		if (!last_save_done) {
			config.fail();
			config.done();
			return;
		}
		last_save_done = false;
		
		if (config.need_preview) {
			set_preview_status(1);
		}
		
		var post_data = {};
		$($(this_form).serializeArray()).each(function() {
			post_data[this["name"]] = this["value"];
		});
		if (config.need_preview) {
			post_data['need_preview'] = 'on';
		}
		post_data["save-" + name] = '';
		
		$.ajax({
			type : 'POST',
			data : post_data,
			url : window.location.href,
			success : function(data) {
				try {
					data = JSON.parse(data)
				} catch (e) {
					alert(data);
					if (config.need_preview) {
						set_preview_status(0);
					}
					config.fail();
					return;
				}
				var ok = true;
				$(['title', 'content_md', 'tags']).each(function() {
					ok &= showErrorHelp(name + '_' + this, data[this]);
				});
				if (data.extra !== undefined) {
					alert(data.extra);
					ok = false;
				}
				if (!ok) {
					if (config.need_preview) {
						set_preview_status(0);
					}
					config.fail();
					return;
				}
				
				set_saved(true);
				
				if (config.need_preview) {
					preview(data.html);
				}
				
				if (data.blog_write_url) {
					window.history.replaceState({}, document.title, data.blog_write_url);
				}
				if (data.blog_url) {
					$('#a-' + name + '_view_blog').attr('href', data.blog_url).show();
				}
				if (data.blog_id) {
					$('#div-blog-id').html('<small>博客 ID：<b>' + data.blog_id + '</b></small>').show();
				}
			}
		}).fail(function() {
			if (config.need_preview) {
				set_preview_status(0);
			}
			config.fail();
		}).always(function() {
			last_save_done = true;
			config.done();
		});
	}
	
	// event
	$.merge(input_title, input_tags).on('input', function() {
		set_saved(false);
	});
	$('#a-' + name + '_save').click(function (e) {
		e.preventDefault();
		save({
			done: function () {
				location.reload();
			}
		});
	});
	save_btn.click(function() {
		save();
	});
	preview_btn.click(function() {
		if (preview_btn.hasClass('active')) {
			set_preview_status(0);
			blog_contend_md_editor.find('.blog-content-md-editor-in').slideDown('fast');
			blog_contend_md_editor.find('.blog-content-md-editor-preview').slideUp('fast', function() {
				$(this).remove();
			});
		} else {
			save({need_preview: true});
		}
	});
	input_is_hidden.on('switchChange.bootstrapSwitch', function(e, state) {
		var ok = true;
		if (!state && !confirm("你确定要公开吗？")) {
			ok = false;
		}
		if (!ok) {
			input_is_hidden.bootstrapSwitch('toggleState', true);
		} else {
			input_is_hidden.bootstrapSwitch('readonly', true);
			var succ = true;
			save({
				fail: function() {
					succ = false;
				},
				done: function() {					
					input_is_hidden.bootstrapSwitch('readonly', false);
					if (!succ) {
						input_is_hidden.bootstrapSwitch('toggleState', true);
					}
				}
			});
		}
	});
	
	// init hot keys
	$(document).bind('keydown', 'ctrl+d', function() {
		preview_btn.click();
		return false;
	});
	$.merge(input_title, input_tags).bind('keydown', 'ctrl+s', function() {
		save_btn.click();
		return false;
	});
	
	if (this_form) {
		$(this_form).submit(function() {
			before_window_unload_message = null;
		});
	}
}
