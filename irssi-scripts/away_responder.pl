use Irssi;
use vars qw($VERSION %IRSSI);
$VERSION = '0.8.1';
%IRSSI = (
	authors		=> 'Kim Zick',
	name		=> 'Away Responder',
	description	=> 'Responds with current away message when highlighted.',
	license		=> 'GPL v2',
	url		=> 'http://gitorious.org/rummikode/default',
	credits		=> 'Loosely based on away_hilight_notice by Geert Hauwaerts <geert@irssi.org>',
	credits_url	=> 'http://irssi.hauwaerts.be/away_hilight_notice.pl',
);

use strict;

my ($timeout, %highlight) = (600, undef);

Irssi::settings_add_int('lookandfeel', 'away_response_timeout', $timeout);

Irssi::signal_add('print text', sub {
	my ($dest, $server, $command, $target, $light) = (
		@_[0],
		@_[0]->{'server'},
		'NOTICE',
		Irssi::parse_special('$;'),
		undef
	);

	if (($dest->{'level'} & (MSGLEVEL_HILIGHT | MSGLEVEL_MSGS)) && !($dest->{'level'} & MSGLEVEL_NOHILIGHT)) {
		if (@_[0]->{'window'}->items()->{'type'} eq 'QUERY') {
			($command, $target) = ('MSG', $dest->{'target'});
		}

		$light = lc "$server->{'tag'}/$target";

		if (%highlight->{$light} && time - %highlight->{$light} >= $timeout) {
			%highlight->{$light} = undef;
		}

		if ($server && $server->{'usermode_away'} && !%highlight->{$light}) {
			%highlight->{$light} = time;
			$server->command("^$command $target Away: $server->{'away_reason'}");
		}
	}
});

sub setup {
        $timeout = Irssi::settings_get_int('away_response_timeout');
}

Irssi::signal_add('setup changed', \&setup); &setup;
Irssi::signal_add('away mode changed', sub { %highlight = (); });
