use Irssi;
use vars qw($VERSION %IRSSI);
$VERSION = '0.7.0';
%IRSSI = (
	authors		=> 'Kim Zick',
	name		=> 'Detach Away',
	description	=> 'Automatically set away status when tmux or screen detaches',
	license		=> 'GPL v2',
	url		=> 'http://gitorious.org/rummikode/default',
	credits		=> 'Loosely based on screen_away by Andreas "ads" Scherbaum <ads@ufp.de>'
);

# known bugs:
#   does not set away unless *all* of your tmux sessions are detached

use strict;

my ($interval, $default_message, $away) = (5, 'sesion detached.', 0);
my ($socket, $message, $timer, $store);

Irssi::theme_register(['detach_away_crap', '{line_start}{hilight Detach Away:} $0']);

if ($ENV{'TMUX'}) {
	$ENV{'TMUX'} =~ m/^([^,]+)/;
	$socket = $1;
	$default_message = "TMUX $default_message";
} elsif ($ENV{'STY'}) {
	`screen -ls` =~ m!(/var/run/screen/[^/]+)\.!;
	$socket = "$1/$ENV{'STY'}";
	$default_message = "GNU Screen $default_message";
}

unless ($socket && -e $socket) {
	Irssi::printformat(MSGLEVEL_CLIENTCRAP, 'detach_away_crap', 'No socket');
	return;
}

Irssi::settings_add_bool('lookandfeel', 'away_on_detach', 1);
Irssi::settings_add_bool('lookandfeel', 'away_return_on_attach', 1);
Irssi::settings_add_str('lookandfeel', 'away_message', $default_message);
Irssi::settings_add_int('misc', 'detach_poll_interval', $interval);

sub interval {
	return unless ($away == -x $socket);
	$away = !$away;
	return unless (($away && Irssi::settings_get_bool('away_on_detach')) ||
	              (!$away && Irssi::settings_get_bool('away_return_on_attach'))
	);

	Irssi::command('window 1') if ($away);

	Irssi::printformat(MSGLEVEL_CLIENTCRAP, 'detach_away_crap',
		'Marking ' . ($away ? 'away' : 'back'));

	foreach my $server (Irssi::servers()) {
		$store->{$server->{'tag'}}->{'away'} = $server->{'usermode_away'} if ($away);
          	next if ($store->{$server->{'tag'}}->{'away'});

		my $silc = $server->{'chat_type'} eq 'SILC';
		$server->command('away ' . ($silc ? '' : '-one ') . ($away ? $message : ''));
	}
}

sub setup {
	$message = Irssi::settings_get_str('away_message');
	$message = $default_message if ($message eq '');

	$interval = Irssi::settings_get_int('detach_poll_interval');

	Irssi::timeout_remove($timer) if (defined $timer);
	$timer = Irssi::timeout_add($interval * 1000, \&interval, '');
}

Irssi::signal_add('setup changed', \&setup); &setup;
