#!/usr/bin/perl
# This program comes without any warranty, to the extent permitted by law.
# License: http://gitorious.org/rummikode/default/blobs/master/LICENSE

my $version = 1.86;


# ==================================================== #
#  password generator configuration (yes, edit this!)  #
# ==================================================== #

my %config = (
	'auto'     => 0,              # autofill from config
	'request'  => 1,              # request secret password
	'hashmode' => 2,              # hash mode (1, 2)
	'login'    => trim(`whoami`), # default login name

	# a string that only you know, this will be used in generating the password(s)
	'secret'   => '',

	'length'   => 8,              # default password length
	'safe'     => 1,              # generate passwords that should be safe everywhere
	'count'    => 5               # number of passwords to generate
);
























# ========================================================== #
#  unless you know what you're doing, don't play below here  #
# ========================================================== #

use strict;
use Switch 'Perl6';
use vars qw($name);

$name = $0;
$name =~ s/^.*\/([^\/]+)$/$1/;

foreach (@ARGV) {
	given ($_) {
		when /^(-v|--version)$/ {
			print "$name: Version: $version\n";
			exit 0;
		}

		when /^(-l=|--length=)\d+$/ {
			$config{'length'} = $1 if (m/(\d+)$/);
		}

		when /^(-c=|--count=)\d+$/ {
			$config{'count'} = $1 if (m/(\d+)$/);
		}

		when /^--(un)?safe$/ {
			$config{'safe'} = !m/un/;
		}

		when m/^-+[^=]+/ {
			print "$name: Unknown argument: ", m/(-+[^=]+)/, "\n";
			exit 1;
		}
	}
}


use Term::ANSIColor qw(:constants);

use Digest::SHA1 qw(sha1 sha1_hex sha1_base64);
use Digest::MD5 qw(md5 md5_hex md5_base64);
use Convert::UU qw(uudecode uuencode);

use vars qw(%replace $login $domain $secret $length $hash);

if (!$config{'auto'}) {
	$login  = &read('Login:', $config{'login'});
	$domain = &read('Domain:');
	$secret = $config{'request'} ? &read('Secret:', $config{'secret'}) : $config{'secret'};
	$length = &read('Length:') || $config{'length'};
} else {
	$login  = $config{'login'};
	$domain = &read('Domain:');
	$secret = $config{'secret'};
	$length = $config{'length'};
}


($login, $secret, $domain) = trim($login, $secret, $domain);

$length = !int $length ? length $length : int $length;
$length = $length > 4 ? ($config{'hashmode'} == 1 ? ($length < 20 ? $length : 20) : ($length < 100 ? $length : 100)) : 4;


print "\n", BOLD, YELLOW, '== PASSWORDS ==', RESET, "\n";
for (0 .. $config{'count'}-1) {
	$hash = &hash($_);

	print BOLD, BLUE, $_ + 1, ': ', RED, substr($hash, int((length($hash) - $length) / 2), $length), RESET, "\n";
}





sub read {
	my ($text, $default) = @_;
	my $read;

	while ($read eq '') {
		print BOLD, BLUE, $text, ' ', RESET;
		chomp($read = <STDIN>);

		$read = $default if ($read eq '' && $default);
	}

	return $read;
}

sub hash {
	my ($hash, $string, $input);
	$input = shift || '';

	given ($config{'hashmode'}) {
		when 1 {
			$string  = "$login\n$domain\n$secret\n" . $input;
			$hash = substr(uuencode(sha1_hex($string) . md5_hex($string)), 22, -4);
		}

		default {
			$string = mix($secret . $domain . $login . $input);
			$hash = mix(substr(uuencode(sha1($string) . md5_base64($string) . md5_hex($string) . sha1_hex($string) . sha1_base64($string) . md5($string)), 22, -4));
		}
	}

	return clean($hash);
}

sub trim {
	@_[$_] =~ s/^\s+|\s+$// foreach (0 .. $#_); @_;
}

sub mix {
	join '', sort split(/(.{1,4})/, shift);
}

sub clean {
	my $_ = shift;
	tr{`@\-,<%&();#!.$="?\\+:*[]> \n'/^_}{a-cdefghijklmnopqrstuvwxyzABC_} if ($config{'safe'} || (tr[ \n][{}] && 0)); $_;
}
