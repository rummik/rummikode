Math.eval = function(expression, precision) {
	precision = Math.max(0, Math.min(10, typeof precision == 'undefined' ? 4 : parseInt(precision)));
	expression = expression.replace(/\s+/g, '');

	var i, p, start, regex, last, operator, operation, expr = '',
	    number = '([+-]?(?:\\d+(?:\\.\\d*)?|\\.\\d+))',
	    psh = function(n) {
		    n = new String(n);
		    n = n.split('.');
		    return +((n[0] || '') + (n[1] || '').substr(0, precision) + (new Array(Math.max(0, precision - (n[1] || '').length) + 1).join('0')));
	    },

	    pus = function(n) {
		    n = new String(n);
		    return +((n.substr(0, n.length - precision) + '.' + n.substr(n.length - precision)).replace(/\.?0*$/, ''));
	    },

	    operators = {
		'^': function(a, b) { return Math.pow(a, b); },
		'*': function(a, b) { return a * b; },
		'/': function(a, b) { return a / b; },
		'+': function(a, b) { return pus(psh(a) + psh(b)); },
		'-': function(a, b) { return pus(psh(a) - psh(b)); },
	    };


	for (p=i=0; i<expression.length; i++) {
		switch (expression[i]) {
			case '(':
				p++;

				if (start == undefined)
					start = i + 1;
				break;

			case ')':
				if (--p > 0)
					continue;

				expr += arguments.callee(expression.substr(start, i - start), precision);

				start = undefined;
				break;

			default:
				if (p == 0)
					expr += expression.charAt(i);
				break;
		}
	}

	expression = expr;

	for (operator in operators) {
		regex = new RegExp('([^+-][+-])??' + number + operator.replace(/([*+^])/, '\\$1') + number);
		operation = function(m, o, a, b) { return (o || '').toString() + operators[operator](+a, +b); };
		for (last=undefined; last!=expression; last=expression, expression=expression.replace(regex, operation));
	}

	if (!new RegExp('^' + number + '$').test(expression))
		return false;

	return +expression;
};
