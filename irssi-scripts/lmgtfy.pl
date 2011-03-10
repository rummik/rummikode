use Irssi;
use vars qw($VERSION %IRSSI);
$VERSION = '1.1';
%IRSSI = (
    authors	=> 'Kim Zick',
    name	=> 'LMGTFY',
    description	=> 'Let me google that for you: /lmgtfy lmgtfy.com',
    license	=> 'http://gitorious.org/rummikode/default/blobs/master/LICENSE',
    url		=> 'http://gitorious.org/rummikode/default',
);

use URI::Escape;
use strict;

Irssi::command_bind('lmgtfy', sub {
	my ($search, $target) = (@_[0], @_[2]);

	if ($target && $target->{type} =~ /CHANNEL|QUERY/) {
		$search = uri_escape($search);
		$search =~ s/%20/+/g;

		$target->command('SAY http://lmgtfy.com/?q=' . $search);
	}
});
