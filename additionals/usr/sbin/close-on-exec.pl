#!/usr/bin/perl -w

use English;
use warnings;

use POSIX;

die("No hay programa a ejecutar") unless @ARGV > 0;

my @l = </proc/self/fd/*>; 
foreach $s (@l) {
	if ( -e $s ) {
		my $base = substr($s, 14);
		if (!($base eq '0' || $base eq '1' || $base eq '2')) {
			#print "Se cierra descriptor $base ...\n";
			my $r = POSIX::close($base);
			#die("POSIX::close($base) - $!") unless defined($r);
		}
	}
}
POSIX::setsid();

unshift @ARGV, 'LC_MESSAGES=C';
# Entrecomillar SIEMPRE los argumentos. La version anterior solo lo hacia con
# los que contenian un espacio, asi que un argumento con metacaracteres del
# shell pero sin espacios (p. ej. el JSON de 'netconfig --apply --change')
# llegaba destrozado: el shell le quitaba las comillas dobles y expandia las
# llaves por las comas, partiendo el JSON en varios argumentos.
sub sh_quote {
	my $s = shift;
	$s =~ s/'/'\\''/g;
	return "'".$s."'";
}
my @finalcmd = map { sh_quote($_) } @ARGV;
$cmd = join(" ",@finalcmd);
exec $cmd;
