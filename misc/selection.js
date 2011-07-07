/*
 * jQuery text selection plugin
 *
 * Copyright (c) 2011 Kim Zick
 * Provided in the Public Domain (or under the WTFPL, at your choice)
 *
 * Author: Kim Zick <k@9k1.us> (http://www.rummik.com/)
 * Version: 0.1.1
 */

(function($) {
	var selection = {
		getIndex: function(elem) {
			var start, end;

			if (!elem)
				return undefined;

			if ('selectionStart' in elem) {
				start = elem.selectionStart;
				end   = elem.selectionEnd;
			} else {
				return undefined;
			}

			start = Math.min(start, end);
			end   = Math.max(start, end);

			return {start: start, end: end};
		},

		setIndex: function(elem, start, end) {
			if (!elem)
				return undefined;

			start = Math.min(start, end);
			end   = Math.max(start, end);

			if ('selectionStart' in elem) {
				elem.selectionStart = start;
				elem.selectionEnd   = end;
			}
		}
	};

	$.fn.selection = function(text) {
		var sel, elem = this[0];

		if (!arguments.length) {
			if ((sel = selection.getIndex(elem)) != undefined)
				return $(elem).val().substr(sel.start, sel.end - sel.start);
			else
				return '';
		}
	
		return this.each(function() {
			if ((sel = selection.getIndex(this)) != undefined) {
				$(this).val($(this).val().substr(0, sel.start) + text + $(this).val().substr(sel.end));
				selection.setIndex(this, sel.start, text.length + sel.start);
			}
		});
	};
})(jQuery);
