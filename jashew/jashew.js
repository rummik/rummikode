#!/usr/bin/node
var HTTP    = require('http'),
    exec    = require('child_process').exec,
    request = {
	host: 'mobile.vzw.com',
	port: 80,
	path: '/vzam/servlet/vzam?client=chrome&serviceName=accountInfo&subServiceName=poundData',
	headers: { 'User-Agent': 'cashew 0.0.1' }
    };

(function() {
	var lastupdate = 0;

	HTTP.get(request, function(res) {
		res.on('data', function(chunk) {
			var total = 0, remaining = 0, plan, response = JSON.parse(chunk);

			if (response.status != 'OK')
				return;

			var kb = function(n) { return Math.floor(n / 1024) + 'KB'; };
			var mb = function(n) { return (Math.floor(n * 100 / (1024 * 1024)) / 100) + 'MB'; };

			if (lastupdate)
				console.log('');

			console.log('-------------');

			var d = new Date();
			while (plan = response.plans.pop()) {
				if (Date.now() + (d.getTimezoneOffset() * 60) > Date.parse(plan.expirationTime)) // || Date.parse(plan.lastUpdate) <= lastupdate)
					continue;

				lastupdate = Date.parse(plan.lastUpdate);

				remaining += (plan.maxBytes - plan.usedBytes);
				total     += plan.maxBytes;

				console.log('Last Update:  ' + new Date(plan.lastUpdate));
				console.log('Plan:         ' + plan.planName + ' [' + plan.planType + ']');
				console.log('Usage:        ' + kb(plan.usedBytes) + '/' + kb(plan.maxBytes) + ' (' + mb(plan.usedBytes) + ')');
				console.log('Expires:      ' + new Date(plan.expirationTime));
				console.log('-------------');
			}

			exec('/sbin/ifconfig', function(error, stdout, stderr) {
				var sum = 0;

				if (stdout.match(/ppp0(.|\n )+/)) {
					sum = stdout.match(/ppp0(.|\n )+/)[0].match(/RX bytes:(\d+).+TX bytes:(\d+)/).slice(1, 3);
					sum = (+sum[0]) + (+sum[1]);
				}

				console.log('Online Usage: ' + kb(sum) + '/' + kb(remaining) + ' (' + mb(sum) + ')');
				console.log('Remaining:    ' + mb(remaining - sum) + '/' + mb(total) + ' (' + mb(total - (remaining - sum)) + ')');
				console.log('-------------');
			});
		});
	});

	// call ourselves in five minutes
	setTimeout(arguments.callee, 300 * 1000);
})();
