Math.eval = function(expression, precision) {
	var i, p, start, regex, last, operator, operation, expr,
	    number = '([+-]?(?:\\d+(?:\\.\\d*)?|\\.\\d+))',
	    operators = {
		'^': function(a, b) { return Math.pow(a, b); },
		'*': function(a, b) { return a * b; },
		'/': function(a, b) { return a / b; },
		'+': function(a, b) { return a + b; },
		'-': function(a, b) { return a - b; },
	};

	expr = expression = expression.replace(/\s+/g, '');

	precision = Math.max(0, Math.min(12, typeof precision == 'undefined' ? 4 : parseInt(precision)));

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

				expr = expr.substr(1, (start - (expression.length - expr.length)) - 1)
				     + arguments.callee(expression.substr(start, i - start), precision)
				     + expression.substr(i + 1);

				start = undefined;
				break;
		}
	}

	expression = expr.replace(new RegExp(number, 'g'), function(n) {
		n = n.split('.');
		return (n[0] || '') + (n[1] || '').substr(0, precision) + (new Array(Math.max(0, precision - (n[1] || '').length) + 1).join('0'));
	});

	for (operator in operators) {
		regex = new RegExp('([^+-][+-])?' + number + operator.replace(/([*+^])/, '\\$1') + number, 'g');
		operation = function(m, o, a, b) { return (o || '').toString() + operators[operator](+a, +b); };
		for (last=undefined; last!=expression; last=expression, expression=expression.replace(regex, operation));
	}

	if (!new RegExp('^' + number + '$').test(expression))
		return false;

	return expression.replace(new RegExp(number, 'g'), function(n) {
		return (n.substr(0, n.length - precision) + '.' + n.substr(n.length - precision)).replace(/\.?0*$/, '');
	});
};
