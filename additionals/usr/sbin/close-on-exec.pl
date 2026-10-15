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

# LC_MESSAGES=C es una variable de entorno, no un comando: se exporta y se
# ejecuta el programa directamente, sin pasar por el shell. Asi ningun argumento
# necesita entrecomillado y los argumentos estructurados (p. ej. el JSON de
# 'netconfig --apply --change') llegan intactos.
$ENV{LC_MESSAGES} = 'C';
exec { $ARGV[0] } @ARGV;
die("No se pudo ejecutar $ARGV[0]: $!\n");
